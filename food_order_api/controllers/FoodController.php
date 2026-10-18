<?php
// controllers/FoodController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';

class FoodController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): void {
        $category = $_GET['category'] ?? null;
        $isPopular = isset($_GET['is_popular']) ? (int)$_GET['is_popular'] : null;
        $search = $_GET['search'] ?? null;
        $restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;
        $sort = $_GET['sort'] ?? null;

        $sql = "SELECT f.*, r.name AS restaurant_name FROM foods f JOIN restaurants r ON f.restaurant_id = r.id WHERE f.is_available = 1";
        $params = [];

        if (!empty($category) && strtolower($category) !== 'semua') {
            $sql .= " AND LOWER(f.category) = LOWER(:category)";
            $params[':category'] = $category;
        }

        if ($isPopular !== null) {
            $sql .= " AND f.is_popular = :is_popular";
            $params[':is_popular'] = $isPopular;
        }

        if (!empty($search)) {
            $sql .= " AND (f.name LIKE :search_name OR f.description LIKE :search_desc OR r.name LIKE :search_resto)";
            $params[':search_name'] = "%$search%";
            $params[':search_desc'] = "%$search%";
            $params[':search_resto'] = "%$search%";
        }

        if ($restaurantId !== null) {
            $sql .= " AND f.restaurant_id = :restaurant_id";
            $params[':restaurant_id'] = $restaurantId;
        }

        if ($sort === 'price_asc') {
            $sql .= " ORDER BY f.price ASC";
        } elseif ($sort === 'price_desc') {
            $sql .= " ORDER BY f.price DESC";
        } elseif ($sort === 'rating') {
            $sql .= " ORDER BY f.rating DESC";
        } else {
            $sql .= " ORDER BY f.is_popular DESC, f.id ASC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $foods = $stmt->fetchAll();

        // Cast boolean and numeric fields cleanly for JSON
        foreach ($foods as &$food) {
            $food['id'] = (int)$food['id'];
            $food['restaurant_id'] = (int)$food['restaurant_id'];
            $food['category_id'] = (int)$food['category_id'];
            $food['price'] = (int)$food['price'];
            $food['original_price'] = $food['original_price'] !== null ? (int)$food['original_price'] : null;
            $food['rating'] = (float)$food['rating'];
            $food['reviews_count'] = (int)$food['reviews_count'];
            $food['is_popular'] = (bool)$food['is_popular'];
            $food['is_available'] = (bool)$food['is_available'];
        }

        Response::json($foods, 200, 'Berhasil memuat daftar makanan');
    }

