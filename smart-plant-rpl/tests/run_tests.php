<?php
/**
 * Test Runner CLI Script (All 5 Unit Test Suites)
 * Menjalankan seluruh Unit Test untuk 5 User Stories MVP Smart Plant Dashboard
 * Tugas Pertemuan 5 RPL
 * 
 * Penggunaan: php tests/run_tests.php
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/SoilMoistureClassificationTest.php';
require_once __DIR__ . '/AutoWateringLogicTest.php';
require_once __DIR__ . '/PumpSafetyGuardTest.php';
require_once __DIR__ . '/SensorLogStatisticsTest.php';
require_once __DIR__ . '/DeviceConnectivityTest.php';

echo "\n" . str_repeat("=", 75) . "\n";
echo " 🌱 SMART PLANT DASHBOARD - 5 MVP UNIT TEST SUITES (PERTEMUAN 5 RPL)\n";
echo str_repeat("=", 75) . "\n\n";

$startTime = microtime(true);
$suites = [
    'US-1: Monitoring Kelembapan & Klasifikasi Tanah'  => new SoilMoistureClassificationTest(),
    'US-2: Otomatisasi Penyiraman Cerdas (Mode AUTO)' => new AutoWateringLogicTest(),
    'US-3: Kontrol Pompa Manual & Safety Guard'       => new PumpSafetyGuardTest(),
    'US-4: Riwayat Log Sensor & Agregat Statistik'    => new SensorLogStatisticsTest(),
    'US-5: Deteksi Heartbeat & Status Konektivitas'   => new DeviceConnectivityTest()
];

$totalTestMethods = 0;
$totalAssertions = 0;
$totalPassed = 0;
$totalFailed = 0;
$allFailures = [];

foreach ($suites as $suiteName => $testInstance) {
    echo "▶ Menjalankan Suite: [{$suiteName}]\n";
    $reflection = new ReflectionClass($testInstance);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    $testMethods = array_filter($methods, function($method) {
        return str_starts_with($method->name, 'test');
    });

    foreach ($testMethods as $method) {
        $totalTestMethods++;
        $methodName = $method->name;
        $prevFailed = $testInstance->getFailedCount();

        $testInstance->$methodName();

        $currentFailed = $testInstance->getFailedCount();
        if ($currentFailed === $prevFailed) {
            echo "   ✔ PASS: {$methodName}\n";
        } else {
            echo "   ✘ FAIL: {$methodName}\n";
        }
    }

    $totalAssertions += $testInstance->getAssertionsCount();
    $totalPassed += $testInstance->getPassedCount();
    $totalFailed += $testInstance->getFailedCount();

    foreach ($testInstance->getFailures() as $failure) {
        $allFailures[] = array_merge(['suite' => $suiteName], $failure);
    }

    echo "\n";
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);

echo str_repeat("-", 75) . "\n";
echo "📊 RINGKASAN HASIL PENGUJIAN 5 UNIT TEST SUITES:\n";
echo "   Total Test Suites: 5 Suites\n";
echo "   Total Test Cases : {$totalTestMethods} Test Cases\n";
echo "   Total Assertions : {$totalAssertions} Assertions\n";
echo "   Lulus (PASSED)   : {$totalPassed}\n";
echo "   Gagal (FAILED)   : {$totalFailed}\n";
echo "   Waktu Eksekusi   : {$elapsed} ms\n";
echo str_repeat("-", 75) . "\n";

if ($totalFailed === 0) {
    echo "🎉 STATUS: ALL PASSED! 100% ACCEPTANCE CRITERIA 5 USER STORIES TERPENUHI\n\n";
    exit(0);
} else {
    echo "⚠️ STATUS: ADA TEST YANG GAGAL!\n";
    foreach ($allFailures as $index => $failure) {
        echo "   " . ($index + 1) . ") [{$failure['suite']}] {$failure['message']}\n";
        echo "      Expected: " . var_export($failure['expected'], true) . "\n";
        echo "      Actual  : " . var_export($failure['actual'], true) . "\n";
    }
    echo "\n";
    exit(1);
}
