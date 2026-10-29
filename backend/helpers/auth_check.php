<?php
/**
 * Helper / Middleware Autentikasi & Session Guard
 * Digunakan oleh Web Dashboard untuk memvalidasi sesi login pengguna
 * Smart Plant Dashboard - University RPL IoT Project
 */

// Pastikan zona waktu sudah diatur
date_default_timezone_set('Asia/Jakarta');

/**
 * Mengirimkan header CORS yang mendukung Session Cookie / Credentials
 */
function sendCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!empty($origin)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true");
    } else {
        header("Access-Control-Allow-Origin: *");
    }
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
}

/**
 * Tangani CORS preflight request OPTIONS
 */
function handleCorsPreflight(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        sendCorsHeaders();
        http_response_code(200);
        exit();
    }
}

/**
 * Memulai session PHP secara aman dengan konfigurasi cookie terbaik
 */
function startAppSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            // Tentukan apakah koneksi menggunakan HTTPS
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            session_set_cookie_params([
                'lifetime' => 0, // Bertahan hingga browser ditutup
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        @session_start();
    }
}

/**
 * Mengecek apakah pengguna saat ini sudah terautentikasi (login aktif)
 * @return bool
 */
function isLoggedIn(): bool {
    startAppSession();
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !empty($_SESSION['user_id']);
}

/**
 * Mengambil data pengguna yang sedang login
 * @return array|null Array berisi id & username atau null jika belum login
 */
function getAuthUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => (string)($_SESSION['username'] ?? ''),
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

/**
 * Guard / Middleware: Memvalidasi apakah user sudah login.
 * Jika belum login, kirim respons HTTP 401 Unauthorized dan hentikan eksekusi script.
 */
function requireAuth(): void {
    sendCorsHeaders();

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            "status"        => "error",
            "message"       => "Akses ditolak. Sesi tidak ditemukan atau telah berakhir. Silakan login terlebih dahulu.",
            "auth_required" => true
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
