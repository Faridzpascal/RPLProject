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
}
