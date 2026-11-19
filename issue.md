# Daftar Issues & Bug Setelah Deploy — Smart Plant Dashboard IoT

Dokumen ini berisi daftar temuan bug, celah keamanan, serta permasalahan teknis & UX yang muncul saat aplikasi **Smart Plant Dashboard** dideploy. Setiap poin diformat agar siap dimasukkan ke **GitHub Issues**.

---

## Issue #1: Tautan & Akses Unit Test Runner Masih Tampil di Dashboard Monitoring Produksi
- **Labels:** `bug`, `ux`, `security`, `frontend`
- **Severity:** Medium
- **Lokasi File:**
  - `frontend/index.html` (navbar link `../tests/index.html`)
  - `frontend/login.html` (footer link `../tests/index.html`)
- **Deskripsi Masalah:**
  Pada halaman dashboard monitoring dan halaman login, tombol/tautan **Unit Tests** (`../tests/index.html`) masih ditampilkan secara terbuka kepada pengguna akhir. Pada lingkungan produksi setelah deploy, fitur pengujian internal/developer tidak seharusnya muncul di antarmuka monitoring maupun dapat diakses publik.
- **Dampak Setelah Deploy:**
  - Mengganggu tampilan UX dashboard monitoring dengan menu yang tidak relevan bagi pengguna.
  - Mengekspos detail struktur logika bisnis dan test suite internal ke publik.
- **Rekomendasi Perbaikan:**
  Hapus tautan Unit Tests dari `frontend/index.html` dan `frontend/login.html`, serta batasi akses folder `tests/` di server produksi (misalnya via `.htaccess` atau konfigurasi web server).

---

## Issue #2: Kredensial Login Default & Tombol "Autofill" Terbuka di Halaman Login Publik
- **Labels:** `security`, `critical`, `frontend`
- **Severity:** Critical
- **Lokasi File:**
  - `frontend/login.html` (kotak *Akun Pengujian Default* & `#btnAutofillDemo`)
  - `frontend/js/app.js` (handler `btnAutofillDemo`)
- **Deskripsi Masalah:**
  Halaman `login.html` menampilkan username (`admin`) dan password (`password123`) secara terang-terangan beserta tombol **Autofill** yang otomatis mengisi form login.
- **Dampak Setelah Deploy:**
  Siapa pun yang mengakses URL aplikasi di internet dapat langsung masuk ke dashboard dan mengendalikan pompa air IoT tanpa otorisasi.
- **Rekomendasi Perbaikan:**
  Hapus kotak informasi akun demo dan tombol autofill dari `frontend/login.html` serta logikanya di `app.js` untuk build produksi, dan wajibkan penggantian password default.

---

## Issue #3: CORS Misconfiguration (Reflected Origin + Credentials Allowed) Membuka Celah CSRF/Data Leak
- **Labels:** `security`, `critical`, `backend`
- **Severity:** Critical
- **Lokasi File:**
  - `backend/helpers/auth_check.php` (`sendCorsHeaders()`, baris 14–25)
- **Deskripsi Masalah:**
  Fungsi `sendCorsHeaders()` memantulkan nilai `$_SERVER['HTTP_ORIGIN']` secara langsung ke header `Access-Control-Allow-Origin` sekaligus mengirimkan `Access-Control-Allow-Credentials: true` tanpa memvalidasi whitelist domain.
- **Dampak Setelah Deploy:**
  Situs web berbahaya mana pun dapat melakukan request lintas-domain (*cross-origin*) menggunakan cookie sesi pengguna yang sedang login untuk membaca data dashboard atau menyalakan/mematikan pompa air secara diam-diam.
- **Rekomendasi Perbaikan:**
  Terapkan whitelist domain (`ALLOWED_ORIGINS`) yang diizinkan, dan tolak/jangan kirim `Access-Control-Allow-Credentials: true` untuk origin yang tidak terdaftar.

---

