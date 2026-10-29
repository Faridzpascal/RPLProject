<?php
/**
 * API Endpoint: POST /backend/api/login.php
 * Digunakan oleh Web Dashboard untuk proses autentikasi pengguna
 * Smart Plant Dashboard - University RPL IoT Project
 */

require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Atur header CORS & Session Cookie
sendCorsHeaders();
handleCorsPreflight();

// Validasi Metode HTTP (Hanya mengizinkan POST)
if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status"  => "error",
        "message" => "Metode HTTP tidak diizinkan. Gunakan POST."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Ambil input data dari JSON body atau $_POST form data
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

$username = trim((string)($data['username'] ?? $_POST['username'] ?? ''));
$password = (string)($data['password'] ?? $_POST['password'] ?? '');

// 1. Validasi Kelengkapan Input
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Username dan password wajib diisi."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 2. Hubungkan ke Database
$database = new Database();
$db = $database->getConnection();

try {
    // 3. Cari pengguna berdasarkan username
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    // 4. Verifikasi kredensial menggunakan password_verify
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            "status"  => "error",
            "message" => "Username atau password salah."
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 5. Inisialisasi Sesi Pengguna
    startAppSession();
    session_regenerate_id(true); // Cegah session fixation

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_time'] = time();

    // 6. Kembalikan respons sukses
    http_response_code(200);
    echo json_encode([
        "status"  => "success",
        "message" => "Login berhasil. Selamat datang, " . htmlspecialchars($user['username']) . "!",
        "data"    => [
            "id"       => (int)$user['id'],
            "username" => $user['username']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
