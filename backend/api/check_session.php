<?php
/**
 * API Endpoint: GET /backend/api/check_session.php
 * Digunakan oleh Web Dashboard untuk mengecek apakah sesi pengguna saat ini aktif
 * Smart Plant Dashboard - University RPL IoT Project
 */

require_once __DIR__ . '/../helpers/auth_check.php';

sendCorsHeaders();
handleCorsPreflight();

startAppSession();

if (isLoggedIn()) {
    $user = getAuthUser();
    http_response_code(200);
    echo json_encode([
        "status"        => "success",
        "authenticated" => true,
        "user"          => $user
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(401);
    echo json_encode([
        "status"        => "error",
        "authenticated" => false,
        "message"       => "Pengguna belum login atau sesi telah berakhir."
    ], JSON_UNESCAPED_UNICODE);
}
