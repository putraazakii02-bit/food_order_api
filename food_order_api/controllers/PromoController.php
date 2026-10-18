<?php
// controllers/PromoController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';

class PromoController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getBanners(): void {
        $stmt = $this->db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY id ASC");
        $banners = $stmt->fetchAll();

        foreach ($banners as &$b) {
            $b['id'] = (int)$b['id'];
            $b['is_active'] = (bool)$b['is_active'];
        }

        Response::json($banners, 200, 'Berhasil memuat banner promo');
    }

    public function getVouchers(): void {
        $stmt = $this->db->query("SELECT * FROM vouchers WHERE is_active = 1 ORDER BY discount_amount DESC");
        $vouchers = $stmt->fetchAll();

        foreach ($vouchers as &$v) {
            $v['id'] = (int)$v['id'];
            $v['discount_amount'] = (int)$v['discount_amount'];
            $v['min_order'] = (int)$v['min_order'];
            $v['is_active'] = (bool)$v['is_active'];
        }

        Response::json($vouchers, 200, 'Berhasil memuat voucher promo');
    }

    public function createBanner(): void {
        $data = Response::getJsonInput();

        $title = trim($data['title'] ?? '');
        $subtitle = trim($data['subtitle'] ?? '');
        $imageUrl = trim($data['image_url'] ?? '');
        $promoCode = trim($data['promo_code'] ?? '');

        if (empty($title) || empty($imageUrl)) {
            Response::error('Judul dan gambar banner wajib diisi', 422);
        }

        $stmt = $this->db->prepare("
            INSERT INTO banners (title, subtitle, image_url, promo_code, is_active)
            VALUES (:title, :subtitle, :image_url, :promo_code, 1)
        ");
        $stmt->execute([
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':image_url' => $imageUrl,
            ':promo_code' => $promoCode,
        ]);

        $this->getBanners();
    }
}
