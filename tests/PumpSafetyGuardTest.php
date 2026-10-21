<?php
/**
 * File Unit Test 3: PumpSafetyGuardTest
 * Menguji User Story 3 (US-3): Kontrol Pompa Manual & Aturan Keamanan (Safety Guard)
 * Smart Plant Dashboard - Tugas RPL Pertemuan 5
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class PumpSafetyGuardTest extends TestCase {

    /**
     * AC-3.1: Guard Rule - Pompa manual DIKUNCI / dilarang saat mode AUTO (mencegah konflik kendali)
     */
    public function testManualPumpToggleBlockedInAutoMode(): void {
        $allowed = PlantLogic::canToggleManualPump('AUTO');
        $this->assertFalse($allowed, 'Guard Rule: Kontrol manual harus dilarang saat perangkat dalam Mode AUTO');
    }

    /**
     * AC-3.2: Guard Rule - Pompa manual DIIZINKAN hanya saat mode MANUAL
     */
    public function testManualPumpToggleAllowedInManualMode(): void {
        $allowed = PlantLogic::canToggleManualPump('MANUAL');
        $this->assertTrue($allowed, 'Guard Rule: Kontrol manual harus diizinkan saat perangkat dalam Mode MANUAL');
    }

    /**
     * AC-3.3: Validasi nilai parameter operating_mode
     */
    public function testOperatingModeValidation(): void {
        $this->assertTrue(PlantLogic::isValidMode('AUTO'), 'Mode AUTO harus valid');
        $this->assertTrue(PlantLogic::isValidMode('MANUAL'), 'Mode MANUAL harus valid');
        $this->assertTrue(PlantLogic::isValidMode('auto'), 'Mode auto lowercase harus valid');
        $this->assertFalse(PlantLogic::isValidMode('HYBRID'), 'Mode HYBRID tidak valid');
        $this->assertFalse(PlantLogic::isValidMode(''), 'Mode kosong tidak valid');
    }

    /**
     * AC-3.4: Validasi nilai parameter pump_status
     */
    public function testPumpStatusValidation(): void {
        $this->assertTrue(PlantLogic::isValidPumpStatus('ON'), 'Status ON harus valid');
        $this->assertTrue(PlantLogic::isValidPumpStatus('OFF'), 'Status OFF harus valid');
        $this->assertTrue(PlantLogic::isValidPumpStatus('on'), 'Status on lowercase harus valid');
        $this->assertFalse(PlantLogic::isValidPumpStatus('START'), 'Status START tidak valid');
        $this->assertFalse(PlantLogic::isValidPumpStatus('STOP'), 'Status STOP tidak valid');
    }
}
