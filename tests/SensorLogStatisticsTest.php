<?php
/**
 * File Unit Test 4: SensorLogStatisticsTest
 * Menguji User Story 4 (US-4): Riwayat Log Sensor & Agregat Statistik
 * Smart Plant Dashboard - Tugas RPL Pertemuan 5
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/PlantLogic.php';

class SensorLogStatisticsTest extends TestCase {

    /**
     * AC-4.1: Kalkulasi statistik (rata-rata, min, max, count) dari 10 log data
     */
    public function testAggregateStatisticsCalculation(): void {
        $dummyMoistures = [48.5, 46.2, 44.0, 41.8, 38.5, 35.1, 31.4, 28.0, 55.4, 52.8];
        $stats = PlantLogic::calculateStats($dummyMoistures);

        $this->assertEquals(10, $stats['count'], 'Jumlah data harus 10');
        $this->assertEquals(28.0, $stats['min'], 'Nilai minimum harus 28.0%');
        $this->assertEquals(55.4, $stats['max'], 'Nilai maksimum harus 55.4%');
        $this->assertEquals(42.2, $stats['avg'], 'Rata-rata harus tepat 42.2%');
    }

    /**
     * AC-4.2: Penanganan dataset log kosong (zero state)
     */
    public function testStatisticsWithEmptyDataset(): void {
        $stats = PlantLogic::calculateStats([]);
        $this->assertEquals(0, $stats['count'], 'Count dataset kosong harus 0');
        $this->assertEquals(0.0, $stats['avg'], 'Rata-rata dataset kosong harus 0.0');
        $this->assertEquals(0.0, $stats['min'], 'Nilai min dataset kosong harus 0.0');
        $this->assertEquals(0.0, $stats['max'], 'Nilai max dataset kosong harus 0.0');
    }

    /**
     * AC-4.3: Pemfilteran dan pembatasan 10 riwayat log sensor terbaru
     */
    public function testFilterRecentLogsLimit(): void {
        $logs = [];
        for ($i = 1; $i <= 25; $i++) {
            $logs[] = [
                'id' => $i,
                'moisture_level' => 30.0 + $i,
                'created_at' => date('Y-m-d H:i:s', time() - (25 - $i) * 60)
            ];
        }

        $filtered = PlantLogic::filterRecentLogs($logs, 10);
        $this->assertEquals(10, count($filtered), 'Hasil filter harus dibatasi tepat 10 log');
        
        // Log teratas harus yang paling baru (id: 25)
        $this->assertEquals(25, $filtered[0]['id'], 'Log pertama harus merupakan log paling baru');
    }
}