## Issue #4: Endpoint ESP32 (`update_sensor.php` & `get_status.php`) Tidak Memiliki Autentikasi / API Key
- **Labels:** `security`, `high`, `backend`, `iot`
- **Severity:** High
- **Lokasi File:**
  - `backend/api/update_sensor.php`
  - `backend/api/get_status.php`
  - `esp32/smart_plant.ino`
- **Deskripsi Masalah:**
  Kedua endpoint yang berkomunikasi dengan ESP32 terbuka bebas tanpa header autentikasi (`X-API-Key` / device token). Selain itu, `update_sensor.php` otomatis melakukan `INSERT INTO devices` jika `mac_address` belum terdaftar.
- **Dampak Setelah Deploy:**
  - Pihak luar dapat mengirim data kelembapan palsu (spoofing) sehingga memicu pompa menyala/mati secara tidak sah.
  - Database dapat dipenuhi perangkat palsu (*spam device registration*).
- **Rekomendasi Perbaikan:**
  Tambahkan validasi `X-API-Key` / bearer token perangkat di `update_sensor.php` dan `get_status.php`, serta kirimkan header tersebut dari `esp32/smart_plant.ino`.

---

## Issue #5: Kolom `devices.last_seen` Menggunakan `ON UPDATE CURRENT_TIMESTAMP` Sehingga Status Online ESP32 Menjadi Palsu
- **Labels:** `bug`, `logic`, `database`, `iot`
- **Severity:** High
- **Lokasi File:**
  - `database.sql` (tabel `devices` baris 23)
  - `backend/api/update_mode.php`
  - `backend/api/update_pump.php`
- **Deskripsi Masalah:**
  Kolom `last_seen` didefinisikan sebagai `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. Setiap kali pengguna web mengubah `operating_mode` atau `pump_status` melalui dashboard, MySQL otomatis memperbarui `last_seen` ke waktu sekarang.
- **Dampak Setelah Deploy:**
  Dashboard akan menampilkan status **"ESP32 Terhubung"** padahal perangkat ESP32 sebenarnya sedang mati atau terputus dari jaringan.
- **Rekomendasi Perbaikan:**
  Hapus `ON UPDATE CURRENT_TIMESTAMP` pada kolom `last_seen` di `database.sql`. Pastikan `last_seen` hanya diperbarui secara eksplisit oleh endpoint yang dipanggil ESP32 (`update_sensor.php` dan `get_status.php`).

---

## Issue #6: Kebocoran Pesan Error Database Mentah ke Respons JSON Publik (Information Disclosure)
- **Labels:** `security`, `backend`
- **Severity:** Medium
- **Lokasi File:**
  - `backend/config/database.php` (baris 39)
  - `backend/api/login.php`, `get_dashboard.php`, `update_mode.php`, `update_pump.php`, `update_sensor.php`, `get_status.php`
- **Deskripsi Masalah:**
  Ketika terjadi `PDOException`, pesan error mentah dari `$e->getMessage()` langsung dikembalikan dalam respons JSON ke client.
- **Dampak Setelah Deploy:**
  Membocorkan detail internal seperti host database, nama database, nama tabel/kolom, atau konfigurasi server kepada penyerang.
- **Rekomendasi Perbaikan:**
  Catat detail exception ke server error log (`error_log($e->getMessage())`) dan kirim pesan error umum yang aman ke client (misalnya *"Terjadi kesalahan pada server."*).

---

## Issue #7: Kredensial Database Hardcoded & Tidak Ada Dukungan Konfigurasi Environment (`.env`)
- **Labels:** `deployment`, `configuration`, `backend`
- **Severity:** High
- **Lokasi File:**
  - `backend/config/database.php` (baris 10–14)
- **Deskripsi Masalah:**
  Konfigurasi koneksi MySQL (`host = 127.0.0.1`, `db_name = smart_plant_db`, `username = root`, `password = ""`) di-hardcode langsung di dalam class `Database`.
- **Dampak Setelah Deploy:**
  Saat dideploy ke server produksi, VPS, atau shared hosting dengan kredensial berbeda, developer harus mengubah file kode sumber secara manual dan berisiko kredensial produksi ikut ter-commit ke Git.
- **Rekomendasi Perbaikan:**
  Gunakan environment variables / file konfigurasi `.env` (yang di-ignore oleh `.gitignore`) dengan fallback default untuk lokal.

---

## Issue #8: Skrip `database.sql` Tidak Membuat Database (`CREATE DATABASE`) Secara Otomatis
- **Labels:** `bug`, `deployment`, `database`
- **Severity:** Medium
- **Lokasi File:**
  - `database.sql` (baris 1–14)
- **Deskripsi Masalah:**
  File `database.sql` langsung menjalankan `DROP TABLE` dan `CREATE TABLE` tanpa perintah `CREATE DATABASE IF NOT EXISTS smart_plant_db;` dan `USE smart_plant_db;`.
- **Dampak Setelah Deploy:**
  Saat deployment baru atau import via CLI/phpMyAdmin tanpa memilih database lebih dulu, proses import akan gagal dengan error *No database selected*.
- **Rekomendasi Perbaikan:**
  Tambahkan `CREATE DATABASE IF NOT EXISTS \`smart_plant_db\` ...;` dan `USE \`smart_plant_db\`;` di bagian awal `database.sql` (atau sediakan instruksi/skrip migrasi yang jelas).

