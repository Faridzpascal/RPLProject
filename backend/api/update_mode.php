<?php
/**
 * API Endpoint: POST /backend/api/update_mode.php
 * Mengubah operating_mode perangkat ('AUTO' atau 'MANUAL')
 */

require_once __DIR__ . '/../helpers/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/PlantLogic.php';

// Atur header CORS & Session Cookie
sendCorsHeaders();
handleCorsPreflight();

// Validasi Sesi Login Pengguna (Session Guard)
requireAuth();

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode HTTP tidak diizinkan. Gunakan POST."], JSON_UNESCAPED_UNICODE);
    exit();
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

$mode = strtoupper(trim((string)($data['operating_mode'] ?? $_POST['operating_mode'] ?? '')));
$mac_address = $data['mac_address'] ?? $_POST['mac_address'] ?? null;

if (!PlantLogic::isValidMode($mode)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Nilai mode tidak valid. Pilihan yang tersedia: 'AUTO' atau 'MANUAL'."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Cari perangkat
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

    $device_id = (int)$device['id'];
    $pump_status = $device['pump_status'];

    // Jika beralih ke mode AUTO, sesuaikan status pompa berdasarkan log kelembapan terkini
    if ($mode === 'AUTO') {
        $stmt_moist = $db->prepare("SELECT moisture_level FROM sensor_logs WHERE device_id = :id ORDER BY id DESC LIMIT 1");
        $stmt_moist->bindParam(':id', $device_id, PDO::PARAM_INT);
        $stmt_moist->execute();
        $latest_log = $stmt_moist->fetch();

        if ($latest_log && (float)$latest_log['moisture_level'] < 35.0) {
            $pump_status = 'ON';
        } else {
            $pump_status = 'OFF';
        }
    }

    // Perbarui mode di database
    $update_sql = "UPDATE devices SET operating_mode = :mode, pump_status = :pump_status WHERE id = :id";
    $stmt_update = $db->prepare($update_sql);
    $stmt_update->bindParam(':mode', $mode);
    $stmt_update->bindParam(':pump_status', $pump_status);
    $stmt_update->bindParam(':id', $device_id, PDO::PARAM_INT);
    $stmt_update->execute();

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Mode operasi berhasil diubah ke {$mode}.",
        "data" => [
            "device_id" => $device_id,
            "mac_address" => $device['mac_address'],
            "operating_mode" => $mode,
            "pump_status" => $pump_status
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('[API update_mode.php Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Terjadi kesalahan pada server. Silakan coba lagi nanti."], JSON_UNESCAPED_UNICODE);
}
