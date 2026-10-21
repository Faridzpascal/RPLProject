<?php
/**
 * File Unit Test 2: AutoWateringLogicTest
 * Menguji User Story 2 (US-2): Otomatisasi Penyiraman Cerdas (Mode AUTO)
 * Smart Plant Dashboard - Tugas RPL Pertemuan 5
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class AutoWateringLogicTest extends TestCase {

    /**
     * AC-2.1: Mode AUTO wajib menyalakan pompa (ON) jika kelembapan < 35.0%
     */
    public function testAutoModeTurnsPumpOnWhenMoistureBelowThreshold(): void {
        $pumpStatus = PlantLogic::evaluateAutoPump('AUTO', 30.0);
        $this->assertEquals('ON', $pumpStatus, 'Pompa harus berstatus ON saat tanah kering (< 35%)');

        $pumpStatusExtreme = PlantLogic::evaluateAutoPump('AUTO', 12.5);
        $this->assertEquals('ON', $pumpStatusExtreme, 'Pompa harus tetap ON pada kondisi sangat kering');
    }

    /**
     * AC-2.2: Mode AUTO wajib mematikan pompa (OFF) jika kelembapan >= 35.0%
     */
    public function testAutoModeTurnsPumpOffWhenMoistureSufficient(): void {
        $pumpStatusAt35 = PlantLogic::evaluateAutoPump('AUTO', 35.0);
        $this->assertEquals('OFF', $pumpStatusAt35, 'Pompa harus OFF saat tepat mencapai ambang 35.0%');

        $pumpStatusMoist = PlantLogic::evaluateAutoPump('AUTO', 65.0);
        $this->assertEquals('OFF', $pumpStatusMoist, 'Pompa harus OFF saat tanah lembab');
    }

    /**
     * AC-2.3: Isolasi mode - Logika auto siram tidak boleh menimpa jika bukan mode AUTO
     */
    public function testAutoWateringDoesNotTriggerInManualMode(): void {
        $pumpStatusInManual = PlantLogic::evaluateAutoPump('MANUAL', 15.0);
        $this->assertEquals('OFF', $pumpStatusInManual, 'Auto watering harus mengembalikan OFF jika perangkat di Mode MANUAL');
    }

    /**
     * AC-2.4: Logika histeresis siram untuk mencegah relay fluttering
     */
    public function testHysteresisWateringLogic(): void {
        // Jika kelembapan sangat kering (< 35%), pompa menyala ON
        $pump1 = PlantLogic::evaluateHysteresis('AUTO', 30.0, 'OFF', 35.0, 45.0);
        $this->assertEquals('ON', $pump1, 'Harus ON saat kelembapan di bawah 35%');

        // Jika kelembapan berada di antara 35% dan 45% dan pompa sedang ON, pertahankan ON
        $pump2 = PlantLogic::evaluateHysteresis('AUTO', 40.0, 'ON', 35.0, 45.0);
        $this->assertEquals('ON', $pump2, 'Harus tetap ON saat di rentang histeresis 35-45%');

        // Jika kelembapan telah mencapai batas atas (>= 45%), pompa dimatikan
        $pump3 = PlantLogic::evaluateHysteresis('AUTO', 46.0, 'ON', 35.0, 45.0);
        $this->assertEquals('OFF', $pump3, 'Harus OFF saat mencapai batas aman 45%');
    }
}
