# 🍕 FoodieGo REST API & Database (Tugas Pak Bonda - Kelas F)

Dokumentasi resmi Backend API dan Database mandiri untuk aplikasi pemesanan makanan **FoodieGo (Food Order App)**.
API ini dibuat secara terpisah dari frontend, tanpa menggunakan Firebase atau Supabase, menggunakan **MySQL (Laragon)** dan **PHP REST API**.

---

## 🌟 Ringkasan Arsitektur & Teknologi

| Komponen | Spesifikasi | Keterangan |
| :--- | :--- | :--- |
| **Frontend** | Flutter (Dart) | Aplikasi Mobile FoodieGo (`food_order_app`) |
| **Backend API** | PHP Native RESTful API (PDO) | Arsitektur modular (MVC: Controllers, Routes, Helpers) |
| **Database** | MySQL 8.x | Database `food_order_db` di Laragon |
| **CORS Support** | Enabled (`*`) | Mendukung Android Emulator, Browser, dan Real Device |
| **Tools Pengujian** | Web Dashboard, Postman, cURL | Tersedia Web UI interaktif dan file `.postman_collection.json` |

---

## 🚀 Cara Menjalankan API & Database

Tersedia **2 Cara Mudah** untuk menjalankan API:

### Cara 1: Menggunakan Laragon (Sangat Direkomendasikan untuk Kuliah)
1. Buka aplikasi **Laragon**.
2. Klik tombol **"Start All"** (Apache dan MySQL akan berjalan).
3. Folder API sudah disinkronkan otomatis ke:
   ```
   C:\laragon\www\food_order_api
   ```
4. Buka browser dan akses salah satu URL berikut:
   - Dashboard Interaktif: `http://localhost/food_order_api/test_dashboard.html`
   - Base URL Endpoint: `http://localhost/food_order_api/api`
   - Virtual Host (jika aktif): `http://food-order-api.test/api`

### Cara 2: 1-Klik Jalankan Tanpa Laragon Apache (`run_api.bat`)
1. Buka folder `food_order_api`.
2. Klik ganda (double-click) file **`run_api.bat`**.
3. Server lokal langsung menyala di:
   - Dashboard Interaktif: `http://localhost:8000/test_dashboard.html`
   - Base URL Endpoint: `http://localhost:8000/api`

---

## 🗄️ Struktur Database (`food_order_db`)

Database terdiri dari 12 tabel terelasi yang disinkronkan 100% dengan tampilan frontend:

1. **`users`** : Data pengguna (`Ahmad Fauzan`, email, password, nomor telepon, avatar, membership `GOLD`, loyalty points).
2. **`categories`** : Kategori pilihan (`Semua`, `Nasi`, `Mie`, `Ayam`, `Daging`, `Pizza`, `Minuman`) lengkap dengan warna dan icon.
3. **`restaurants`** : Restoran mitra (`Warung Geprek Bu Rini`, `Nasi Goreng Pak Budi`, `Mie Ayam Pak Karto`, dll).
4. **`foods`** : Menu makanan dengan harga normal, harga diskon, rating, deskripsi rasa, jarak pengantaran, badge diskon.
5. **`food_toppings`** : Opsi topping tambahan menu (Telur Mata Sapi, Keju Mozzarella, Sambal Ekstra).
6. **`addresses`** : Alamat tersimpan pengguna (Rumah, Kantor, Kampus Ketintang) beserta catatan kurir.
7. **`banners`** : Banner carousel promo di beranda.
8. **`vouchers`** : Voucher diskon (Potongan Rp 10.000, Diskon 20%, Gratis Ongkir).
9. **`cart_items`** : Keranjang belanja pengguna (food_id, quantity, spicy_level, toppings, notes).
10. **`orders`** : Data transaksi pesanan, status pesanan, alamat antar, kurir pengantaran, total biaya.
11. **`order_items`** : Rincian item yang dibeli dalam satu invoice pesanan.
12. **`order_status_history`** : Status live tracking timeline 4 langkah (Dikonfirmasi -> Dimasak -> Driver Menuju Resto -> Selesai).

> File SQL migrasi dan data awal berada di folder `database/schema.sql` dan `database/seeders.sql`. Script otomatisasi migrasi ada di `database/setup_db.php`.

---

## 📋 Daftar Lengkap Endpoint API

Semua endpoint mengembalikan respon JSON standar:
```json
{
  "success": true,
  "message": "Pesan status",
  "data": { ... }
}
```

### 1. Modul Foods (Menu Makanan & Minuman)
*Sesuai tampilan `HomeScreen` dan `FoodDetailScreen`*

| Method | Endpoint | Keterangan & Parameter |
| :--- | :--- | :--- |
| `GET` | `/api/foods` | Mengambil seluruh menu makanan. Mendukung filter: `?category=Mie`, `?is_popular=1`, `?search=Ayam`, `?sort=price_asc` |
| `GET` | `/api/foods/{id}` | Mengambil detail menu makanan beserta daftar topping dan opsi level pedas. |
| `POST` | `/api/foods` | Menambahkan menu makanan baru ke dalam katalog. |
| `PUT` | `/api/foods/{id}` | Mengubah data menu makanan (harga, deskripsi, diskon, ketersediaan). |
| `DELETE` | `/api/foods/{id}` | Menghapus menu makanan dari katalog. |

