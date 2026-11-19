<?php
/**
 * Helper Autentikasi Perangkat IoT (ESP32) menggunakan X-API-Key / Bearer Token
 * Smart Plant Dashboard - University RPL IoT Project
 */

/**
 * Mengambil API Key perangkat yang dikonfigurasi melalui environment variable DEVICE_API_KEY.
 * Pada lingkungan lokal tanpa env var, gunakan kunci pengembangan default yang harus diganti saat deploy.
 */
function getDeviceApiKey(): string {
    $envKey = getenv('DEVICE_API_KEY');
    if (is_string($envKey) && trim($envKey) !== '') {
        return trim($envKey);
    }

    return 'smartplant-dev-device-key-change-me';
}

/**
 * Mengekstrak API Key dari header HTTP X-API-Key atau Authorization: Bearer <token>.
 */
function extractDeviceApiKeyFromRequest(): string {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (is_string($apiKey) && trim($apiKey) !== '') {
        return trim($apiKey);
    }

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (is_string($authHeader) && preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    return '';
}

/**
 * Memvalidasi API Key perangkat menggunakan perbandingan constant-time.
 */
function isValidDeviceApiKey(string $providedKey): bool {
    $trimmed = trim($providedKey);
    if ($trimmed === '') {
        return false;
    }

    return hash_equals(getDeviceApiKey(), $trimmed);
}

/**
 * Guard / Middleware: Menolak request perangkat tanpa API Key yang valid (HTTP 401).
 */
function requireDeviceApiKey(): void {
    if (!isValidDeviceApiKey(extractDeviceApiKeyFromRequest())) {
        http_response_code(401);
        echo json_encode([
            "status"  => "error",
            "message" => "Akses perangkat ditolak. X-API-Key tidak valid atau tidak disertakan."
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

