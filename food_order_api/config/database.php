<?php
// config/database.php

class Database {
    private static ?PDO $instance = null;

    private static string $host = '127.0.0.1';
    private static string $port = '3306';
    private static string $dbName = 'food_order_db';
    private static string $username = 'root';
    private static string $password = '';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbName . ";charset=utf8mb4";
            
            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal terkoneksi ke Database MySQL. Pastikan Laragon/MySQL sudah aktif (Start All).',
                    'error' => $e->getMessage()
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        return self::$instance;
    }
}
