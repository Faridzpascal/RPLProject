<?php

/**
 * Smart Plant IoT - Dummy Soil Moisture Data Generator CLI
 * 
 * Mensimulasikan pembacaan sensor kelembapan tanah Capacitive Soil Moisture Sensor
 * dan mengirimkannya ke Backend Laravel API secara berkala.
 * 
 * Penggunaan:
 * php dummy_generator.php [interval_detik]
 */

$apiUrl = 'http://127.0.0.1:8000/api/simulator/generate';
$deviceId = 'SP001';
$interval = isset($argv[1]) ? (int)$argv[1] : 5; // Default 5 detik

echo "========================================================\n";
echo " 🌱 SMART PLANT - DUMMY SENSOR DATA GENERATOR (CLI)\n";
echo " Target Endpoint : $apiUrl\n";
echo " Device ID       : $deviceId\n";
echo " Interval        : $interval detik\n";
echo " Tekan Ctrl + C untuk menghentikan simulator\n";
echo "========================================================\n\n";

// Nilai awal kelembapan tanah
$currentMoisture = 74;

while (true) {
    $timestamp = date('H:i:s');

    // Buat payload
    $payload = json_encode([
        'device_id' => $deviceId,
        'soil_moisture' => $currentMoisture
    ]);

    // Kirim HTTP POST ke Laravel API menggunakan cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        $result = json_decode($response, true);
        $status = $result['plant_status'] ?? 'N/A';
        $autoWatering = (!empty($result['auto_watering_triggered'])) ? '🚿 [AUTO WATERING ON!]' : '';

        echo "[$timestamp] Ingested -> Soil Moisture: {$currentMoisture}% | Status: {$status} $autoWatering\n";

        // Simulasi pengeringan tanah alami
        if ($currentMoisture <= 25) {
            // Setelah tanah sangat kering dan tersiram, kelembapan melonjak naik
            echo "             💦 Tanah tersiram air! Kelembapan meningkat.\n";
            $currentMoisture = rand(75, 85);
        } else {
            // Pengurangan bertahap (tanah mengering alami)
            $decrease = rand(2, 4);
            $currentMoisture = max(15, $currentMoisture - $decrease);
        }
    } else {
        echo "[$timestamp] ⚠️ Error ($httpCode): Gagal mengirim data. Pastikan Laravel berjalan di http://127.0.0.1:8000\n";
        if ($curlError) {
            echo "             Detail: $curlError\n";
        }
    }

    sleep($interval);
}
