<?php
/**
 * Unit Test Suite: Fitur Autentikasi & Session Guard (Login, Session, Logout)
 * Menguji logika verifikasi hash password, status sesi, dan helper auth
 * RPL Project - Antigravity Test Harness
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/helpers/auth_check.php';

class AuthenticationTest extends TestCase {

    public function testPasswordHashVerificationWithDefaultCredentials(): void {
        $plainPassword = 'password123';
        // Hash bcrypt yang tersimpan di database.sql
        $storedHash = '$2y$12$FVRgDUmZukpReJZEGremT.PzzXSfs8HdPd00eftDO.KpvpXuQwQra';

        $isValid = password_verify($plainPassword, $storedHash);
        $this->assertTrue($isValid, "Password 'password123' harus cocok dengan hash di database.sql");

        $isInvalid = password_verify('salahpassword', $storedHash);
        $this->assertFalse($isInvalid, "Password yang salah tidak boleh diverifikasi");
    }

    public function testInitialSessionStateIsUnauthenticated(): void {
        startAppSession();
        $_SESSION = []; // Kosongkan session

        $this->assertFalse(isLoggedIn(), "Pengguna baru tanpa data session tidak boleh terautentikasi");
        $this->assertEquals(null, getAuthUser(), "getAuthUser() harus null jika belum login");
    }

    public function testSessionStateAfterSuccessfulLogin(): void {
        startAppSession();
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['login_time'] = time();

        $this->assertTrue(isLoggedIn(), "Pengguna dengan session valid harus terautentikasi");
        $user = getAuthUser();
        $this->assertEquals(1, $user['id'], "ID user harus 1");
        $this->assertEquals('admin', $user['username'], "Username harus 'admin'");
    }

    public function testSessionClearingOnLogout(): void {
        startAppSession();
        // Simulasi kondisi sedang login
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';

        // Simulasi logout
        $_SESSION = [];

        $this->assertFalse(isLoggedIn(), "Setelah sesi dikosongkan, pengguna tidak boleh lagi terautentikasi");
        $this->assertEquals(null, getAuthUser(), "Data user harus null setelah logout");
    }

    public function testCorsWhitelistRejectsUntrustedAndNullOrigins(): void {
        $this->assertTrue(isOriginAllowed('http://localhost:8000'), "Origin localhost:8000 harus diizinkan secara default");
        $this->assertTrue(isOriginAllowed('http://127.0.0.1:8000/'), "Origin dengan trailing slash harus dinormalisasi dan diizinkan");
        $this->assertFalse(isOriginAllowed('https://evil.example.com'), "Origin eksternal tidak terdaftar harus ditolak");
        $this->assertFalse(isOriginAllowed('null'), "Origin 'null' harus ditolak");
        $this->assertFalse(isOriginAllowed(''), "Origin kosong harus ditolak");
    }

    public function testCorsWhitelistRespectsAllowedOriginsEnv(): void {
        $previous = getenv('ALLOWED_ORIGINS');
        putenv('ALLOWED_ORIGINS=https://smartplant.example.com, https://app.example.com/');

        try {
            $this->assertTrue(isOriginAllowed('https://smartplant.example.com'), "Origin dari ALLOWED_ORIGINS harus diizinkan");
            $this->assertTrue(isOriginAllowed('https://app.example.com'), "Origin kedua dari ALLOWED_ORIGINS harus diizinkan");
            $this->assertFalse(isOriginAllowed('http://localhost:8000'), "Origin di luar ALLOWED_ORIGINS harus ditolak saat env diset");
            $this->assertFalse(isOriginAllowed('https://evil.example.com'), "Origin penyerang harus tetap ditolak");
        } finally {
            if ($previous === false) {
                putenv('ALLOWED_ORIGINS');
            } else {
                putenv("ALLOWED_ORIGINS={$previous}");
            }
        }
    }
}
