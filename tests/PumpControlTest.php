<?php
/**
 * Unit Test: Fitur 2 - Kontrol Dual Mode & Aturan Keamanan Pompa
 * Smart Plant Dashboard - Tugas Pertemuan 5 RPL
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class PumpControlTest extends TestCase {

    /**
     * Test 1: Logika Mode AUTO mengaktifkan pompa (ON) saat kelembapan < 35.0%
     */
    public function testAutoModeTurnsPumpOnWhenDry(): void {
        $status = PlantLogic::evaluateAutoPump('AUTO', 28.5);
        $this->assertEquals('ON', $status, 'Mode AUTO harus menyalakan pompa saat kelembapan < 35%');
    }

    /**
     * Test 2: Logika Mode AUTO mematikan pompa (OFF) saat kelembapan >= 35.0%
     */
    public function testAutoModeTurnsPumpOffWhenMoist(): void {
        $statusAt35 = PlantLogic::evaluateAutoPump('AUTO', 35.0);
        $this->assertEquals('OFF', $statusAt35, 'Mode AUTO harus mematikan pompa saat kelembapan >= 35%');

        $statusAt60 = PlantLogic::evaluateAutoPump('AUTO', 60.0);
        $this->assertEquals('OFF', $statusAt60, 'Mode AUTO harus tetap OFF jika kelembapan tinggi');
    }

    /**
     * Test 3: Guard Rule - Pompa TIDAK BISA diubah manual jika mode adalah AUTO
     */
    public function testCannotToggleManualPumpInAutoMode(): void {
        $allowed = PlantLogic::canToggleManualPump('AUTO');
        $this->assertFalse($allowed, 'Guard Rule: Tombol pompa manual harus dikunci saat mode AUTO');
    }

    /**
     * Test 4: Guard Rule - Pompa BISA diubah manual jika mode adalah MANUAL
     */
    public function testCanToggleManualPumpInManualMode(): void {
        $allowed = PlantLogic::canToggleManualPump('MANUAL');
        $this->assertTrue($allowed, 'Guard Rule: Kontrol manual pompa harus diizinkan saat mode MANUAL');
    }

    /**
     * Test 5: Validasi nilai parameter mode operasi
     */
    public function testValidateOperatingMode(): void {
        $this->assertTrue(PlantLogic::isValidMode('AUTO'), 'Mode AUTO harus valid');
        $this->assertTrue(PlantLogic::isValidMode('MANUAL'), 'Mode MANUAL harus valid');
        $this->assertTrue(PlantLogic::isValidMode('auto'), 'Mode auto lowercase harus valid (case-insensitive)');
        $this->assertFalse(PlantLogic::isValidMode('SEMI_AUTO'), 'Mode SEMI_AUTO harus tidak valid');
        $this->assertFalse(PlantLogic::isValidMode(''), 'Mode kosong harus tidak valid');
    }

    /**
     * Test 6: Validasi nilai status pompa
     */
    public function testValidatePumpStatus(): void {
        $this->assertTrue(PlantLogic::isValidPumpStatus('ON'), 'Status ON harus valid');
        $this->assertTrue(PlantLogic::isValidPumpStatus('OFF'), 'Status OFF harus valid');
        $this->assertTrue(PlantLogic::isValidPumpStatus('on'), 'Status on lowercase harus valid');
        $this->assertFalse(PlantLogic::isValidPumpStatus('IDLE'), 'Status IDLE harus tidak valid');
        $this->assertFalse(PlantLogic::isValidPumpStatus('RUNNING'), 'Status RUNNING harus tidak valid');
    }

    /**
     * Test 7: Isolasi Mode MANUAL dari pemicu otomatis sensor
     */
    public function testManualModeIsolatesSensorAutoTrigger(): void {
        // Pada mode MANUAL, fungsi evaluasi otomatis siram tidak boleh mengubah status secara sepihak
        $statusInManual = PlantLogic::evaluateAutoPump('MANUAL', 15.0);
        $this->assertEquals('OFF', $statusInManual, 'Evaluasi auto siram harus mengembalikan OFF jika bukan mode AUTO');
    }
}
