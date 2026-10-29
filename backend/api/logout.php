<?php
/**
 * API Endpoint: POST/GET /backend/api/logout.php
 * Digunakan oleh Web Dashboard untuk menghancurkan sesi login pengguna
 * Smart Plant Dashboard - University RPL IoT Project
 */

require_once __DIR__ . '/../helpers/auth_check.php';

// Atur header CORS & Session Cookie
sendCorsHeaders();
handleCorsPreflight();

// Inisialisasi session jika belum aktif
startAppSession();

// 1. Kosongkan semua data $_SESSION
$_SESSION = [];

// 2. Hapus cookie session jika browser menggunakan cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Hancurkan sesi di server
session_destroy();

// 4. Kembalikan respons sukses
http_response_code(200);
echo json_encode([
    "status"  => "success",
    "message" => "Logout berhasil. Sesi telah diakhiri."
], JSON_UNESCAPED_UNICODE);
