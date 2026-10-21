<?php
/**
 * File Unit Test 5: DeviceConnectivityTest
 * Menguji User Story 5 (US-5): Deteksi Heartbeat & Status Konektivitas ESP32
 * Smart Plant Dashboard - Tugas RPL Pertemuan 5
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class DeviceConnectivityTest extends TestCase {

    /**
     * AC-5.1: Perangkat dinyatakan ONLINE jika heartbeat diterima <= 30 detik yang lalu
     */
    public function testDeviceIsOnlineWithinTimeout(): void {
        $now = time();
        
        // 12 detik lalu -> ONLINE
        $this->assertTrue(PlantLogic::isOnline($now - 12, $now), 'Perangkat harus ONLINE jika selisih 12 detik');

        // Tepat 30 detik lalu -> ONLINE (ambang batas pas)
        $this->assertTrue(PlantLogic::isOnline($now - 30, $now), 'Perangkat harus ONLINE jika tepat 30 detik');
    }

    /**
     * AC-5.2: Perangkat dinyatakan OFFLINE jika heartbeat > 30 detik yang lalu atau null
     */
    public function testDeviceIsOfflineAfterTimeout(): void {
        $now = time();

        // 35 detik lalu -> OFFLINE
        $this->assertFalse(PlantLogic::isOnline($now - 35, $now), 'Perangkat harus OFFLINE jika selisih 35 detik');

        // null / empty -> OFFLINE
        $this->assertFalse(PlantLogic::isOnline(null, $now), 'Perangkat harus OFFLINE jika timestamp null');
        $this->assertFalse(PlantLogic::isOnline('', $now), 'Perangkat harus OFFLINE jika timestamp string kosong');
    }

    /**
     * AC-5.3: Validasi format alamat MAC mikrokontroler ESP32
     */
    public function testMacAddressFormatValidation(): void {
        $this->assertTrue(PlantLogic::isValidMacAddress('24:6F:28:7A:B1:C0'), 'Format MAC address standar colon harus valid');
        $this->assertTrue(PlantLogic::isValidMacAddress('24-6F-28-7A-B1-C0'), 'Format MAC address dengan strip harus valid');
        $this->assertTrue(PlantLogic::isValidMacAddress('aa:bb:cc:dd:ee:ff'), 'Format MAC address lowercase harus valid');
        $this->assertFalse(PlantLogic::isValidMacAddress('invalid-mac'), 'Format string sembarang harus tidak valid');
        $this->assertFalse(PlantLogic::isValidMacAddress('24:6F:28:7A:B1'), 'MAC tidak lengkap (kurang segmen) harus tidak valid');
        $this->assertFalse(PlantLogic::isValidMacAddress(''), 'MAC kosong harus tidak valid');
    }

    /**
     * AC-5.4: Perhitungan selisih detik waktu koneksi terakhir
     */
    public function testSecondsSinceLastSeenCalculation(): void {
        $now = 50000;
        $seconds = PlantLogic::getSecondsSinceLastSeen($now - 20, $now);
        $this->assertEquals(20, $seconds, 'Selisih detik harus tepat 20 detik');
    }
}
