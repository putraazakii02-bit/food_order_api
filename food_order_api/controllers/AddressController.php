<?php
// controllers/AddressController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AddressController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function getUserId(): int {
        return Auth::userId() ?? 1;
    }

    public function getAll(): void {
        $userId = $this->getUserId();
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, id ASC");
        $stmt->execute([':uid' => $userId]);
        $addresses = $stmt->fetchAll();

        foreach ($addresses as &$a) {
            $a['id'] = (int)$a['id'];
            $a['user_id'] = (int)$a['user_id'];
            $a['is_default'] = (bool)$a['is_default'];
        }

        Response::json($addresses, 200, 'Berhasil memuat daftar alamat');
    }

    public function create(): void {
        $userId = $this->getUserId();
        $data = Response::getJsonInput();

        $label = trim($data['label'] ?? 'Rumah');
        $recipientName = trim($data['recipient_name'] ?? 'Ahmad Fauzan');
        $phone = trim($data['phone'] ?? '081234567890');
        $fullAddress = trim($data['full_address'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if (empty($fullAddress)) {
            Response::error('Alamat lengkap wajib diisi', 422);
        }

        if ($isDefault) {
            $this->db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :uid")->execute([':uid' => $userId]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO addresses (user_id, label, recipient_name, phone, full_address, notes, is_default)
            VALUES (:uid, :label, :rec, :phone, :addr, :notes, :def)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':label' => $label,
            ':rec' => $recipientName,
            ':phone' => $phone,
            ':addr' => $fullAddress,
            ':notes' => $notes,
            ':def' => $isDefault
        ]);

        $newId = (int)$this->db->lastInsertId();
        $fetch = $this->db->prepare("SELECT * FROM addresses WHERE id = :id");
        $fetch->execute([':id' => $newId]);
        $addr = $fetch->fetch();
        $addr['id'] = (int)$addr['id'];
        $addr['is_default'] = (bool)$addr['is_default'];

        Response::json($addr, 201, 'Alamat berhasil ditambahkan');
    }

    public function update(int $id): void {
        $userId = $this->getUserId();
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            Response::error('Alamat tidak ditemukan', 404);
        }

        $data = Response::getJsonInput();
        $label = trim($data['label'] ?? $existing['label']);
        $recipientName = trim($data['recipient_name'] ?? $existing['recipient_name']);
        $phone = trim($data['phone'] ?? $existing['phone']);
        $fullAddress = trim($data['full_address'] ?? $existing['full_address']);
        $notes = array_key_exists('notes', $data) ? trim($data['notes']) : $existing['notes'];

        $upStmt = $this->db->prepare("
            UPDATE addresses SET
                label = :label,
                recipient_name = :rec,
                phone = :phone,
                full_address = :addr,
                notes = :notes
            WHERE id = :id AND user_id = :uid
        ");
        $upStmt->execute([
            ':label' => $label,
            ':rec' => $recipientName,
            ':phone' => $phone,
            ':addr' => $fullAddress,
            ':notes' => $notes,
            ':id' => $id,
            ':uid' => $userId
        ]);

        $fetch = $this->db->prepare("SELECT * FROM addresses WHERE id = :id");
        $fetch->execute([':id' => $id]);
        $addr = $fetch->fetch();
        $addr['id'] = (int)$addr['id'];
        $addr['is_default'] = (bool)$addr['is_default'];

        Response::json($addr, 200, 'Alamat berhasil diperbarui');
    }

    public function setDefault(int $id): void {
        $userId = $this->getUserId();
        $stmt = $this->db->prepare("SELECT id FROM addresses WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        if (!$stmt->fetch()) {
            Response::error('Alamat tidak ditemukan', 404);
        }

        $this->db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :uid")->execute([':uid' => $userId]);
        $this->db->prepare("UPDATE addresses SET is_default = 1 WHERE id = :id AND user_id = :uid")->execute([':id' => $id, ':uid' => $userId]);

        $this->getAll();
    }

    public function delete(int $id): void {
        $userId = $this->getUserId();
        $stmt = $this->db->prepare("DELETE FROM addresses WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        Response::json(['deleted_id' => $id], 200, 'Alamat berhasil dihapus');
    }
}
