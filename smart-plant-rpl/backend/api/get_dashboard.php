<?php
/**
 * API Endpoint: GET /backend/api/get_dashboard.php
 * Digunakan oleh Web Dashboard untuk mengambil seluruh data real-time:
 * - Status perangkat (Mode, Pompa, Last Seen)
 * - Kelembapan tanah saat ini
 * - 10 riwayat log sensor terakhir
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Ambil data perangkat utama
    $stmt_device = $db->query("SELECT id, mac_address, name, operating_mode, pump_status, last_seen, created_at FROM devices ORDER BY id ASC LIMIT 1");
    $device = $stmt_device->fetch();

    if (!$device) {
        // Jika belum ada data sama sekali di devices, buat data default
        $db->query("INSERT INTO devices (mac_address, name, operating_mode, pump_status, last_seen) VALUES ('24:6F:28:7A:B1:C0', 'ESP32 - Tanaman Monstera', 'AUTO', 'OFF', NOW())");
        $stmt_device = $db->query("SELECT id, mac_address, name, operating_mode, pump_status, last_seen, created_at FROM devices ORDER BY id ASC LIMIT 1");
        $device = $stmt_device->fetch();
    }

    $device_id = (int)$device['id'];

    // Hitung status online ESP32 (jika last_seen < 30 detik yang lalu dianggap online)
    $last_seen_timestamp = strtotime($device['last_seen']);
    $time_difference = time() - $last_seen_timestamp;
    $is_online = ($time_difference <= 30);

    // 2. Ambil log sensor terbaru (Kelembapan saat ini)
    $stmt_latest = $db->prepare("SELECT moisture_level, created_at FROM sensor_logs WHERE device_id = :device_id ORDER BY id DESC LIMIT 1");
    $stmt_latest->bindParam(':device_id', $device_id, PDO::PARAM_INT);
    $stmt_latest->execute();
    $latest_log = $stmt_latest->fetch();

    $current_moisture = $latest_log ? (float)$latest_log['moisture_level'] : 0.0;
    $last_moisture_time = $latest_log ? $latest_log['created_at'] : $device['last_seen'];

    // 3. Ambil 10 riwayat log sensor terakhir
    $stmt_logs = $db->prepare("SELECT id, moisture_level, created_at FROM sensor_logs WHERE device_id = :device_id ORDER BY id DESC LIMIT 10");
    $stmt_logs->bindParam(':device_id', $device_id, PDO::PARAM_INT);
    $stmt_logs->execute();
    $logs = $stmt_logs->fetchAll();

    // 4. Hitung statistik ringkas (rata-rata, minimum, maksimum)
    $stats = [
        "avg" => 0,
        "min" => 0,
        "max" => 0,
        "count" => count($logs)
    ];

    if (!empty($logs)) {
        $levels = array_column($logs, 'moisture_level');
        $stats['avg'] = round(array_sum($levels) / count($levels), 1);
        $stats['min'] = round(min($levels), 1);
        $stats['max'] = round(max($levels), 1);
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => [
            "device" => [
                "id" => (int)$device['id'],
                "mac_address" => $device['mac_address'],
                "name" => $device['name'],
                "operating_mode" => $device['operating_mode'],
                "pump_status" => $device['pump_status'],
                "last_seen" => $device['last_seen'],
                "is_online" => $is_online,
                "seconds_since_last_seen" => $time_difference
            ],
            "current_moisture" => $current_moisture,
            "last_moisture_time" => $last_moisture_time,
            "logs" => $logs,
            "stats" => $stats,
            "server_time" => date("Y-m-d H:i:s")
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
