<?php
/**
 * Konfigurasi Database MySQL menggunakan PHP PDO Native
 * Mendukung environment variables dan file .env di root proyek.
 * Smart Plant Dashboard - University RPL IoT Project
 */

date_default_timezone_set('Asia/Jakarta');

/**
 * Memuat pasangan KEY=VALUE dari file .env jika ada tanpa menimpa env server yang sudah diset.
 */
function loadDotEnv(string $envPath): void {
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $eqPos = strpos($trimmed, '=');
        if ($eqPos === false) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $eqPos));
        $val = trim(substr($trimmed, $eqPos + 1));

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }

        if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
        }
    }
}

class Database {
    private string $host;
    private string $port;
    private string $db_name;
    private string $username;
    private string $password;
    private string $charset;
    public ?PDO $conn = null;

    public function __construct() {
        loadDotEnv(__DIR__ . '/../../.env');

        $this->host     = $this->env('DB_HOST', '127.0.0.1');
        $this->port     = $this->env('DB_PORT', '3306');
        $this->db_name  = $this->env('DB_NAME', 'smart_plant_db');
        $this->username = $this->env('DB_USER', 'root');
        $this->password = $this->env('DB_PASS', '');
        $this->charset  = $this->env('DB_CHARSET', 'utf8mb4');
    }

    private function env(string $key, string $default): string {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return (string)$_ENV[$key];
        }
        return $default;
    }

    /**
     * Membuat dan mengembalikan koneksi PDO
     * @return PDO|null
     */
    public function getConnection(): ?PDO {
        $this->conn = null;

        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            error_log('[Database Connection Error] ' . $e->getMessage());
            http_response_code(500);
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode([
                "status" => "error",
                "message" => "Layanan database sedang tidak tersedia. Silakan coba lagi nanti."
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        return $this->conn;
    }
}
