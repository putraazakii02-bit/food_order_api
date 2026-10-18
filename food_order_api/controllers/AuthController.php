<?php
// controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AuthController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function login(): void {
        $data = Response::getJsonInput();

        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            Response::error('Email dan kata sandi wajib diisi', 422);
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Check password (supports both password_verify hash and plain for university demo)
        $passwordValid = false;
        if ($user) {
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $passwordValid = true;
            }
        }

        if (!$user || !$passwordValid) {
            Response::error('Email atau kata sandi tidak cocok.', 401);
        }

        unset($user['password']);

        // Dummy Bearer token for demo/university assignment
        $token = base64_encode($user['id'] . ':' . $user['email'] . ':' . time());

        Response::json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 200, 'Login berhasil!');
    }

    public function register(): void {
        $data = Response::getJsonInput();

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $phone = trim($data['phone'] ?? '081234567890');

        if (empty($name) || empty($email) || empty($password)) {
            Response::error('Nama, email, dan kata sandi wajib diisi', 422);
        }

        // Cek duplikasi email
        $checkStmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            Response::error('Email sudah terdaftar. Silakan gunakan email lain.', 409);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80';

        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, phone, avatar_url, membership_tier, points, vouchers_count)
            VALUES (:name, :email, :password, :phone, :avatar, 'SILVER', 100, 2)
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':phone' => $phone,
            ':avatar' => $avatar
        ]);

        $userId = $this->db->lastInsertId();

        $fetchStmt = $this->db->prepare("SELECT id, name, email, phone, avatar_url, membership_tier, points, vouchers_count, created_at FROM users WHERE id = :id");
        $fetchStmt->execute([':id' => $userId]);
        $user = $fetchStmt->fetch();

        $token = base64_encode($user['id'] . ':' . $user['email'] . ':' . time());

        Response::json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201, 'Registrasi berhasil!');
    }

    public function getProfile(): void {
        // Default to user id 1 (Ahmad Fauzan) if no auth header
        $userId = $this->extractUserId() ?? 1;

        $stmt = $this->db->prepare("SELECT id, name, email, phone, avatar_url, membership_tier, points, vouchers_count, created_at, updated_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('Pengguna tidak ditemukan', 404);
        }

        Response::json($user, 200, 'Berhasil mengambil profil pengguna');
    }

    public function updateProfile(): void {
        $userId = $this->extractUserId() ?? 1;
        $data = Response::getJsonInput();

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $avatarUrl = trim($data['avatar_url'] ?? '');

        $updates = [];
        $params = [':id' => $userId];

        if (!empty($name)) {
            $updates[] = "name = :name";
            $params[':name'] = $name;
        }
        if (!empty($phone)) {
            $updates[] = "phone = :phone";
            $params[':phone'] = $phone;
        }
        if (!empty($avatarUrl)) {
            $updates[] = "avatar_url = :avatar_url";
            $params[':avatar_url'] = $avatarUrl;
        }

        if (empty($updates)) {
            Response::error('Tidak ada data yang diubah', 400);
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $fetchStmt = $this->db->prepare("SELECT id, name, email, phone, avatar_url, membership_tier, points, vouchers_count FROM users WHERE id = :id");
        $fetchStmt->execute([':id' => $userId]);
        $user = $fetchStmt->fetch();

        Response::json($user, 200, 'Profil berhasil diperbarui');
    }

    private function extractUserId(): ?int {
        return Auth::userId();
    }
}
