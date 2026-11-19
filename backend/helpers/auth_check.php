<?php
/**
 * Helper / Middleware Autentikasi & Session Guard
 * Digunakan oleh Web Dashboard untuk memvalidasi sesi login pengguna
 * Smart Plant Dashboard - University RPL IoT Project
 */

// Pastikan zona waktu sudah diatur
date_default_timezone_set('Asia/Jakarta');

/**
 * Mengembalikan daftar origin yang diizinkan untuk CORS.
 * Dapat dikonfigurasi melalui environment variable ALLOWED_ORIGINS (dipisah koma).
 *
 * @return array<int, string>
 */
function getAllowedCorsOrigins(): array {
    $envOrigins = getenv('ALLOWED_ORIGINS');
    if (is_string($envOrigins) && trim($envOrigins) !== '') {
        $origins = array_filter(array_map(
            static fn(string $item): string => rtrim(trim($item), '/'),
            explode(',', $envOrigins)
        ));
        return array_values(array_unique($origins));
    }

    $allowed = [
        'http://localhost',
        'https://localhost',
        'http://127.0.0.1',
        'https://127.0.0.1',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://localhost:8080',
        'http://127.0.0.1:8080',
    ];

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '') {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        $scheme = $isHttps ? 'https' : 'http';
        $allowed[] = "{$scheme}://{$host}";
    }

    return array_values(array_unique($allowed));
}

/**
 * Memeriksa apakah Origin termasuk dalam whitelist CORS yang diizinkan.
 */
function isOriginAllowed(string $origin): bool {
    $normalized = rtrim(trim($origin), '/');
    if ($normalized === '' || $normalized === 'null') {
        return false;
    }

    return in_array($normalized, getAllowedCorsOrigins(), true);
}

/**
 * Mengirimkan header CORS yang aman berdasarkan whitelist origin
 */
function sendCorsHeaders(): void {
    header("Content-Type: application/json; charset=UTF-8");
    header("Vary: Origin");

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && isOriginAllowed($origin)) {
        $safeOrigin = rtrim(trim($origin), '/');
        header("Access-Control-Allow-Origin: {$safeOrigin}");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    }
}

/**
 * Tangani CORS preflight request OPTIONS
 */
function handleCorsPreflight(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        sendCorsHeaders();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && !isOriginAllowed($origin)) {
            http_response_code(403);
            echo json_encode([
                "status"  => "error",
                "message" => "Origin tidak diizinkan oleh kebijakan CORS."
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
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
