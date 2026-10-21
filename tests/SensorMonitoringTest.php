<?php
/**
 * Unit Test: Fitur 1 - Monitoring Kelembapan & Klasifikasi Sensor
 * Smart Plant Dashboard - Tugas Pertemuan 5 RPL
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class SensorMonitoringTest extends TestCase {

    /**
     * Test 1: Klasifikasi kondisi tanah kering (< 35.0%)
     */
    public function testClassifyMoistureDry(): void {
        $result = PlantLogic::classifyMoisture(24.5);
        $this->assertEquals('DRY', $result['statusKey'], 'Kelembapan 24.5% harus berstatus DRY');
        $this->assertEquals('Kering (Perlu Disiram)', $result['label'], 'Label harus menyatakan Kering');
        $this->assertEquals('bg-danger', $result['bgClass'], 'Warna indikator harus merah/danger');
    }

    /**
     * Test 2: Klasifikasi kondisi tanah ideal / lembab (35.0% - 70.0%)
     */
    public function testClassifyMoistureIdeal(): void {
        $result = PlantLogic::classifyMoisture(50.0);
        $this->assertEquals('IDEAL', $result['statusKey'], 'Kelembapan 50.0% harus berstatus IDEAL');
        $this->assertEquals('Lembab (Kondisi Ideal)', $result['label'], 'Label harus menyatakan Lembab');
        $this->assertEquals('bg-success', $result['bgClass'], 'Warna indikator harus hijau/success');
    }

    /**
     * Test 3: Klasifikasi kondisi tanah sangat basah / jenuh (> 70.0%)
     */
    public function testClassifyMoistureSaturated(): void {
        $result = PlantLogic::classifyMoisture(85.2);
        $this->assertEquals('SATURATED', $result['statusKey'], 'Kelembapan 85.2% harus berstatus SATURATED');
        $this->assertEquals('Sangat Basah / Jenuh', $result['label'], 'Label harus menyatakan Sangat Basah');
        $this->assertEquals('bg-info', $result['bgClass'], 'Warna indikator harus info/cyan');
    }

    /**
     * Test 4: Boundary test tepat pada batas ambang 35.0% dan 70.0%
     */
    public function testBoundaryThresholds(): void {
        $at35 = PlantLogic::classifyMoisture(35.0);
        $this->assertEquals('IDEAL', $at35['statusKey'], 'Tepat 35.0% masuk kategori IDEAL');

        $at70 = PlantLogic::classifyMoisture(70.0);
        $this->assertEquals('IDEAL', $at70['statusKey'], 'Tepat 70.0% masuk kategori IDEAL');

        $at34_9 = PlantLogic::classifyMoisture(34.9);
        $this->assertEquals('DRY', $at34_9['statusKey'], '34.9% masuk kategori DRY');

        $at70_1 = PlantLogic::classifyMoisture(70.1);
        $this->assertEquals('SATURATED', $at70_1['statusKey'], '70.1% masuk kategori SATURATED');
    }

    /**
     * Test 5: Pembatasan rentang kelembapan (clamping 0% - 100%)
     */
    public function testMoistureClamping(): void {
        $this->assertEquals(0.0, PlantLogic::clampMoisture(-15.5), 'Nilai negatif harus di-clamp ke 0.0%');
        $this->assertEquals(100.0, PlantLogic::clampMoisture(145.0), 'Nilai > 100 harus di-clamp ke 100.0%');
        $this->assertEquals(45.5, PlantLogic::clampMoisture(45.54), 'Nilai valid harus dibulatkan 1 desimal');
    }

    /**
     * Test 6: Perhitungan statistik agregat (average, min, max, count)
     */
    public function testCalculateStats(): void {
        $dummyLogs = [48.5, 46.2, 44.0, 41.8, 38.5, 35.1, 31.4, 28.0, 55.4, 52.8];
        $stats = PlantLogic::calculateStats($dummyLogs);

        $this->assertEquals(10, $stats['count'], 'Jumlah data harus 10');
        $this->assertEquals(28.0, $stats['min'], 'Nilai minimum harus 28.0%');
        $this->assertEquals(55.4, $stats['max'], 'Nilai maksimum harus 55.4%');
        $this->assertEquals(42.2, $stats['avg'], 'Rata-rata harus tepat 42.2%');
    }

    /**
     * Test 7: Perhitungan statistik jika data log masih kosong
     */
    public function testCalculateStatsEmpty(): void {
        $stats = PlantLogic::calculateStats([]);
        $this->assertEquals(0, $stats['count'], 'Count harus 0');
        $this->assertEquals(0.0, $stats['avg'], 'Avg harus 0.0');
        $this->assertEquals(0.0, $stats['min'], 'Min harus 0.0');
        $this->assertEquals(0.0, $stats['max'], 'Max harus 0.0');
    }

    /**
     * Test 8: Deteksi status online/offline ESP32 berdasarkan heartbeat last_seen
     */
    public function testDeviceOnlineDetection(): void {
        $now = time();
        
        // 10 detik lalu -> ONLINE (ambang batas 30s)
        $this->assertTrue(PlantLogic::isOnline($now - 10, $now), 'Perangkat harus online jika last_seen 10s lalu');

        // 30 detik lalu -> ONLINE (tepat pada batas)
        $this->assertTrue(PlantLogic::isOnline($now - 30, $now), 'Perangkat harus online jika tepat 30s lalu');

        // 45 detik lalu -> OFFLINE (> 30s)
        $this->assertFalse(PlantLogic::isOnline($now - 45, $now), 'Perangkat harus offline jika > 30s lalu');

        // null / empty -> OFFLINE
        $this->assertFalse(PlantLogic::isOnline(null, $now), 'Perangkat offline jika last_seen null');
    }
}
