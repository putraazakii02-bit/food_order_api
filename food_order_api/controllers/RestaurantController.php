<?php
// controllers/RestaurantController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';

class RestaurantController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): void {
        $stmt = $this->db->query("SELECT * FROM restaurants ORDER BY rating DESC, id ASC");
        $restaurants = $stmt->fetchAll();

        foreach ($restaurants as &$r) {
            $r['id'] = (int)$r['id'];
            $r['rating'] = (float)$r['rating'];
            $r['reviews_count'] = (int)$r['reviews_count'];
            $r['is_open'] = (bool)$r['is_open'];
        }

        Response::json($restaurants, 200, 'Berhasil memuat daftar restoran');
    }

    public function getById(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM restaurants WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            Response::error('Restoran tidak ditemukan', 404);
        }

        $restaurant['id'] = (int)$restaurant['id'];
        $restaurant['rating'] = (float)$restaurant['rating'];
        $restaurant['reviews_count'] = (int)$restaurant['reviews_count'];
        $restaurant['is_open'] = (bool)$restaurant['is_open'];

        // Get foods sold by this restaurant
        $foodStmt = $this->db->prepare("SELECT * FROM foods WHERE restaurant_id = :id AND is_available = 1 ORDER BY is_popular DESC, id ASC");
        $foodStmt->execute([':id' => $id]);
        $foods = $foodStmt->fetchAll();

        foreach ($foods as &$f) {
            $f['id'] = (int)$f['id'];
            $f['restaurant_id'] = (int)$f['restaurant_id'];
            $f['category_id'] = (int)$f['category_id'];
            $f['price'] = (int)$f['price'];
            $f['original_price'] = $f['original_price'] !== null ? (int)$f['original_price'] : null;
            $f['rating'] = (float)$f['rating'];
            $f['reviews_count'] = (int)$f['reviews_count'];
            $f['is_popular'] = (bool)$f['is_popular'];
            $f['is_available'] = (bool)$f['is_available'];
        }

        $restaurant['menus'] = $foods;

        Response::json($restaurant, 200, 'Berhasil memuat detail restoran beserta menunya');
    }

    public function create(): void {
        $data = Response::getJsonInput();

        $name = trim($data['name'] ?? '');
        $tagline = trim($data['tagline'] ?? 'Cita Rasa Pilihan');
        $imageUrl = trim($data['image_url'] ?? 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80');
        $rating = (float)($data['rating'] ?? 4.8);
        $distance = trim($data['distance'] ?? '1.5 km');
        $deliveryTime = trim($data['delivery_time'] ?? '20-25 min');
        $address = trim($data['address'] ?? 'Surabaya');
        $phone = trim($data['phone'] ?? '08123456789');

        if (empty($name)) {
            Response::error('Nama restoran wajib diisi', 422);
        }

        $stmt = $this->db->prepare("
            INSERT INTO restaurants (name, tagline, image_url, rating, reviews_count, distance, delivery_time, address, phone, is_open)
            VALUES (:name, :tagline, :image_url, :rating, 50, :distance, :delivery_time, :address, :phone, 1)
        ");
        $stmt->execute([
            ':name' => $name,
            ':tagline' => $tagline,
            ':image_url' => $imageUrl,
            ':rating' => $rating,
            ':distance' => $distance,
            ':delivery_time' => $deliveryTime,
            ':address' => $address,
            ':phone' => $phone,
        ]);

        $newId = (int)$this->db->lastInsertId();
        $this->getById($newId);
    }

    public function update(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM restaurants WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            Response::error('Restoran tidak ditemukan', 404);
        }

        $data = Response::getJsonInput();
        $name = trim($data['name'] ?? $existing['name']);
        $tagline = trim($data['tagline'] ?? $existing['tagline']);
        $imageUrl = trim($data['image_url'] ?? $existing['image_url']);
        $rating = isset($data['rating']) ? (float)$data['rating'] : (float)$existing['rating'];
        $distance = trim($data['distance'] ?? $existing['distance']);
        $deliveryTime = trim($data['delivery_time'] ?? $existing['delivery_time']);
        $address = trim($data['address'] ?? $existing['address']);
        $isOpen = isset($data['is_open']) ? (int)$data['is_open'] : (int)$existing['is_open'];

        $updateStmt = $this->db->prepare("
            UPDATE restaurants SET
                name = :name,
                tagline = :tagline,
                image_url = :image_url,
                rating = :rating,
                distance = :distance,
                delivery_time = :delivery_time,
                address = :address,
                is_open = :is_open
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':name' => $name,
            ':tagline' => $tagline,
            ':image_url' => $imageUrl,
            ':rating' => $rating,
            ':distance' => $distance,
            ':delivery_time' => $deliveryTime,
            ':address' => $address,
            ':is_open' => $isOpen,
            ':id' => $id
        ]);

        $this->getById($id);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare("SELECT id FROM restaurants WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Restoran tidak ditemukan', 404);
        }

        $delStmt = $this->db->prepare("DELETE FROM restaurants WHERE id = :id");
        $delStmt->execute([':id' => $id]);

        Response::json(['deleted_id' => $id], 200, 'Restoran berhasil dihapus');
    }
}
