<?php
/**
 * API Endpoint: POST /backend/api/update_pump.php
 * Mengubah pump_status ('ON' atau 'OFF') secara manual dari dashboard
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode HTTP tidak diizinkan. Gunakan POST."], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

$pump_status = strtoupper(trim((string)($data['pump_status'] ?? $_POST['pump_status'] ?? '')));
$mac_address = $data['mac_address'] ?? $_POST['mac_address'] ?? null;

if (!in_array($pump_status, ['ON', 'OFF'], true)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Nilai pump_status tidak valid. Pilihan yang tersedia: 'ON' atau 'OFF'."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Ambil data perangkat
    if (!empty($mac_address)) {
        $stmt_check = $db->prepare("SELECT id, mac_address, operating_mode, pump_status FROM devices WHERE mac_address = :mac LIMIT 1");
        $stmt_check->bindParam(':mac', $mac_address);
    } else {
        $stmt_check = $db->prepare("SELECT id, mac_address, operating_mode, pump_status FROM devices ORDER BY id ASC LIMIT 1");
    }
    $stmt_check->execute();
    $device = $stmt_check->fetch();

    if (!$device) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Perangkat tidak ditemukan."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Aturan Sistem: Kontrol manual pompa HANYA dapat dilakukan jika mode adalah MANUAL
    if ($device['operating_mode'] === 'AUTO') {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Kontrol manual dinonaktifkan. Pompa hanya dapat diubah secara manual jika perangkat dalam Mode Manual."
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $device_id = (int)$device['id'];

    // Perbarui status pompa di database
    $update_sql = "UPDATE devices SET pump_status = :pump_status WHERE id = :id";
    $stmt_update = $db->prepare($update_sql);
    $stmt_update->bindParam(':pump_status', $pump_status);
    $stmt_update->bindParam(':id', $device_id, PDO::PARAM_INT);
    $stmt_update->execute();

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Status pompa berhasil diubah menjadi {$pump_status}.",
        "data" => [
            "device_id" => $device_id,
            "mac_address" => $device['mac_address'],
            "operating_mode" => $device['operating_mode'],
            "pump_status" => $pump_status
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
