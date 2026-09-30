<?php
/**
 * API Endpoint: POST /backend/api/update_sensor.php
 * Digunakan oleh ESP32 untuk mengirimkan data kelembapan tanah
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle CORS Preflight request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Metode HTTP tidak diizinkan. Gunakan POST."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Ambil input data dari JSON body atau $_POST form data
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

$mac_address = $data['mac_address'] ?? $_POST['mac_address'] ?? null;
$moisture_level = $data['moisture_level'] ?? $_POST['moisture_level'] ?? null;

// Validasi input
if (empty($mac_address) || $moisture_level === null || !is_numeric($moisture_level)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Data tidak lengkap atau format salah. Diperlukan 'mac_address' dan 'moisture_level' (angka)."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$mac_address = trim((string)$mac_address);
$moisture_level = round((float)$moisture_level, 2);

// Batasi persentase kelembapan 0 - 100%
if ($moisture_level < 0) $moisture_level = 0.0;
if ($moisture_level > 100) $moisture_level = 100.0;

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Cek apakah device sudah terdaftar di tabel `devices`
    $query_device = "SELECT id, name, operating_mode, pump_status FROM devices WHERE mac_address = :mac_address LIMIT 1";
    $stmt_device = $db->prepare($query_device);
    $stmt_device->bindParam(':mac_address', $mac_address);
    $stmt_device->execute();

    $device = $stmt_device->fetch();

    if (!$device) {
        // Jika belum ada, daftarkan otomatis perangkat baru
        $default_name = "ESP32 (" . substr($mac_address, -5) . ")";
        $insert_device_sql = "INSERT INTO devices (mac_address, name, operating_mode, pump_status, last_seen) 
                              VALUES (:mac_address, :name, 'AUTO', 'OFF', NOW())";
        $stmt_insert_device = $db->prepare($insert_device_sql);
        $stmt_insert_device->bindParam(':mac_address', $mac_address);
        $stmt_insert_device->bindParam(':name', $default_name);
        $stmt_insert_device->execute();

        $device_id = (int)$db->lastInsertId();
        $operating_mode = 'AUTO';
        $pump_status = 'OFF';
    } else {
        $device_id = (int)$device['id'];
        $operating_mode = $device['operating_mode'];
        $pump_status = $device['pump_status'];
    }

    // 2. Simpan data sensor ke tabel `sensor_logs`
    $insert_log_sql = "INSERT INTO sensor_logs (device_id, moisture_level, created_at) 
                       VALUES (:device_id, :moisture_level, NOW())";
    $stmt_log = $db->prepare($insert_log_sql);
    $stmt_log->bindParam(':device_id', $device_id, PDO::PARAM_INT);
    $stmt_log->bindParam(':moisture_level', $moisture_level);
    $stmt_log->execute();

    // 3. Logika Otomatisasi jika mode AUTO:
    // Jika kelembapan tanah < 35%, nyalakan pompa otomatis
    // Jika kelembapan tanah >= 40%, matikan pompa
    if ($operating_mode === 'AUTO') {
        if ($moisture_level < 35.0) {
            $pump_status = 'ON';
        } else {
            $pump_status = 'OFF';
        }
    }

    // 4. Perbarui `last_seen` dan `pump_status` pada tabel `devices`
    $update_device_sql = "UPDATE devices SET last_seen = NOW(), pump_status = :pump_status WHERE id = :id";
    $stmt_update = $db->prepare($update_device_sql);
    $stmt_update->bindParam(':pump_status', $pump_status);
    $stmt_update->bindParam(':id', $device_id, PDO::PARAM_INT);
    $stmt_update->execute();

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Data sensor berhasil disimpan.",
        "data" => [
            "device_id" => $device_id,
            "mac_address" => $mac_address,
            "moisture_level" => $moisture_level,
            "operating_mode" => $operating_mode,
            "pump_status" => $pump_status,
            "timestamp" => date("Y-m-d H:i:s")
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
