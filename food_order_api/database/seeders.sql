-- database/seeders.sql
-- Data Awal (Dummy & Master Data) Sinkron dengan Tampilan Frontend FoodieGo

USE `food_order_db`;

-- 1. Seed Users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `avatar_url`, `membership_tier`, `points`, `vouchers_count`) VALUES
(1, 'Ahmad Fauzan', 'ahmad.fauzan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80', 'GOLD', 1250, 4)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `icon_name`, `color_hex`, `display_order`) VALUES
(1, 'Semua', 'semua', 'restaurant_menu', '#FF5722', 1),
(2, 'Nasi', 'nasi', 'rice_bowl', '#F59E0B', 2),
(3, 'Mie', 'mie', 'ramen_dining', '#10B981', 3),
(4, 'Ayam', 'ayam', 'egg', '#E11D48', 4),
(5, 'Daging', 'daging', 'kebab_dining', '#8B5CF6', 5),
(6, 'Pizza', 'pizza', 'local_pizza', '#EC4899', 6),
(7, 'Minuman', 'minuman', 'local_cafe', '#06B6D4', 7)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Seed Restaurants
INSERT INTO `restaurants` (`id`, `name`, `tagline`, `image_url`, `rating`, `reviews_count`, `distance`, `delivery_time`, `address`, `phone`, `is_open`) VALUES
(1, 'Warung Geprek Bu Rini', 'Ayam Geprek Sambal Pedas Mantap Gurih', 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&auto=format&fit=crop&q=80', 4.8, 428, '1.2 km', '15-20 min', 'Jl. Ketintang Madya No. 44, Surabaya', '08123456001', 1),
(2, 'Nasi Goreng Pak Budi', 'Nasi Goreng Rempah Legendaris Sejak 1998', 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&auto=format&fit=crop&q=80', 4.7, 310, '1.8 km', '20-25 min', 'Jl. Raya Darmo Permai No. 12, Surabaya', '08123456002', 1),
(3, 'Mie Ayam Pak Karto', 'Mie Ayam Rumahan Kenyal & Bakso Sapi Asli', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=600&auto=format&fit=crop&q=80', 4.9, 560, '0.8 km', '10-15 min', 'Jl. Gayung Kebonsari No. 9, Surabaya', '08123456003', 1),
(4, 'Sate Madura Pak Slamet', 'Sate Daging Pilihan Arang Batok Kelapa', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&auto=format&fit=crop&q=80', 4.9, 490, '2.3 km', '25-30 min', 'Jl. Ahmad Yani No. 102, Surabaya', '08123456004', 1),
(5, 'Milano Pizza Artisan', 'Pizza Oven Kayu Tradisional Gaya Italia', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80', 4.8, 220, '3.1 km', '30-35 min', 'Jl. Mayjen Sungkono No. 78, Surabaya', '08123456005', 1),
(6, 'ChaTime Bubble House', 'Boba Chewy & Minuman Teh Susu Segar', 'https://images.unsplash.com/photo-1558857563-b37cf0e27150?w=600&auto=format&fit=crop&q=80', 4.7, 380, '1.0 km', '10-15 min', 'Jl. Ketintang Wiyata No. 5, Surabaya', '08123456006', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 4. Seed Foods (Sinkron dengan dummyFoods di Flutter)
INSERT INTO `foods` (`id`, `restaurant_id`, `category_id`, `name`, `restaurant_name`, `image_url`, `price`, `original_price`, `rating`, `description`, `category`, `delivery_time`, `distance`, `reviews_count`, `discount_badge`, `is_popular`, `is_available`) VALUES
(1, 1, 4, 'Ayam Geprek Sambal Bawang', 'Warung Geprek Bu Rini', 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&auto=format&fit=crop&q=80', 22000, 28000, 4.8, 'Ayam goreng renyah bumbu kuning khas, digeprek bersama sambal bawang pedas nendang, disajikan dengan nasi pulen hangat dan lalapan segar.', 'Ayam', '15-20 min', '1.2 km', 428, 'Diskon 20%', 1, 1),
(2, 2, 2, 'Nasi Goreng Spesial Telur Ceplok', 'Nasi Goreng Pak Budi', 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&auto=format&fit=crop&q=80', 20000, 25000, 4.7, 'Nasi goreng harum berpadu bumbu rempah tradisional, dilengkapi suwiran daging ayam empuk, telur mata sapi setengah matang, dan acar timur.', 'Nasi', '20-25 min', '1.8 km', 310, 'Diskon 20%', 1, 1),
(3, 3, 3, 'Mie Ayam Jamur Bakso Sapi', 'Mie Ayam Pak Karto', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=600&auto=format&fit=crop&q=80', 18000, NULL, 4.9, 'Mie kenyal racikan rumahan disiram kuah kaldu sapi gurih, disajikan dengan topping ayam jamur manis gurih, 2 butir bakso halus, dan pangsit renyah.', 'Mie', '10-15 min', '0.8 km', 560, 'Terlaris', 1, 1),
(4, 4, 5, 'Sate Ayam Madura Saus Kacang', 'Sate Madura Pak Slamet', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&auto=format&fit=crop&q=80', 25000, 32000, 4.9, '10 tusuk sate daging ayam pilihan dibakar arang kelapa dengan aroma sedap, disiram bumbu kacang lembut gurih kental dan potongan lontong lembut.', 'Daging', '25-30 min', '2.3 km', 490, 'Diskon 22%', 1, 1),
(5, 5, 6, 'Pizza Pepperoni Supreme', 'Milano Pizza Artisan', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&auto=format&fit=crop&q=80', 48000, 65000, 4.8, 'Adonan tipis berkerak renyah dipanggang oven kayu, topping saus tomat Italia, lelehan keju mozzarella melimpah dan irisan beef pepperoni gurih.', 'Pizza', '30-35 min', '3.1 km', 220, 'Diskon 25%', 0, 1),
(6, 6, 7, 'Boba Brown Sugar Fresh Milk', 'ChaTime Bubble House', 'https://images.unsplash.com/photo-1558857563-b37cf0e27150?w=600&auto=format&fit=crop&q=80', 16000, 20000, 4.7, 'Susu segar dingin berpadu sirup gula aren karamel premium dengan tekstur boba tapioka kenyal lembut yang dimasak setiap jam.', 'Minuman', '10-15 min', '1.0 km', 380, 'Promo', 0, 1),
(7, 1, 4, 'Ayam Bakar Madu Spesial', 'Warung Geprek Bu Rini', 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=600&auto=format&fit=crop&q=80', 24000, 30000, 4.8, 'Ayam bakar dengan lumuran madu asli dan bumbu rempah bakar meresap sampai ke tulang.', 'Ayam', '15-20 min', '1.2 km', 185, 'Diskon 20%', 0, 1),
(8, 3, 3, 'Mie Pangsit Goreng Krispi', 'Mie Ayam Pak Karto', 'https://images.unsplash.com/photo-1612927601601-6638404737ce?w=600&auto=format&fit=crop&q=80', 16000, NULL, 4.6, 'Mie keriting kenyal dengan taburan ayam cincang gurih, kuah terpisah, dan pangsit goreng ekstra renyah.', 'Mie', '10-15 min', '0.8 km', 142, NULL, 0, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 5. Seed Food Toppings (Sinkron dengan food_detail_screen)
INSERT INTO `food_toppings` (`id`, `food_id`, `name`, `price`) VALUES
(1, NULL, 'Telur Mata Sapi', 4000),
(2, NULL, 'Keju Mozzarella', 6000),
(3, NULL, 'Sambal Ekstra', 3000),
(4, NULL, 'Kerupuk Udang Renyah', 2500)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 6. Seed Addresses (Sinkron dengan cart_screen & checkout_screen)
INSERT INTO `addresses` (`id`, `user_id`, `label`, `recipient_name`, `phone`, `full_address`, `notes`, `is_default`) VALUES
(1, 1, 'Rumah', 'Ahmad Fauzan (Rumah)', '081234567890', 'Jl. Sudirman No. 45, RT 02 / RW 05, Menteng, Jakarta Pusat, 10310', 'Catatan: Rumah pagar hitam samping minimarket', 1),
(2, 1, 'Kampus / Kost', 'Ahmad Fauzan (Kampus)', '081234567890', 'Jl. Ketintang No. 15, Gayungan, Surabaya, Jawa Timur 60231', 'Catatan: Titipkan di satpam gerbang utama', 0)
ON DUPLICATE KEY UPDATE `recipient_name`=VALUES(`recipient_name`);

-- 7. Seed Promo Banners (Sinkron dengan PromoBanner widget)
INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `promo_code`, `action_url`, `is_active`) VALUES
(1, 'Diskon Kilat 50%', 'Khusus Pengguna Baru Hari Ini!', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&auto=format&fit=crop&q=80', 'MAKANHEMAT', '/promo/kilat', 1),
(2, 'Gratis Ongkir Se-Kota', 'Min. Belanja Rp 30.000 Tanpa Syarat Ribet', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800&auto=format&fit=crop&q=80', 'FREEONGKIR', '/promo/ongkir', 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 8. Seed Vouchers
INSERT INTO `vouchers` (`id`, `code`, `title`, `discount_amount`, `min_order`, `is_active`) VALUES
(1, 'HEMAT10K', 'Voucher Potongan Diskon Rp 10.000', 10000, 30000, 1),
(2, 'DISKON20', 'Diskon Spesial 20% Pelajar', 8000, 25000, 1),
(3, 'ONGKIRFREE', 'Potongan Ongkos Kirim Rp 8.000', 8000, 20000, 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 9. Seed Cart Items (Sample item di keranjang seperti di cart_screen)
INSERT INTO `cart_items` (`id`, `user_id`, `food_id`, `quantity`, `spicy_level`, `toppings`, `notes`) VALUES
(1, 1, 1, 1, 'Sedang 🌶️', '["Telur Mata Sapi"]', 'Sambal dipisah ya mas'),
(2, 1, 2, 1, 'Original', '[]', 'Jangan terlalu manis')
ON DUPLICATE KEY UPDATE `quantity`=VALUES(`quantity`);

-- 10. Seed Orders (Sample order seperti di order_success_screen)
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `restaurant_id`, `address_id`, `delivery_address_text`, `delivery_service`, `subtotal`, `discount_voucher`, `delivery_fee`, `service_fee`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `notes`, `estimated_delivery_time`) VALUES
(1, 'ORD-20261007-001', 1, 1, 1, 'Jl. Sudirman No. 45, RT 02 / RW 05, Menteng, Jakarta Pusat, 10310', 'Kurir Express Instan', 42000, 10000, 8000, 1000, 41000, 'GoPay', 'PAID', 'COOKING', 'Pagar hitam samping minimarket', '15-20 min')
ON DUPLICATE KEY UPDATE `order_number`=VALUES(`order_number`);

-- 11. Seed Order Items
INSERT INTO `order_items` (`id`, `order_id`, `food_id`, `food_name`, `food_price`, `quantity`, `spicy_level`, `toppings`, `subtotal_item`) VALUES
(1, 1, 1, 'Ayam Geprek Sambal Bawang', 22000, 1, 'Sedang 🌶️', '["Telur Mata Sapi"]', 22000),
(2, 1, 2, 'Nasi Goreng Spesial Telur Ceplok', 20000, 1, 'Original', '[]', 20000)
ON DUPLICATE KEY UPDATE `food_name`=VALUES(`food_name`);

-- 12. Seed Order Status History (Sinkron dengan Live Tracking di order_success_screen)
INSERT INTO `order_status_history` (`id`, `order_id`, `step`, `title`, `description`, `is_completed`, `is_active`, `timestamp_text`) VALUES
(1, 1, 1, 'Pesanan Dikonfirmasi', 'Restoran telah menerima pesananmu dan sedang menyiapkan bahan.', 1, 0, '12:05'),
(2, 1, 2, 'Sedang Dimasak di Dapur', 'Koki sedang memasak pesanan makananmu dengan higienis.', 1, 1, '12:12'),
(3, 1, 3, 'Driver Menuju Restoran', 'Driver Mitra FoodieGo sedang menuju lokasi resto untuk mengambil makanan.', 0, 0, '12:18'),
(4, 1, 4, 'Makanan Tiba di Tujuan', 'Kurir tiba dan pesanan siap kamu santap hangat-hangat.', 0, 0, '12:30')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);
