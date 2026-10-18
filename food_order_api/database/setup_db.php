<?php
// database/setup_db.php
// Script migrasi dan seeder otomatis untuk food_order_db

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';

echo "=== Memulai Inisialisasi Database food_order_db ===\n";

try {
    // 1. Koneksi tanpa nama database terlebih dahulu untuk membuat DB
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "[1/3] Membuat database jika belum ada...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `food_order_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `food_order_db`;");

    // 2. Eksekusi schema.sql
    echo "[2/3] Mengeksekusi tabel dari schema.sql...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    echo "       -> Tabel berhasil dibuat!\n";

    // 3. Eksekusi seeders.sql
    echo "[3/3] Memasukkan data seeder awal dari seeders.sql...\n";
    $seedersSql = file_get_contents(__DIR__ . '/seeders.sql');
    $pdo->exec($seedersSql);
    echo "       -> Data seeder berhasil dimasukkan!\n";

    echo "\n=== Database Siap & Lengkap Digunakan! ===\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<h2 style='color:green;'>Database Berhasil Diinisialisasi!</h2>";
        echo "<a href='../index.php'>Buka API</a> | <a href='../test_dashboard.html'>Buka Test Dashboard</a>";
    }
} catch (PDOException $e) {
    echo "[ERROR] Gagal inisialisasi database: " . $e->getMessage() . "\n";
    if (php_sapi_name() !== 'cli') {
        echo "<h2 style='color:red;'>Gagal Inisialisasi: " . htmlspecialchars($e->getMessage()) . "</h2>";
    }
    exit(1);
}
