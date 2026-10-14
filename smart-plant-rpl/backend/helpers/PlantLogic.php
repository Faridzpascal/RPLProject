<?php
/**
 * PlantLogic - Core Business Logic Helper
 * Kelas logika bisnis terpusat untuk unit testing dan backend API
 * Smart Plant Dashboard - Rekayasa Perangkat Lunak (RPL)
 */

class PlantLogic {
    const MOISTURE_THRESHOLD_DRY = 35.0;
    const MOISTURE_THRESHOLD_WET = 70.0;
    const ONLINE_TIMEOUT_SECONDS = 30;

    /**
     * Membatasi nilai kelembapan antara 0.0% dan 100.0%
     */
    public static function clampMoisture(float $moisture): float {
        if ($moisture < 0.0) return 0.0;
        if ($moisture > 100.0) return 100.0;
        return round($moisture, 1);
    }

    /**
     * US-1: Mengklasifikasikan kondisi tanah berdasarkan persentase kelembapan
     * @return array [label, colorClass, statusKey]
     */
    public static function classifyMoisture(float $moisture): array {
        $clamped = self::clampMoisture($moisture);

        if ($clamped < self::MOISTURE_THRESHOLD_DRY) {
            return [
                'statusKey'   => 'DRY',
                'label'       => 'Kering (Perlu Disiram)',
                'badgeClass'  => 'text-danger',
                'bgClass'     => 'bg-danger'
            ];
        } elseif ($clamped <= self::MOISTURE_THRESHOLD_WET) {
            return [
                'statusKey'   => 'IDEAL',
                'label'       => 'Lembab (Kondisi Ideal)',
                'badgeClass'  => 'text-success',
                'bgClass'     => 'bg-success'
            ];
        } else {
            return [
                'statusKey'   => 'SATURATED',
                'label'       => 'Sangat Basah / Jenuh',
                'badgeClass'  => 'text-info',
                'bgClass'     => 'bg-info'
            ];
        }
    }

    /**
     * US-2: Logika otomatisasi siram pada Mode AUTO
     * Mengembalikan 'ON' jika tanah kering (< 35%), 'OFF' jika sudah cukup lembab
     */
    public static function evaluateAutoPump(string $mode, float $moisture, float $threshold = self::MOISTURE_THRESHOLD_DRY): string {
        if (strtoupper(trim($mode)) !== 'AUTO') {
            return 'OFF';
        }

        return ($moisture < $threshold) ? 'ON' : 'OFF';
    }

    /**
     * US-2: Evaluasi Histeresis Siram (mencegah pompa on/off terlalu cepat saat mendekati ambang)
     */
    public static function evaluateHysteresis(string $mode, float $moisture, string $currentPumpStatus, float $turnOnThreshold = 35.0, float $turnOffThreshold = 45.0): string {
        if (strtoupper(trim($mode)) !== 'AUTO') {
            return strtoupper(trim($currentPumpStatus)) === 'ON' ? 'ON' : 'OFF';
        }

        $clamped = self::clampMoisture($moisture);

        if ($clamped < $turnOnThreshold) {
            return 'ON';
        } elseif ($clamped >= $turnOffThreshold) {
            return 'OFF';
        } else {
            // Berada di zona histeresis (35% - 45%): pertahankan status pompa sebelumnya
            return strtoupper(trim($currentPumpStatus)) === 'ON' ? 'ON' : 'OFF';
        }
    }

    /**
     * US-3: Aturan Keamanan (Guard Rule):
     * Kontrol manual pompa HANYA diperbolehkan jika perangkat berada dalam Mode MANUAL
     */
    public static function canToggleManualPump(string $mode): bool {
        return (strtoupper(trim($mode)) === 'MANUAL');
    }

    /**
     * US-3: Validasi Mode Operasi yang valid
     */
    public static function isValidMode(string $mode): bool {
        return in_array(strtoupper(trim($mode)), ['AUTO', 'MANUAL'], true);
    }

    /**
     * US-3: Validasi Status Pompa yang valid
     */
    public static function isValidPumpStatus(string $status): bool {
        return in_array(strtoupper(trim($status)), ['ON', 'OFF'], true);
    }

    /**
     * US-4: Menghitung statistik ringkas dari sekumpulan nilai kelembapan
     */
    public static function calculateStats(array $moistureValues): array {
        if (empty($moistureValues)) {
            return [
                'avg'   => 0.0,
                'min'   => 0.0,
                'max'   => 0.0,
                'count' => 0
            ];
        }

        $filtered = array_map(function($val) {
            return self::clampMoisture((float)$val);
        }, $moistureValues);

        $count = count($filtered);
        $avg   = round(array_sum($filtered) / $count, 1);
        $min   = round(min($filtered), 1);
        $max   = round(max($filtered), 1);

        return [
            'avg'   => $avg,
            'min'   => $min,
            'max'   => $max,
            'count' => $count
        ];
    }

    /**
     * US-4: Menyortir dan membatasi log sensor terbaru (maksimal limit)
     */
    public static function filterRecentLogs(array $logs, int $limit = 10): array {
        if (empty($logs)) return [];

        // Urutkan descending berdasarkan created_at atau id jika ada
        usort($logs, function($a, $b) {
            $timeA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
            $timeB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
            return $timeB <=> $timeA;
        });

        return array_slice($logs, 0, $limit);
    }

    /**
     * US-5: Menentukan apakah perangkat ESP32 online berdasarkan timestamp last_seen
     */
    public static function isOnline($lastSeen, ?int $currentTime = null, int $thresholdSeconds = self::ONLINE_TIMEOUT_SECONDS): bool {
        if (empty($lastSeen)) return false;

        $lastSeenTimestamp = is_numeric($lastSeen) ? (int)$lastSeen : strtotime($lastSeen);
        if ($lastSeenTimestamp === false || $lastSeenTimestamp <= 0) return false;

        $now = $currentTime ?? time();
        $diff = $now - $lastSeenTimestamp;

        return ($diff >= 0 && $diff <= $thresholdSeconds);
    }

    /**
     * US-5: Validasi format alamat MAC mikrokontroler ESP32 (XX:XX:XX:XX:XX:XX)
     */
    public static function isValidMacAddress(string $mac): bool {
        $trimmed = trim($mac);
        return (bool)preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $trimmed);
    }

    /**
     * US-5: Menghitung selisih detik waktu koneksi terakhir
     */
    public static function getSecondsSinceLastSeen($lastSeen, ?int $currentTime = null): int {
        if (empty($lastSeen)) return 999999;
        $timestamp = is_numeric($lastSeen) ? (int)$lastSeen : strtotime($lastSeen);
        if ($timestamp === false || $timestamp <= 0) return 999999;
        $now = $currentTime ?? time();
        return max(0, $now - $timestamp);
    }
}
