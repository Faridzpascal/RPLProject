<?php
/**
 * API Endpoint: GET /backend/api/get_status.php
 * Digunakan oleh ESP32 untuk mengecek operating_mode dan pump_status secara periodik
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle CORS Preflight request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Metode HTTP tidak diizinkan. Gunakan GET."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$mac_address = $_GET['mac_address'] ?? null;

$database = new Database();
$db = $database->getConnection();

try {
    if (!empty($mac_address)) {
        $query = "SELECT id, mac_address, name, operating_mode, pump_status, last_seen 
                  FROM devices WHERE mac_address = :mac_address LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':mac_address', $mac_address);
    } else {
        // Ambil perangkat pertama yang aktif
        $query = "SELECT id, mac_address, name, operating_mode, pump_status, last_seen 
                  FROM devices ORDER BY id ASC LIMIT 1";
        $stmt = $db->prepare($query);
    }

    $stmt->execute();
    $device = $stmt->fetch();

    if (!$device) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Perangkat tidak ditemukan di database."
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Perbarui heartbeat last_seen
    $update_sql = "UPDATE devices SET last_seen = NOW() WHERE id = :id";
    $stmt_update = $db->prepare($update_sql);
    $stmt_update->bindParam(':id', $device['id'], PDO::PARAM_INT);
    $stmt_update->execute();

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "mac_address" => $device['mac_address'],
        "name" => $device['name'],
        "operating_mode" => $device['operating_mode'], // 'AUTO' atau 'MANUAL'
        "pump_status" => $device['pump_status'],       // 'ON' atau 'OFF'
        "last_seen" => $device['last_seen'],
        "server_time" => date("Y-m-d H:i:s")
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
