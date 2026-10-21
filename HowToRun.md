# 🌱 Panduan Menjalankan MVP Smart Plant Dashboard
> **Sistem Monitoring Kelembapan Tanah & Otomatisasi Kontrol Pompa Berbasis IoT**  
> *Mata Kuliah Rekayasa Perangkat Lunak (RPL) - Final MVP Release*

---

## 📌 Ringkasan Proyek
Aplikasi web Internet of Things (IoT) untuk pemantauan kelembapan tanah tanaman dan kontrol penyiraman otomatis real-time. Dibangun menggunakan arsitektur **Single Page Application (SPA)** dengan REST API PHP Native PDO, database MySQL relasional, dan integrasi mikrokontroler ESP32.

---

## 💻 Prasyarat Sistem
Pastikan perangkat Anda telah terinstal salah satu lingkungan berikut:
1. **Web Server & Database:**
   - **XAMPP** (Apache & MySQL v8.0+) **ATAU**
   - **Laragon** (Apache/Nginx & MySQL) **ATAU**
   - **PHP CLI v8.0+** & MySQL standalone.
2. **Web Browser:** Google Chrome, Microsoft Edge, atau Mozilla Firefox versi terbaru.
3. **(Opsional Hardware IoT):** ESP32 Dev Module, Capacitive Soil Moisture Sensor, Modul Relay 1-Channel 5V, Mini Submersible Water Pump 5V DC.

---

## 🚀 Langkah Menjalankan Aplikasi Web

### Opsi 1: Menggunakan XAMPP (Metode Standar Praktikum)
1. **Jalankan Service Apache & MySQL:**
   - Buka aplikasi **XAMPP Control Panel**.
   - Klik tombol **Start** pada modul **Apache** dan modul **MySQL** hingga indikator berwarna hijau.

2. **Import Database `smart_plant_db`:**
   - Buka browser ke alamat: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Klik menu **Import** pada bar navigasi atas.
   - Klik **Choose File** dan pilih file `database.sql` yang berada di folder utama proyek.
   - Gulir ke bawah lalu klik **Import / Go**. Database `smart_plant_db` beserta tabel `devices` dan `sensor_logs` serta data awal dummy akan dibuat secara otomatis.

3. **Pastikan Folder Proyek Berada di `htdocs`:**
   - Folder proyek berada di direktori `htdocs`, contoh:
     `C:\xampp\htdocs\RPLProject` atau `C:\xampp\htdocs\smart-plant-rpl`

4. **Buka Web Dashboard:**
   - Buka browser dan akses salah satu URL berikut:
     ```
     http://localhost/RPLProject/
     ```
     *(atau `http://localhost/smart-plant-rpl/` jika nama folder adalah smart-plant-rpl)*
   - Halaman utama web dashboard akan langsung terbuka dengan tampilan real-time.

---

### Opsi 2: Menggunakan PHP Built-in Server (Cepat & Praktis)
Jika Anda tidak ingin menyalin folder ke `htdocs`:
1. Pastikan service MySQL sudah aktif di XAMPP / Laragon (port 3306).
2. Buka Terminal / PowerShell di folder proyek ini:
   ```powershell
   php -S 0.0.0.0:8000
   ```
3. Buka browser dan akses:
   ```
   http://localhost:8000/
   ```

---

### Opsi 3: Mode Pratinjau Interaktif (Tanpa Database)
Aplikasi telah dilengkapi dengan fitur **Resilient Demo Fallback**:
1. Buka file `frontend/index.html` langsung di browser Anda (klik ganda dari File Explorer).
2. Dashboard otomatis mengaktifkan mode simulasi visual: menampilkan kartu metrik realistis, progress bar, grafik, dan tabel riwayat.
3. Anda tetap dapat menguji tombol toggle switch mode `AUTO` / `MANUAL` dan tombol kendali pompa secara interaktif dengan notifikasi visual toast.

---

## 🧪 Cara Menjalankan Seluruh Unit Test (Wajib All Passed)

Proyek ini dilengkapi dengan test harness mandiri (*zero-dependency*) yang menguji seluruh 5 User Stories inti bisnis proyek.

### Metode 1: Melalui Terminal / CLI (Cepat)
Buka Terminal / PowerShell di folder utama proyek, lalu ketik:
```powershell
php tests/run_tests.php
```
*Output akan menampilkan seluruh pengujian dari 5 test suite dengan status **✔ PASS** dan ringkasan **ALL PASSED (100% LULUS)**.*

### Metode 2: Melalui Browser (Visual Interactive Test Runner)
1. Buka browser ke alamat:
   ```
   http://localhost/RPLProject/tests/
   ```
   *(atau buka langsung file `tests/index.html` di browser)*