    public function getById(int $id): void {
        $stmt = $this->db->prepare("SELECT f.*, r.name AS restaurant_name FROM foods f JOIN restaurants r ON f.restaurant_id = r.id WHERE f.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $food = $stmt->fetch();

        if (!$food) {
            Response::error('Menu makanan tidak ditemukan', 404);
        }

        $food['id'] = (int)$food['id'];
        $food['restaurant_id'] = (int)$food['restaurant_id'];
        $food['category_id'] = (int)$food['category_id'];
        $food['price'] = (int)$food['price'];
        $food['original_price'] = $food['original_price'] !== null ? (int)$food['original_price'] : null;
        $food['rating'] = (float)$food['rating'];
        $food['reviews_count'] = (int)$food['reviews_count'];
        $food['is_popular'] = (bool)$food['is_popular'];
        $food['is_available'] = (bool)$food['is_available'];

        // Get available toppings for food
        $toppingStmt = $this->db->prepare("SELECT id, name, price FROM food_toppings WHERE food_id = :id OR food_id IS NULL");
        $toppingStmt->execute([':id' => $id]);
        $toppings = $toppingStmt->fetchAll();
        foreach ($toppings as &$top) {
            $top['id'] = (int)$top['id'];
            $top['price'] = (int)$top['price'];
        }

        $food['toppings'] = $toppings;
        $food['spicy_levels'] = [
            'Original',
            'Sedang 🌶️',
            'Pedas 🌶️🌶️',
            'Ekstra 🌶️🌶️🌶️'
        ];

        Response::json($food, 200, 'Berhasil memuat detail makanan');
    }

    public function create(): void {
        $data = Response::getJsonInput();

        $name = trim($data['name'] ?? '');
        $restaurantId = (int)($data['restaurant_id'] ?? 1);
        $categoryId = (int)($data['category_id'] ?? 4);
        $price = (int)($data['price'] ?? 0);
        $originalPrice = isset($data['original_price']) && $data['original_price'] !== '' ? (int)$data['original_price'] : null;
        $description = trim($data['description'] ?? '');
        $imageUrl = trim($data['image_url'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&auto=format&fit=crop&q=80');
        $category = trim($data['category'] ?? 'Makanan');
        $deliveryTime = trim($data['delivery_time'] ?? '15-25 min');
        $distance = trim($data['distance'] ?? '1.5 km');
        $discountBadge = !empty($data['discount_badge']) ? trim($data['discount_badge']) : null;
        $isPopular = !empty($data['is_popular']) ? 1 : 0;

        if (empty($name) || $price <= 0) {
            Response::error('Nama makanan dan harga valid wajib diisi', 422);
        }

        // Get restaurant name
        $restoStmt = $this->db->prepare("SELECT name FROM restaurants WHERE id = :id LIMIT 1");
        $restoStmt->execute([':id' => $restaurantId]);
        $resto = $restoStmt->fetch();
        $restaurantName = $resto ? $resto['name'] : 'Restoran Pilihan';

        $stmt = $this->db->prepare("
            INSERT INTO foods (restaurant_id, category_id, name, restaurant_name, image_url, price, original_price, rating, description, category, delivery_time, distance, reviews_count, discount_badge, is_popular, is_available)
            VALUES (:restaurant_id, :category_id, :name, :restaurant_name, :image_url, :price, :original_price, 4.8, :description, :category, :delivery_time, :distance, 10, :discount_badge, :is_popular, 1)
        ");
        $stmt->execute([
            ':restaurant_id' => $restaurantId,
            ':category_id' => $categoryId,
            ':name' => $name,
            ':restaurant_name' => $restaurantName,
            ':image_url' => $imageUrl,
            ':price' => $price,
            ':original_price' => $originalPrice,
            ':description' => $description,
            ':category' => $category,
            ':delivery_time' => $deliveryTime,
            ':distance' => $distance,
            ':discount_badge' => $discountBadge,
            ':is_popular' => $isPopular,
        ]);

        $newId = (int)$this->db->lastInsertId();
        $this->getById($newId);
    }

    public function update(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM foods WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $food = $stmt->fetch();

        if (!$food) {
            Response::error('Makanan tidak ditemukan', 404);
        }

        $data = Response::getJsonInput();

        $name = trim($data['name'] ?? $food['name']);
        $price = isset($data['price']) ? (int)$data['price'] : (int)$food['price'];
        $originalPrice = array_key_exists('original_price', $data) ? ($data['original_price'] !== null ? (int)$data['original_price'] : null) : $food['original_price'];
        $description = trim($data['description'] ?? $food['description']);
        $imageUrl = trim($data['image_url'] ?? $food['image_url']);
        $category = trim($data['category'] ?? $food['category']);
        $discountBadge = array_key_exists('discount_badge', $data) ? $data['discount_badge'] : $food['discount_badge'];
        $isPopular = isset($data['is_popular']) ? (int)$data['is_popular'] : (int)$food['is_popular'];
        $isAvailable = isset($data['is_available']) ? (int)$data['is_available'] : (int)$food['is_available'];

        $updateStmt = $this->db->prepare("
            UPDATE foods SET 
                name = :name,
                price = :price,
                original_price = :original_price,
                description = :description,
                image_url = :image_url,
                category = :category,
                discount_badge = :discount_badge,
                is_popular = :is_popular,
                is_available = :is_available
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':name' => $name,
            ':price' => $price,
            ':original_price' => $originalPrice,
            ':description' => $description,
            ':image_url' => $imageUrl,
            ':category' => $category,
            ':discount_badge' => $discountBadge,
            ':is_popular' => $isPopular,
            ':is_available' => $isAvailable,
            ':id' => $id
        ]);

        $this->getById($id);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare("SELECT id FROM foods WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Makanan tidak ditemukan', 404);
        }

        $delStmt = $this->db->prepare("DELETE FROM foods WHERE id = :id");
        $delStmt->execute([':id' => $id]);

        Response::json(['deleted_id' => $id], 200, 'Menu makanan berhasil dihapus');
    }
}
