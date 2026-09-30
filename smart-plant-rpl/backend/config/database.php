<?php
/**
 * Konfigurasi Database MySQL menggunakan PHP PDO Native
 * Smart Plant Dashboard - University RPL IoT Project
 */

date_default_timezone_set('Asia/Jakarta');

class Database {
    private string $host = "127.0.0.1";
    private string $db_name = "smart_plant_db";
    private string $username = "root";
    private string $password = "";
    private string $charset = "utf8mb4";
    public ?PDO $conn = null;

    /**
     * Membuat dan mengembalikan koneksi PDO
     * @return PDO|null
     */
    public function getConnection(): ?PDO {
        $this->conn = null;

        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            http_response_code(500);
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode([
                "status" => "error",
                "message" => "Gagal terhubung ke database: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        return $this->conn;
    }
}
