<?php
/**
 * Helper Rate Limiting Login (Anti-Brute-Force) berbasis file + flock()
 * Smart Plant Dashboard - University RPL IoT Project
 */

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_SECONDS = 300; // 5 menit

/**
 * Mengambil alamat IP client untuk kunci rate limiting.
 */
function getClientIpAddress(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
}

/**
 * Menentukan path file penyimpanan percobaan login yang aman.
 */
function getRateLimitFilePath(string $key, ?string $storageDir = null): string {
    $dir = $storageDir ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smartplant_ratelimit');
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
}

/**
 * Memeriksa apakah percobaan login saat ini masih diizinkan.
 *
 * @return array{allowed: bool, remaining: int, retry_after: int}
 */
function checkLoginRateLimit(
    string $key,
    int $maxAttempts = LOGIN_MAX_ATTEMPTS,
    int $windowSeconds = LOGIN_WINDOW_SECONDS,
    ?string $storageDir = null
): array {
    $file = getRateLimitFilePath($key, $storageDir);
    if (!is_file($file)) {
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
    }

    $raw = @file_get_contents($file);
    $timestamps = json_decode((string)$raw, true);
    if (!is_array($timestamps)) {
        return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
    }

    $now = time();
    $recent = array_values(array_filter(
        $timestamps,
        static fn($ts): bool => is_int($ts) && ($now - $ts) < $windowSeconds
    ));

    $count = count($recent);
    if ($count >= $maxAttempts) {
        $oldest = (int)min($recent);
        $retryAfter = max(1, $windowSeconds - ($now - $oldest));
        return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retryAfter];
    }

    return [
        'allowed'     => true,
        'remaining'   => max(0, $maxAttempts - $count),
        'retry_after' => 0,
    ];
}

/**
 * Mencatat 1 percobaan login gagal ke penyimpanan rate limit.
 */
function recordFailedLoginAttempt(
    string $key,
    int $windowSeconds = LOGIN_WINDOW_SECONDS,
    ?string $storageDir = null
): void {
    $file = getRateLimitFilePath($key, $storageDir);
    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        return;
    }

    if (flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $timestamps = json_decode((string)$raw, true);
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        $now = time();
        $recent = array_values(array_filter(
            $timestamps,
            static fn($ts): bool => is_int($ts) && ($now - $ts) < $windowSeconds
        ));
        $recent[] = $now;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($recent));
        fflush($fp);
        flock($fp, LOCK_UN);
    }

    fclose($fp);
}

/**
 * Menghapus catatan percobaan gagal setelah login berhasil.
 */
function clearLoginRateLimit(string $key, ?string $storageDir = null): void {
    $file = getRateLimitFilePath($key, $storageDir);
    if (is_file($file)) {
        @unlink($file);
    }
}

