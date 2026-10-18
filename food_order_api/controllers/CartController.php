<?php
// controllers/CartController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Auth.php';

class CartController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function getUserId(): int {
        return Auth::userId() ?? 1;
    }

    public function getCart(): void {
        $userId = $this->getUserId();

        $stmt = $this->db->prepare("
            SELECT 
                c.id AS cart_id,
                c.quantity,
                c.spicy_level,
                c.toppings,
                c.notes,
                f.id AS food_id,
                f.name AS food_name,
                f.restaurant_name,
                f.restaurant_id,
                f.image_url,
                f.price,
                f.original_price
            FROM cart_items c
            JOIN foods f ON c.food_id = f.id
            WHERE c.user_id = :user_id
            ORDER BY c.id ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        $items = $stmt->fetchAll();

        $subtotal = 0;
        foreach ($items as &$item) {
            $item['cart_id'] = (int)$item['cart_id'];
            $item['food_id'] = (int)$item['food_id'];
            $item['restaurant_id'] = (int)$item['restaurant_id'];
            $item['quantity'] = (int)$item['quantity'];
            $item['price'] = (int)$item['price'];
            $item['original_price'] = $item['original_price'] !== null ? (int)$item['original_price'] : null;

            $toppingList = json_decode($item['toppings'] ?? '[]', true) ?: [];
            $toppingPrice = 0;
            foreach ($toppingList as $toppingName) {
                $toppingStmt = $this->db->prepare("
                    SELECT price FROM food_toppings
                    WHERE name = :name AND (food_id = :food_id OR food_id IS NULL)
                    ORDER BY food_id IS NULL ASC LIMIT 1
                ");
                $toppingStmt->execute([
                    ':name' => $toppingName,
                    ':food_id' => $item['food_id']
                ]);
                $toppingPrice += (int)($toppingStmt->fetchColumn() ?: 0);
            }
            $item['toppings_list'] = $toppingList;
            $item['item_total'] = ($item['price'] + $toppingPrice) * $item['quantity'];
            $subtotal += $item['item_total'];
        }

        $diskonVoucher = 0;
        $ongkir = count($items) > 0 ? 8000 : 0;
        $biayaLayanan = count($items) > 0 ? 1000 : 0;
        $total = count($items) > 0 ? $subtotal + $ongkir + $biayaLayanan : 0;

        Response::json([
            'items' => $items,
            'item_count' => count($items),
            'summary' => [
                'subtotal' => $subtotal,
                'diskon_voucher' => $diskonVoucher,
                'ongkir' => $ongkir,
                'biaya_layanan' => $biayaLayanan,
                'total' => $total
            ]
        ], 200, 'Berhasil memuat keranjang belanja');
    }

    public function addToCart(): void {
        $userId = $this->getUserId();
        $data = Response::getJsonInput();

        $foodId = (int)($data['food_id'] ?? 0);
        $quantity = max(1, (int)($data['quantity'] ?? 1));
        $spicyLevel = trim($data['spicy_level'] ?? 'Sedang 🌶️');
        $notes = trim($data['notes'] ?? '');
        $toppings = isset($data['toppings']) ? (is_array($data['toppings']) ? json_encode($data['toppings']) : $data['toppings']) : '[]';

        if ($foodId <= 0) {
            Response::error('Food ID tidak valid', 422);
        }

        // Cek makanan ada
        $checkStmt = $this->db->prepare("SELECT id FROM foods WHERE id = :id AND is_available = 1 LIMIT 1");
        $checkStmt->execute([':id' => $foodId]);
        if (!$checkStmt->fetch()) {
            Response::error('Menu makanan tidak tersedia', 404);
        }

        // Cek apakah item serupa sudah ada di cart
        $existStmt = $this->db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = :uid AND food_id = :fid AND spicy_level = :spicy AND toppings = :toppings LIMIT 1");
        $existStmt->execute([
            ':uid' => $userId,
            ':fid' => $foodId,
            ':spicy' => $spicyLevel,
            ':toppings' => $toppings
        ]);
        $existing = $existStmt->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            $upStmt = $this->db->prepare("UPDATE cart_items SET quantity = :qty, notes = :notes, toppings = :top WHERE id = :id");
            $upStmt->execute([
                ':qty' => $newQty,
                ':notes' => $notes,
                ':top' => $toppings,
                ':id' => $existing['id']
            ]);
        } else {
            $insStmt = $this->db->prepare("
                INSERT INTO cart_items (user_id, food_id, quantity, spicy_level, toppings, notes)
                VALUES (:uid, :fid, :qty, :spicy, :top, :notes)
            ");
            $insStmt->execute([
                ':uid' => $userId,
                ':fid' => $foodId,
                ':qty' => $quantity,
                ':spicy' => $spicyLevel,
                ':top' => $toppings,
                ':notes' => $notes
            ]);
        }

        $this->getCart();
    }

    public function updateCartItem(int $id): void {
        $userId = $this->getUserId();
        $data = Response::getJsonInput();

        $stmt = $this->db->prepare("SELECT * FROM cart_items WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item keranjang tidak ditemukan', 404);
        }

        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : (int)$item['quantity'];

        if ($quantity <= 0) {
            // Jika quantity 0, hapus dari keranjang
            $this->removeItem($id);
            return;
        }

        $notes = array_key_exists('notes', $data) ? trim($data['notes']) : $item['notes'];
        $spicyLevel = array_key_exists('spicy_level', $data) ? trim($data['spicy_level']) : $item['spicy_level'];

        $upStmt = $this->db->prepare("
            UPDATE cart_items SET quantity = :qty, notes = :notes, spicy_level = :spicy WHERE id = :id
        ");
        $upStmt->execute([
            ':qty' => $quantity,
            ':notes' => $notes,
            ':spicy' => $spicyLevel,
            ':id' => $id
        ]);

        $this->getCart();
    }

    public function removeItem(int $id): void {
        $userId = $this->getUserId();
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        Response::json(['deleted_id' => $id], 200, 'Item keranjang berhasil dihapus');
    }

    public function clearCart(): void {
        $userId = $this->getUserId();
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);

        Response::json([], 200, 'Keranjang berhasil dikosongkan');
    }
}