### 2. Modul Categories (Kategori Makanan)
*Sesuai tab kategori horizontal di `HomeScreen`*

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api/categories` | Mengambil semua kategori makanan. |
| `GET` | `/api/categories/{id}` | Mengambil data 1 kategori makanan. |
| `POST` | `/api/categories` | Menambahkan kategori baru. |
| `PUT` | `/api/categories/{id}` | Mengubah kategori makanan. |
| `DELETE` | `/api/categories/{id}` | Menghapus kategori makanan. |

### 3. Modul Restaurants (Restoran Mitra)
*Sesuai tampilan `RestaurantDetailScreen` & Rekomendasi di `HomeScreen`*

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api/restaurants` | Mengambil daftar restoran rekomendasi terdekat dengan rating dan jarak. |
| `GET` | `/api/restaurants/{id}` | Mengambil detail profil restoran beserta seluruh menu makanan yang dijualnya. |
| `POST` | `/api/restaurants` | Menambahkan restoran mitra baru. |
| `PUT` | `/api/restaurants/{id}` | Mengubah profil restoran. |
| `DELETE` | `/api/restaurants/{id}` | Menghapus data restoran. |

### 4. Modul Cart (Keranjang Belanja)
*Sesuai tampilan `CartScreen`*

| Method | Endpoint | Keterangan & Body |
| :--- | :--- | :--- |
| `GET` | `/api/cart` | Mengambil isi keranjang belanja dan perhitungan otomatis subtotal, diskon voucher, ongkir, biaya layanan, dan total. |
| `POST` | `/api/cart/add` | Menambahkan menu ke keranjang. Body: `{"food_id": 1, "quantity": 2, "spicy_level": "Pedas", "toppings": ["Telur Mata Sapi"], "notes": "..."}` |
| `PUT` | `/api/cart/update/{id}` | Mengubah quantity atau catatan item di keranjang. Body: `{"quantity": 3}` |
| `DELETE` | `/api/cart/remove/{id}` | Menghapus satu item dari keranjang. |
| `DELETE` | `/api/cart/clear` | Mengosongkan seluruh keranjang belanja. |

### 5. Modul Orders & Live Tracking
*Sesuai tampilan `CheckoutScreen` dan `OrderSuccessScreen`*

| Method | Endpoint | Keterangan & Body |
| :--- | :--- | :--- |
| `POST` | `/api/orders/checkout` | Membuat pesanan baru dari isi keranjang. Body: `{"address_id": 1, "delivery_service": "Kurir Express Instan", "payment_method": "GoPay", "voucher_code": "HEMAT10K"}` |
| `GET` | `/api/orders` | Mengambil riwayat transaksi pesanan pengguna. |
| `GET` | `/api/orders/{id}` | Mengambil detail invoice pesanan, rincian makanan, dan live status tracking timeline 4 langkah. |
| `PUT` | `/api/orders/{id}/status` | Mengupdate status pesanan (`CONFIRMED`, `COOKING`, `ON_DELIVERY`, `COMPLETED`). |

### 6. Modul Auth & Profile
*Sesuai tampilan `LoginScreen` dan `ProfileScreen`*

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `POST` | `/api/auth/login` | Login pengguna. Body: `{"email": "ahmad.fauzan@email.com", "password": "password123"}` |
| `POST` | `/api/auth/register` | Mendaftarkan akun pengguna baru. |
| `GET` | `/api/profile` | Mengambil data profil pengguna (`Ahmad Fauzan`, poin, tier GOLD, voucher). |
| `PUT` | `/api/profile` | Memperbarui nama, no. telp, atau foto profil. |

### 7. Modul Addresses & Promos
*Sesuai tampilan alamat di checkout dan carousel banner promo*

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api/addresses` | Mengambil daftar alamat tujuan pengantaran tersimpan. |
| `POST` | `/api/addresses` | Menambahkan alamat baru. |
| `PUT` | `/api/addresses/{id}/default` | Menjadikan alamat sebagai alamat utama. |
| `GET` | `/api/banners` | Mengambil banner promo beranda (`PromoBanner`). |
| `GET` | `/api/vouchers` | Mengambil voucher diskon checkout. |

---

## 🧪 Cara Pengujian Endpoint

### 1. Menggunakan Test Dashboard Bawaan (Paling Mudah)
Buka di browser:
👉 **`http://localhost:8000/test_dashboard.html`** (atau `http://localhost/food_order_api/test_dashboard.html`)
- Terdapat tombol uji langsung (1-klik Kirim Request).
- Parameter dan request body JSON dapat disesuaikan langsung di web.
- Tampilan status HTTP, waktu respon (ms), dan syntax highlighter JSON.

### 2. Menggunakan Postman
1. Buka aplikasi **Postman**.
2. Klik menu **Import**.
3. Pilih file **`food_order_api.postman_collection.json`** yang ada di folder ini.
4. Semua 8 grup endpoint langsung siap di-test lengkap dengan contoh payload.

---

## 📱 Panduan Praktek Minggu Depan: Menghubungkan Frontend Flutter

Untuk persiapan sesi kuliah minggu depan:
1. File client service sudah disiapkan di frontend pada:
   ```
   lib/services/api_service.dart
   ```
2. Model data di `lib/models/food_item.dart` sudah dilengkapi method `FoodItem.fromJson()` dan `toJson()`.
3. Tambahkan dependency `http` pada `pubspec.yaml`:
   ```yaml
   dependencies:
     flutter:
       sdk: flutter
     cupertino_icons: ^1.0.6
     http: ^1.2.0
   ```
4. Pilihan Base URL saat praktek di emulator atau HP:
   - **Android Emulator**: `http://10.0.2.2:8000/api` (atau `http://10.0.2.2/food_order_api/api`)
   - **Web / Desktop**: `http://localhost:8000/api`
   - **HP Fisik**: `http://192.168.x.x:8000/api` (pastikan satu jaringan Wi-Fi)