---

## Issue #9: Tidak Ada Validasi Format MAC Address pada Endpoint `update_sensor.php`
- **Labels:** `bug`, `validation`, `backend`
- **Severity:** Medium
- **Lokasi File:**
  - `backend/api/update_sensor.php` (baris 38–48)
  - `backend/helpers/PlantLogic.php` (`isValidMacAddress()`)
- **Deskripsi Masalah:**
  Helper `PlantLogic::isValidMacAddress()` sudah tersedia di codebase, tetapi `update_sensor.php` hanya memeriksa `!empty($mac_address)`. String acak apa pun dapat dikirim dan langsung dibuatkan baris baru di tabel `devices`.
- **Dampak Setelah Deploy:**
  Data device di database menjadi kotor/tidak konsisten akibat request invalid atau bot yang mengirim `mac_address` sembarangan.
- **Rekomendasi Perbaikan:**
  Panggil `PlantLogic::isValidMacAddress($mac_address)` di `update_sensor.php` dan `get_status.php`, lalu kembalikan HTTP `400 Bad Request` jika format tidak valid.

---

## Issue #10: Tidak Ada Rate Limiting pada Endpoint `login.php` (Rentan Brute Force)
- **Labels:** `security`, `backend`
- **Severity:** High
- **Lokasi File:**
  - `backend/api/login.php`
- **Deskripsi Masalah:**
  Endpoint `login.php` tidak membatasi jumlah percobaan login gagal per IP atau per username.
- **Dampak Setelah Deploy:**
  Penyerang dapat menjalankan serangan *brute-force* atau *credential stuffing* tanpa batas terhadap akun dashboard.
- **Rekomendasi Perbaikan:**
  Terapkan pembatasan percobaan login (rate limiting / temporary lockout setelah N kali gagal dalam periode waktu tertentu).

---

## Issue #11: Firmware ESP32 Masih Menggunakan URL HTTP Lokal Hardcoded & Tanpa Timeout Request
- **Labels:** `bug`, `deployment`, `iot`, `esp32`
- **Severity:** High
- **Lokasi File:**
  - `esp32/smart_plant.ino` (baris 26, dan pemanggilan `HTTPClient`)
- **Deskripsi Masalah:**
  `SERVER_BASE_URL` di `smart_plant.ino` masih mengarah ke IP lokal `http://192.168.1.10/smart-plant-rpl/backend/api` (bahkan nama path `smart-plant-rpl` tidak konsisten dengan folder `RPLProject`). Selain itu, belum menggunakan `https://` (`WiFiClientSecure`) dan tidak mengatur `http.setTimeout()`.
