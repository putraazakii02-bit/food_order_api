<?php
// controllers/OrderController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Auth.php';

class OrderController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function getUserId(): int {
        return Auth::userId() ?? 1;
    }

    public function getAll(): void {
        $userId = $this->getUserId();

        $stmt = $this->db->prepare("
            SELECT o.*, r.name AS restaurant_name, r.image_url AS restaurant_image
            FROM orders o
            JOIN restaurants r ON o.restaurant_id = r.id
            WHERE o.user_id = :uid
            ORDER BY o.id DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$o) {
            $o['id'] = (int)$o['id'];
            $o['subtotal'] = (int)$o['subtotal'];
            $o['discount_voucher'] = (int)$o['discount_voucher'];
            $o['delivery_fee'] = (int)$o['delivery_fee'];
            $o['service_fee'] = (int)$o['service_fee'];
            $o['total_amount'] = (int)$o['total_amount'];

            // Get count items
            $itemCountStmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE order_id = :oid");
            $itemCountStmt->execute([':oid' => $o['id']]);
            $o['items_count'] = (int)$itemCountStmt->fetch()['cnt'];
        }

        Response::json($orders, 200, 'Berhasil memuat riwayat pesanan');
    }

    public function getById(int $id): void {
        $stmt = $this->db->prepare("
            SELECT o.*, r.name AS restaurant_name, r.image_url AS restaurant_image, r.address AS restaurant_address
            FROM orders o
            JOIN restaurants r ON o.restaurant_id = r.id
            WHERE o.id = :id AND o.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $this->getUserId()]);
        $order = $stmt->fetch();

        if (!$order) {
            Response::error('Pesanan tidak ditemukan', 404);
        }

        $order['id'] = (int)$order['id'];
        $order['subtotal'] = (int)$order['subtotal'];
        $order['discount_voucher'] = (int)$order['discount_voucher'];
        $order['delivery_fee'] = (int)$order['delivery_fee'];
        $order['service_fee'] = (int)$order['service_fee'];
        $order['total_amount'] = (int)$order['total_amount'];

        // Get items
        $itemsStmt = $this->db->prepare("
            SELECT oi.*, f.image_url
            FROM order_items oi
            LEFT JOIN foods f ON oi.food_id = f.id
            WHERE oi.order_id = :oid
        ");
        $itemsStmt->execute([':oid' => $id]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as &$it) {
            $it['id'] = (int)$it['id'];
            $it['food_price'] = (int)$it['food_price'];
            $it['quantity'] = (int)$it['quantity'];
            $it['subtotal_item'] = (int)$it['subtotal_item'];
            $it['toppings_list'] = json_decode($it['toppings'] ?? '[]', true) ?: [];
        }
        $order['items'] = $items;

        // Get tracking steps history (Sinkron dengan timeline di OrderSuccessScreen)
        $trackStmt = $this->db->prepare("
            SELECT * FROM order_status_history WHERE order_id = :oid ORDER BY step ASC
        ");
        $trackStmt->execute([':oid' => $id]);
        $tracking = $trackStmt->fetchAll();

        foreach ($tracking as &$tr) {
            $tr['step'] = (int)$tr['step'];
            $tr['is_completed'] = (bool)$tr['is_completed'];
            $tr['is_active'] = (bool)$tr['is_active'];
        }
        $order['tracking_steps'] = $tracking;

        Response::json($order, 200, 'Berhasil memuat detail pesanan');
    }

    public function checkout(): void {
        $userId = $this->getUserId();
        $data = Response::getJsonInput();

        // 1. Ambil item dari cart atau langsung dari body
        $cartStmt = $this->db->prepare("
            SELECT c.*, f.name AS food_name, f.price, f.restaurant_id
            FROM cart_items c
            JOIN foods f ON c.food_id = f.id
            WHERE c.user_id = :uid
        ");
        $cartStmt->execute([':uid' => $userId]);
        $cartItems = $cartStmt->fetchAll();

        if (empty($cartItems)) {
            Response::error('Keranjang belanja kosong. Silakan pilih makanan terlebih dahulu.', 400);
        }

        // Ambil info alamat
        $addressId = (int)($data['address_id'] ?? 1);
        $addrStmt = $this->db->prepare("SELECT * FROM addresses WHERE id = :id LIMIT 1");
        $addrStmt->execute([':id' => $addressId]);
        $address = $addrStmt->fetch();
        $addressText = $address ? ($address['recipient_name'] . ' - ' . $address['full_address']) : 'Jl. Ketintang No. 15, Surabaya';

        $deliveryService = trim($data['delivery_service'] ?? 'Kurir Express Instan');
        $paymentMethod = trim($data['payment_method'] ?? 'GoPay');
        $voucherCode = strtoupper(trim($data['voucher_code'] ?? ''));
        $notes = trim($data['notes'] ?? 'Tolong cepat sampai ya');

        // Hitung biaya
        $subtotal = 0;
        $restaurantId = $cartItems[0]['restaurant_id'];

        foreach ($cartItems as &$cartItem) {
            $toppingTotal = 0;
            $toppings = json_decode($cartItem['toppings'] ?? '[]', true) ?: [];
            foreach ($toppings as $toppingName) {
                $toppingStmt = $this->db->prepare("
                    SELECT price FROM food_toppings
                    WHERE name = :name AND (food_id = :food_id OR food_id IS NULL)
                    ORDER BY food_id IS NULL ASC LIMIT 1
                ");
                $toppingStmt->execute([
                    ':name' => $toppingName,
                    ':food_id' => $cartItem['food_id']
                ]);
                $toppingTotal += (int)($toppingStmt->fetchColumn() ?: 0);
            }
            $cartItem['_topping_total'] = $toppingTotal;
            $subtotal += (($cartItem['price'] + $toppingTotal) * $cartItem['quantity']);
        }
        unset($cartItem);

        $deliveryFee = ($deliveryService === 'Kurir Express Instan') ? 8000 : 5000;
        $discountVoucher = 0;
        if ($voucherCode !== '') {
            $voucherStmt = $this->db->prepare("
                SELECT discount_amount, min_order FROM vouchers
                WHERE code = :code AND is_active = 1 LIMIT 1
            ");
            $voucherStmt->execute([':code' => $voucherCode]);
            $voucher = $voucherStmt->fetch();
            if (!$voucher) {
                Response::error('Voucher tidak ditemukan atau sudah tidak aktif.', 422);
            }
            if ($subtotal < (int)$voucher['min_order']) {
                Response::error('Subtotal belum memenuhi minimum voucher.', 422);
            }
            $discountVoucher = (int)$voucher['discount_amount'];
            if ($voucherCode === 'ONGKIRFREE') {
                $discountVoucher = min($deliveryFee, $discountVoucher);
                $deliveryFee -= $discountVoucher;
            }
        }
        $serviceFee = 1000;
        $totalAmount = max(0, $subtotal - $discountVoucher + $deliveryFee + $serviceFee);

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $this->db->beginTransaction();

        try {
            // Insert Orders
            $orderStmt = $this->db->prepare("
                INSERT INTO orders (order_number, user_id, restaurant_id, address_id, delivery_address_text, delivery_service, subtotal, discount_voucher, delivery_fee, service_fee, total_amount, payment_method, payment_status, order_status, notes, estimated_delivery_time)
                VALUES (:ord_no, :uid, :resto_id, :addr_id, :addr_txt, :service, :subtotal, :disc, :deliv, :fee, :total, :pay_method, 'PAID', 'CONFIRMED', :notes, '15-20 min')
            ");
            $orderStmt->execute([
                ':ord_no' => $orderNumber,
                ':uid' => $userId,
                ':resto_id' => $restaurantId,
                ':addr_id' => $addressId,
                ':addr_txt' => $addressText,
                ':service' => $deliveryService,
                ':subtotal' => $subtotal,
                ':disc' => $discountVoucher,
                ':deliv' => $deliveryFee,
                ':fee' => $serviceFee,
                ':total' => $totalAmount,
                ':pay_method' => $paymentMethod,
                ':notes' => $notes
            ]);

            $orderId = (int)$this->db->lastInsertId();

            // Insert Order Items
            $itemInsertStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, food_id, food_name, food_price, quantity, spicy_level, toppings, subtotal_item)
                VALUES (:oid, :fid, :fname, :price, :qty, :spicy, :top, :sub)
            ");

            foreach ($cartItems as $c) {
                $itemInsertStmt->execute([
                    ':oid' => $orderId,
                    ':fid' => $c['food_id'],
                    ':fname' => $c['food_name'],
                    ':price' => $c['price'],
                    ':qty' => $c['quantity'],
                    ':spicy' => $c['spicy_level'],
                    ':top' => $c['toppings'],
                    ':sub' => ($c['price'] + $c['_topping_total']) * $c['quantity']
                ]);
            }

            // Insert default tracking history steps (Sinkron dengan UI)
            $trackInsert = $this->db->prepare("
                INSERT INTO order_status_history (order_id, step, title, description, is_completed, is_active, timestamp_text)
                VALUES 
                (:oid1, 1, 'Pesanan Dikonfirmasi', 'Restoran telah menerima pesananmu dan sedang menyiapkan bahan.', 1, 0, :time1),
                (:oid2, 2, 'Sedang Dimasak di Dapur', 'Koki sedang memasak pesanan makananmu dengan higienis.', 1, 1, :time2),
                (:oid3, 3, 'Driver Menuju Restoran', 'Driver Mitra FoodieGo sedang menuju lokasi resto untuk mengambil makanan.', 0, 0, :time3),
                (:oid4, 4, 'Makanan Tiba di Tujuan', 'Kurir tiba dan pesanan siap kamu santap hangat-hangat.', 0, 0, :time4)
            ");
            $now = date('H:i');
            $trackInsert->execute([
                ':oid1' => $orderId,
                ':oid2' => $orderId,
                ':oid3' => $orderId,
                ':oid4' => $orderId,
                ':time1' => $now,
                ':time2' => date('H:i', strtotime('+5 minutes')),
                ':time3' => date('H:i', strtotime('+12 minutes')),
                ':time4' => date('H:i', strtotime('+20 minutes'))
            ]);

            // Kosongkan keranjang setelah order dibuat
            $clearCartStmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = :uid");
            $clearCartStmt->execute([':uid' => $userId]);

            $this->db->commit();

            $this->getById($orderId);
        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('Gagal membuat pesanan: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus(int $id): void {
        $data = Response::getJsonInput();
        $status = strtoupper(trim($data['order_status'] ?? 'COOKING'));

        $stmt = $this->db->prepare("SELECT id FROM orders WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $this->getUserId()]);
        if (!$stmt->fetch()) {
            Response::error('Pesanan tidak ditemukan', 404);
        }

        $upStmt = $this->db->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
        $upStmt->execute([':status' => $status, ':id' => $id]);

        // Sesuaikan timeline step
        if ($status === 'COOKING') {
            $this->db->exec("UPDATE order_status_history SET is_completed = 1, is_active = 0 WHERE order_id = $id AND step = 1");
            $this->db->exec("UPDATE order_status_history SET is_completed = 0, is_active = 1 WHERE order_id = $id AND step = 2");
        } elseif ($status === 'ON_DELIVERY') {
            $this->db->exec("UPDATE order_status_history SET is_completed = 1, is_active = 0 WHERE order_id = $id AND step IN (1, 2)");
            $this->db->exec("UPDATE order_status_history SET is_completed = 0, is_active = 1 WHERE order_id = $id AND step = 3");
        } elseif ($status === 'COMPLETED') {
            $this->db->exec("UPDATE order_status_history SET is_completed = 1, is_active = 0 WHERE order_id = $id");
            $this->db->exec("UPDATE order_status_history SET is_completed = 1, is_active = 1 WHERE order_id = $id AND step = 4");
        }

        $this->getById($id);
    }
}
