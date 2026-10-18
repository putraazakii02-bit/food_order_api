<?php
// controllers/CategoryController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';

class CategoryController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): void {
        $stmt = $this->db->query("SELECT id, name, slug, icon_name, color_hex, display_order FROM categories ORDER BY display_order ASC");
        $categories = $stmt->fetchAll();

        foreach ($categories as &$c) {
            $c['id'] = (int)$c['id'];
            $c['display_order'] = (int)$c['display_order'];
        }

        Response::json($categories, 200, 'Berhasil memuat kategori');
    }

    public function getById(int $id): void {
        $stmt = $this->db->prepare("SELECT id, name, slug, icon_name, color_hex, display_order FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $category = $stmt->fetch();

        if (!$category) {
            Response::error('Kategori tidak ditemukan', 404);
        }

        $category['id'] = (int)$category['id'];
        $category['display_order'] = (int)$category['display_order'];

        Response::json($category, 200, 'Berhasil memuat kategori');
    }

    public function create(): void {
        $data = Response::getJsonInput();

        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? strtolower(str_replace(' ', '-', $name)));
        $iconName = trim($data['icon_name'] ?? 'restaurant_menu');
        $colorHex = trim($data['color_hex'] ?? '#FF5722');
        $displayOrder = (int)($data['display_order'] ?? 99);

        if (empty($name)) {
            Response::error('Nama kategori wajib diisi', 422);
        }

        $stmt = $this->db->prepare("
            INSERT INTO categories (name, slug, icon_name, color_hex, display_order)
            VALUES (:name, :slug, :icon_name, :color_hex, :display_order)
        ");
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':icon_name' => $iconName,
            ':color_hex' => $colorHex,
            ':display_order' => $displayOrder
        ]);

        $newId = (int)$this->db->lastInsertId();
        $this->getById($newId);
    }

    public function update(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            Response::error('Kategori tidak ditemukan', 404);
        }

        $data = Response::getJsonInput();
        $name = trim($data['name'] ?? $existing['name']);
        $slug = trim($data['slug'] ?? $existing['slug']);
        $iconName = trim($data['icon_name'] ?? $existing['icon_name']);
        $colorHex = trim($data['color_hex'] ?? $existing['color_hex']);
        $displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : (int)$existing['display_order'];

        $updateStmt = $this->db->prepare("
            UPDATE categories SET
                name = :name,
                slug = :slug,
                icon_name = :icon_name,
                color_hex = :color_hex,
                display_order = :display_order
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':icon_name' => $iconName,
            ':color_hex' => $colorHex,
            ':display_order' => $displayOrder,
            ':id' => $id
        ]);

        $this->getById($id);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare("SELECT id FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            Response::error('Kategori tidak ditemukan', 404);
        }

        $delStmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        $delStmt->execute([':id' => $id]);

        Response::json(['deleted_id' => $id], 200, 'Kategori berhasil dihapus');
    }
}