- **Dampak Setelah Deploy:**
  - Perangkat ESP32 gagal terhubung ke server produksi.
  - Tanpa timeout HTTP, loop ESP32 dapat macet (*blocking*) cukup lama saat server produksi mengalami gangguan jaringan.
- **Rekomendasi Perbaikan:**
  Pisahkan konfigurasi WiFi/URL server ke header terpisah (`secrets.h`), dukung HTTPS, dan tambahkan `http.setTimeout(5000)`.

---

## Issue #12: Bypass Tampilan Dashboard ke "Mode Demo" Saat User Belum Login Jika Backend Mengembalikan Error 500/Offline
- **Labels:** `bug`, `security`, `ux`, `frontend`
- **Severity:** Medium
- **Lokasi File:**
  - `frontend/js/app.js` (`DOMContentLoaded`, `checkAuthSession()`, `fetchDashboardData()`, `handleOfflineFallback()`)
- **Deskripsi Masalah:**
  Saat `index.html` dibuka, `checkAuthSession()` dan `fetchDashboardData()` dijalankan bersamaan secara asinkron. Jika koneksi ke backend gagal atau database mengembalikan HTTP 500, `handleOfflineFallback()` langsung merender dashboard dengan data simulasi (`FALLBACK_DEMO_DATA`) tanpa memastikan user sudah terautentikasi terlebih dahulu.
- **Dampak Setelah Deploy:**
  Ketika database sempat down atau lambat setelah deploy, pengunjung yang belum login dapat melihat tampilan dashboard penuh (dengan banner demo) alih-alih diarahkan ke `login.html`.
- **Rekomendasi Perbaikan:**
  Tunggu hasil `checkAuthSession()` sebelum memanggil `fetchDashboardData()`, dan batasi fallback demo hanya untuk mode development/offline lokal, bukan saat server produksi mengembalikan error 500.

---

## Issue #13: Grafik Chart.js Di-Destroy & Dibuat Ulang Setiap 5 Detik (Flickering UX)
- **Labels:** `bug`, `ux`, `performance`, `frontend`
- **Severity:** Low
- **Lokasi File:**
  - `frontend/js/app.js` (`renderMoistureChart()`)
- **Deskripsi Masalah:**
  Setiap kali `fetchDashboardData()` berjalan (tiap 5 detik), `renderMoistureChart()` memanggil `moistureChartInstance.destroy()` lalu membuat ulang `new Chart(...)` dari awal.
- **Dampak Setelah Deploy:**
  Grafik berkedip dan mengulang animasi render setiap 5 detik, mengganggu kenyamanan pemantauan real-time dan membebani CPU browser jika dashboard dibuka lama.
- **Rekomendasi Perbaikan:**
  Perbarui `chart.data.labels` dan `chart.data.datasets` lalu panggil `moistureChartInstance.update('none')` jika instance chart sudah ada.

---

## Issue #14: Pertumbuhan Data `sensor_logs` Tanpa Batas (Unbounded Table Growth)
- **Labels:** `performance`, `database`, `iot`
- **Severity:** Medium
- **Lokasi File:**
  - `esp32/smart_plant.ino` (`INTERVAL_SEND_SENSOR = 5000`)
  - `backend/api/update_sensor.php`
  - `database.sql`
- **Deskripsi Masalah:**
  ESP32 mengirim data sensor setiap 5 detik sekali (~17.280 baris/hari atau >500.000 baris/bulan per perangkat) dan semuanya disimpan permanen ke `sensor_logs` tanpa mekanisme retensi/pembersihan data lama atau pemisahan interval penyimpanan.
- **Dampak Setelah Deploy:**
  Ukuran database membengkak cepat, query `ORDER BY id DESC` pada perangkat dengan banyak histori melambat, dan kuota penyimpanan hosting cepat habis.
- **Rekomendasi Perbaikan:**
  Tambahkan mekanisme pembersihan/arsip log lama secara berkala (retensi data, misal simpan log detail 7–30 hari terakhir) atau atur interval penyimpanan log lebih efisien.