2. Anda juga dapat mengklik tombol **"Unit Tests"** pada navbar atas web dashboard.
3. Layar akan menampilkan kartu metrik pengujian, waktu eksekusi, serta rincian assertion setiap User Story dengan badge hijau **PASSED**.

---

## 🎬 Skenario Demonstrasi Fitur (Demo 3 Menit ke Dosen)

| No | Fitur yang Didemokan | Langkah Tindakan | Hasil yang Diharapkan |
| :---: | :--- | :--- | :--- |
| **1** | **Unit Testing Otomatis** | Jalankan `php tests/run_tests.php` atau buka `tests/index.html` | Seluruh 5 suite (20 test cases, 58 assertions) berstatus **ALL PASSED**. |
| **2** | **Monitoring Kelembapan Real-time** | Buka halaman dashboard utama | Nilai kelembapan tampil (contoh: 52.8%), progress bar berwarna hijau/ideal, status tanah "Lembab". |
| **3** | **Aturan Keamanan Pompa (Mode AUTO)** | Coba klik tombol "Nyalakan Pompa" saat mode AUTO aktif | Tombol dalam kondisi terkunci (*disabled*) dan muncul pesan info bahwa otomasi sedang aktif. |
| **4** | **Kontrol Manual Pompa** | Geser switch ke **Mode MANUAL**, lalu klik tombol **"Nyalakan Pompa"** | Tombol pompa menjadi aktif, kartu pompa berubah hijau (**ON**), dan notifikasi toast sukses muncul. |
| **5** | **Riwayat & Statistik Sensor** | Periksa tabel log sensor dan kartu ringkasan | Tampil 10 log data pembacaan sensor terbaru beserta nilai Rata-rata, Min, dan Max. |
| **6** | **Auto-Refresh SPA 5 Detik** | Amati indikator timer countdown di atas | Angka countdown menghitung mundur (5s, 4s, ... 1s) lalu memperbarui data secara asinkron tanpa reload halaman. |

---

## 📂 Struktur File Utama Proyek
```plaintext
RPLProject/
├── backend/
│   ├── api/
│   │   ├── get_dashboard.php     # Endpoint agregat dashboard frontend SPA
│   │   ├── get_status.php        # Endpoint query status pompa & mode untuk ESP32
│   │   ├── update_mode.php       # Endpoint POST perubahan mode AUTO / MANUAL
│   │   ├── update_pump.php       # Endpoint POST kontrol pompa manual ON / OFF
│   │   └── update_sensor.php     # Endpoint POST pengiriman data kelembapan ESP32
│   ├── config/
│   │   └── database.php          # Konfigurasi koneksi MySQL PDO Native
│   └── helpers/
│       └── PlantLogic.php        # Logika bisnis terpusat (domain rules & sanitasi)
├── frontend/
│   ├── css/
│   │   └── style.css             # Tema styling custom tanaman & micro-animations
│   ├── js/
│   │   └── app.js                # Logika SPA, AJAX fetch polling 5s, demo fallback
│   └── index.html                # Antarmuka dashboard utama pengguna
├── tests/
│   ├── AutoWateringLogicTest.php # Unit Test US-2: Otomasi siram mode AUTO & histeresis
│   ├── DeviceConnectivityTest.php# Unit Test US-5: Heartbeat timeout & validasi MAC
│   ├── PumpControlTest.php       # Unit Test kontrol pompa terintegrasi
│   ├── PumpSafetyGuardTest.php   # Unit Test US-3: Guard rule & validasi kontrol pompa
│   ├── SensorLogStatisticsTest.php# Unit Test US-4: Statistik rata-rata, min, max, limit
│   ├── SensorMonitoringTest.php  # Unit Test US-1: Klasifikasi kelembapan tanah
│   ├── SoilMoistureClassificationTest.php # Unit Test US-1 lanjutan & boundary test
│   ├── TestCase.php              # Base framework assertion mandiri
│   ├── index.html                # Visual in-browser interactive unit test runner
│   └── run_tests.php             # CLI script test runner
├── esp32/
│   └── smart_plant.ino           # Firmware Arduino C++ mikrokontroler ESP32
├── database.sql                  # Skrip pembuatan tabel database & data awal dummy
├── HowToRun.md                   # Petunjuk lengkap menjalankan proyek
├── checklist_mvp.md              # Matriks kelengkapan fitur & hasil verifikasi testing
├── index.php                     # Redirector otomatis ke frontend/index.html
└── README.md                     # Dokumentasi arsitektur proyek
```
