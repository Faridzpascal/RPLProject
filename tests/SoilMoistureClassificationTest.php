<?php
/**
 * File Unit Test 1: SoilMoistureClassificationTest
 * Menguji User Story 1 (US-1): Monitoring Kelembapan & Klasifikasi Kondisi Tanah
 * Smart Plant Dashboard - Tugas RPL Pertemuan 5
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class SoilMoistureClassificationTest extends TestCase {

    /**
     * AC-1.1: Kelembapan di bawah ambang batas (< 35.0%) harus berstatus DRY (Kering)
     */
    public function testClassificationForDrySoil(): void {
        $result = PlantLogic::classifyMoisture(22.4);
        $this->assertEquals('DRY', $result['statusKey'], 'Kelembapan 22.4% harus berstatus DRY');
        $this->assertEquals('Kering (Perlu Disiram)', $result['label'], 'Label harus menyatakan Kering');
        $this->assertEquals('bg-danger', $result['bgClass'], 'Warna indikator harus merah/danger');
    }

    /**
     * AC-1.2: Kelembapan 35.0% - 70.0% harus berstatus IDEAL (Lembab)
     */
    public function testClassificationForIdealSoil(): void {
        $result = PlantLogic::classifyMoisture(54.0);
        $this->assertEquals('IDEAL', $result['statusKey'], 'Kelembapan 54.0% harus berstatus IDEAL');
        $this->assertEquals('Lembab (Kondisi Ideal)', $result['label'], 'Label harus menyatakan Lembab');
        $this->assertEquals('bg-success', $result['bgClass'], 'Warna indikator harus hijau/success');
    }

    /**
     * AC-1.3: Kelembapan di atas 70.0% harus berstatus SATURATED (Sangat Basah)
     */
    public function testClassificationForSaturatedSoil(): void {
        $result = PlantLogic::classifyMoisture(88.5);
        $this->assertEquals('SATURATED', $result['statusKey'], 'Kelembapan 88.5% harus berstatus SATURATED');
        $this->assertEquals('Sangat Basah / Jenuh', $result['label'], 'Label harus menyatakan Sangat Basah');
        $this->assertEquals('bg-info', $result['bgClass'], 'Warna indikator harus biru/info');
    }

    /**
     * AC-1.4: Uji titik kritis batas ambang (Boundary Conditions)
     */
    public function testBoundaryThresholdConditions(): void {
        // Tepat 35.0% masuk kategori IDEAL
        $at35 = PlantLogic::classifyMoisture(35.0);
        $this->assertEquals('IDEAL', $at35['statusKey'], 'Tepat 35.0% adalah batas bawah IDEAL');

        // Tepat 70.0% masuk kategori IDEAL
        $at70 = PlantLogic::classifyMoisture(70.0);
        $this->assertEquals('IDEAL', $at70['statusKey'], 'Tepat 70.0% adalah batas atas IDEAL');

        // 34.9% masuk kategori DRY
        $at34_9 = PlantLogic::classifyMoisture(34.9);
        $this->assertEquals('DRY', $at34_9['statusKey'], '34.9% harus masuk kategori DRY');

        // 70.1% masuk kategori SATURATED
        $at70_1 = PlantLogic::classifyMoisture(70.1);
        $this->assertEquals('SATURATED', $at70_1['statusKey'], '70.1% harus masuk kategori SATURATED');
    }

    /**
     * AC-1.5: Sanitasi dan pembatasan nilai sensor (Clamping 0% - 100%)
     */
    public function testMoistureValueClamping(): void {
        $this->assertEquals(0.0, PlantLogic::clampMoisture(-25.0), 'Nilai negatif harus dibatasi ke 0.0%');
        $this->assertEquals(100.0, PlantLogic::clampMoisture(175.0), 'Nilai > 100 harus dibatasi ke 100.0%');
        $this->assertEquals(42.5, PlantLogic::clampMoisture(42.54), 'Nilai valid dibulatkan 1 desimal');
    }
}
