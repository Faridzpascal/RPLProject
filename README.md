# 🌱 Smart Plant Dashboard - Sistem Monitoring & Kontrol IoT
> **Aplikasi Monitoring Kelembapan Tanah & Otomatisasi Penyiraman Berbasis IoT**  
> *Mata Kuliah Rekayasa Perangkat Lunak (RPL) - Final MVP Release*

[![Unit Tests](https://img.shields.io/badge/Unit%20Tests-58%20Passed%20(100%25)-success?style=flat-square&logo=php)](tests/index.html)
[![Architecture](https://img.shields.io/badge/Architecture-REST%20API%20%2B%20SPA-blue?style=flat-square)](backend/api/)
[![PHP](https://img.shields.io/badge/Backend-PHP%208%20Native%20PDO-777BB4?style=flat-square&logo=php)](backend/)
[![Frontend](https://img.shields.io/badge/Frontend-HTML5%20%7C%20CSS3%20%7C%20Bootstrap%205-E34F26?style=flat-square&logo=bootstrap)](frontend/)
[![Hardware](https://img.shields.io/badge/Hardware-ESP32%20%2B%20Soil%20Sensor-00979D?style=flat-square&logo=arduino)](esp32/)

Aplikasi web Internet of Things (IoT) yang andal, responsif, dan terstandarisasi untuk memantau kelembapan tanah tanaman secara *real-time* dan mengendalikan pompa penyiraman otomatis. Dibangun menggunakan arsitektur **Single Page Application (SPA)** dengan REST API PHP Native PDO, database MySQL relasional, modul logika terisolasi, serta integrasi mikrokontroler ESP32.

---

## 📌 5 Fitur Inti MVP (User Stories)

1. **US-1: Monitoring Kelembapan & Klasifikasi Tanah Real-time**
   * Mengambil data persentase kelembapan tanah (0.0% – 100.0%) dari sensor kapasitif.
   * Mengklasifikasikan kondisi tanah secara otomatis:
     - 🔴 **Kering (< 35.0%)**: *Perlu Disiram*
     - 🟢 **Lembab (35.0% - 70.0%)**: *Kondisi Ideal*
     - 🔵 **Sangat Basah (> 70.0%)**: *Jenuh Air*
   * Dilengkapi sanitasi *clamping* untuk mencegah nilai input sensor yang tidak valid.

2. **US-2: Otomatisasi Penyiraman Cerdas (Mode AUTO & Histeresis)**
   * **Auto ON**: Pompa air otomatis menyala saat kelembapan tanah $< 35.0\%$.
   * **Auto OFF**: Pompa air otomatis mati saat kelembapan tanah mencapai $\ge 35.0\%$.
   * **Proteksi Histeresis**: Mencegah saklar relay hidup-mati terlalu cepat akibat fluktuasi air sesaat.

3. **US-3: Kontrol Pompa Manual & Aturan Keamanan (Safety Guard)**
   * **Mode MANUAL**: Pengguna dapat menyalakan atau mematikan pompa air langsung melalui tombol di dashboard web.
   * **Safety Guard Rule**: Tombol kontrol manual otomatis terkunci (*disabled*) saat sistem dalam Mode AUTO. Percobaan pengubahan manual saat mode AUTO wajib ditolak oleh API dengan status **HTTP 403 Forbidden**.

4. **US-4: Riwayat 10 Log Sensor & Agregat Statistik**
   * Menampilkan tabel 10 pembacaan sensor terbaru dengan indikator visual progress bar.
   * Menghitung nilai agregat statistik secara presisi: **Rata-rata (Average)**, **Nilai Terendah (Min)**, dan **Nilai Tertinggi (Max)**.
   * Penanganan *zero-state* yang aman jika belum ada data log (tanpa galat pembagian nol).

5. **US-5: Deteksi Heartbeat & Status Konektivitas ESP32 (Online / Offline)**
   * Mengetahui status aktif mikrokontroler ESP32 berdasarkan selisih waktu `last_seen` dengan waktu server.
   * Status **ONLINE** (badge hijau) jika heartbeat diterima $\le 30$ detik yang lalu.
   * Status **OFFLINE** (badge merah) jika selisih waktu $> 30$ detik atau data terputus.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3 kustom, Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript (Fetch API AJAX polling 5s).
* **Backend:** Native PHP 8 menggunakan PDO (Prepared Statements, bebas ORM) dan modul pembantu `PlantLogic.php`.
* **Database:** MySQL / MariaDB (Relasional 2 tabel: `devices` dan `sensor_logs`).
* **Mikrokontroler & Hardware:**
  * ESP32 Development Board (30 / 38 pin)
  * Capacitive Soil Moisture Sensor v1.2 / v2.0
  * Modul Relay 1-Channel (5V Active LOW)
  * Mini Submersible Water Pump 5V DC + Selang
  * Adaptor 5V / Power Supply eksternal & Breadboard
* **Testing:** Custom zero-dependency PHP unit test harness & In-browser visual test runner.

---

## 📁 Struktur Direktori Proyek

```plaintext
RPLProject/
├── backend/
│   ├── api/
│   │   ├── get_dashboard.php     # Endpoint GET gabungan untuk auto-refresh SPA
│   │   ├── get_status.php        # Endpoint GET query status pompa/mode untuk ESP32
│   │   ├── update_mode.php       # Endpoint POST perubahan mode AUTO / MANUAL
│   │   ├── update_pump.php       # Endpoint POST kontrol manual pompa ON / OFF
│   │   └── update_sensor.php     # Endpoint POST penerimaan data kelembapan ESP32
│   ├── config/
│   │   └── database.php          # Koneksi database MySQL dengan PDO Native
│   └── helpers/
│       └── PlantLogic.php        # Kelas logika bisnis terpusat & aturan domain
├── frontend/
│   ├── css/
│   │   └── style.css             # Tema desain custom tanaman, cards & micro-animations
│   ├── js/
│   │   └── app.js                # Logika SPA, AJAX polling 5s, toast & fallback demo
│   └── index.html                # Tampilan utama web dashboard (Bahasa Indonesia)
├── tests/
│   ├── AutoWateringLogicTest.php # Unit Test US-2: Logika siram otomatis & histeresis
│   ├── DeviceConnectivityTest.php# Unit Test US-5: Heartbeat online & validasi MAC
│   ├── PumpControlTest.php       # Unit Test kontrol pompa terintegrasi
│   ├── PumpSafetyGuardTest.php   # Unit Test US-3: Guard rule & validasi pompa
│   ├── SensorLogStatisticsTest.php# Unit Test US-4: Statistik rata-rata, min, max, count
│   ├── SensorMonitoringTest.php  # Unit Test US-1: Klasifikasi kelembapan tanah
│   ├── SoilMoistureClassificationTest.php # Unit Test US-1: Boundary condition testing
│   ├── TestCase.php              # Framework assertion mandiri (zero-dependency)
│   ├── index.html                # In-browser visual interactive unit test runner
│   └── run_tests.php             # CLI script test runner
├── esp32/
│   └── smart_plant.ino           # Firmware Arduino C++ ESP32 (WiFi & HTTPClient)
├── database.sql                  # Skrip SQL pembuatan tabel & data awal pengujian
├── HowToRun.md                   # Petunjuk lengkap menjalankan proyek
├── checklist_mvp.md              # Matriks kelengkapan fitur & bukti testing (PASSED)
├── index.php                     # Entry point redirector ke frontend/index.html
└── README.md                     # Dokumentasi arsitektur & panduan proyek
```

---

## 🔌 Skematik & Panduan Rangkaian Hardware

### 1. Pinout Rangkaian

| Komponen Hardware | Pin Komponen | Pin ESP32 / Sumber Daya | Catatan Penting |
| :--- | :--- | :--- | :--- |
| **Capacitive Soil Sensor** | `VCC` | ESP32 `3.3V` atau `VIN (5V)` | Disarankan 3.3V agar output analog $\le 3.3\text{V}$ |
| | `GND` | ESP32 `GND` | Ground bersama (*Common Ground*) |
| | `AOUT` (Analog Out) | ESP32 `GPIO 34` | Gunakan ADC1 (GPIO 34 aman dipakai bersama Wi-Fi) |
| **Modul Relay 1-Channel** | `VCC` | ESP32 `VIN` (5V) | Suplai tegangan modul relay |
| | `GND` | ESP32 `GND` | Ground bersama (*Common Ground*) |
| | `IN` (Signal) | ESP32 `GPIO 26` | Kontrol sinyal relay (*Active LOW*) |
| **Pompa Air Mini 5V** | Positif (+) | Relay `COM` (*Common*) | Jalur saklar pompa |
| | Negatif (-) | Power Supply / Charger `GND` | Jalur langsung ke ground power supply |
| **Adaptor / Power Suplai** | Positif 5V | Relay `NO` (*Normally Open*) | Sirkuit tertutup saat relay ON |

> [!IMPORTANT]
> **PENTING TENTANG PIN ADC ESP32:**  
> Selalu gunakan pin analog pada blok **ADC1** (seperti **GPIO 34**, GPIO 35, GPIO 36/VP, GPIO 39/VN). Jangan menggunakan pin ADC2 (GPIO 2, 4, 12, 13, 14, 15, 25, 26, 27) untuk pembacaan analog ketika modul Wi-Fi aktif, karena ADC2 digunakan oleh driver Wi-Fi internal ESP32.

---

## 🎯 Panduan Kalibrasi Sensor Kelembapan Tanah

Sensor kapasitif menghasilkan tegangan analog (0 - 4095 pada ADC 12-bit ESP32). Karena setiap sensor memiliki toleransi komponen yang berbeda, lakukan kalibrasi sederhana:

1. Buka Serial Monitor di Arduino IDE pada baud rate `115200`.
2. **Uji Udara Bebas (Kering):**
   - Biarkan sensor di udara terbuka kering tanpa menyentuh apa pun.
   - Amati nilai raw ADC pada Serial Monitor (biasanya berkisar antara **3200 - 3500**).
   - Masukkan nilai ini ke variabel `ADC_DRY` pada file `esp32/smart_plant.ino`.
3. **Uji Air (Basah 100%):**
   - Celupkan ujung sensor ke dalam gelas berisi air (jangan melebihi batas garis komponen elektronik!).
   - Amati nilai raw ADC (biasanya berkisar antara **1300 - 1600**).
   - Masukkan nilai ini ke variabel `ADC_WET` pada file `esp32/smart_plant.ino`.

---

## 🚀 Panduan Menjalankan Aplikasi Web

> [!NOTE]
> Panduan lengkap dan langkah demi langkah tersedia di file [HowToRun.md](HowToRun.md).

### Opsi A: Menggunakan XAMPP / Laragon (Metode Kuliah Standar)
1. **Jalankan Apache & MySQL:** Buka XAMPP Control Panel atau Laragon, lalu klik **Start** pada modul Apache dan MySQL.
2. **Import Database:** Buka browser ke `http://localhost/phpmyadmin`, buat database bernama `smart_plant_db`, lalu import file `database.sql`.
3. **Buka Web Dashboard:**
   - Akses via browser:
     ```
     http://localhost/RPLProject/
     ```
     *(atau `http://localhost/smart-plant-rpl/` jika nama folder disesuaikan)*.
   - Dashboard akan langsung tampil lengkap dengan data awal dummy.

### Opsi B: Menggunakan PHP Built-in Server
1. Buka terminal di folder proyek:
   ```bash
   php -S 0.0.0.0:8000
   ```
2. Buka browser: `http://localhost:8000/`

### Opsi C: Mode Simulasi Demo (Tanpa Database)
Buka file `frontend/index.html` langsung di browser Anda. Dashboard otomatis mendeteksi ketiadaan server dan menyajikan data simulasi visual interaktif lengkap dengan notifikasi toast.

---

## 🧪 Unit Testing & Pengujian Otomatis (All Passed)

> [!NOTE]
> Matriks checklist seluruh fitur dan bukti testing tersedia di file [checklist_mvp.md](checklist_mvp.md).

Proyek ini telah dilengkapi dengan test harness otomatis (*zero-dependency*) yang memvalidasi seluruh acceptance criteria 5 User Stories:

### Cara 1: Menjalankan via Terminal / CLI
Buka terminal di root direktori proyek, lalu jalankan:
```bash
php tests/run_tests.php
```

**Hasil Pengujian:**
```text
===========================================================================
📊 RINGKASAN HASIL PENGUJIAN 5 UNIT TEST SUITES:
   Total Test Suites: 5 Suites
   Total Test Cases : 20 Test Cases
   Total Assertions : 58 Assertions
   Lulus (PASSED)   : 58 (100%)
   Gagal (FAILED)   : 0
   Waktu Eksekusi   : ~0.5 ms
===========================================================================
🎉 STATUS: ALL PASSED! 100% ACCEPTANCE CRITERIA 5 USER STORIES TERPENUHI
```

### Cara 2: Menjalankan via Browser (Visual Test Runner)
Buka browser ke alamat:
```
http://localhost/RPLProject/tests/
```
*(atau buka langsung file `tests/index.html` di browser)*.  
Layar akan memvisualisasikan seluruh pengujian unit dengan status **PASSED**, badge indikator, dan waktu eksekusi secara real-time.

---

## 📡 Dokumentasi Endpoint REST API

### 1. `POST /backend/api/update_sensor.php`
Mengirim data kelembapan dari ESP32 ke backend.
* **Request Body (JSON):**
  ```json
  {
    "mac_address": "24:6F:28:7A:B1:C0",
    "moisture_level": 42.5
  }
  ```
* **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Data sensor berhasil disimpan.",
    "data": {
      "device_id": 1,
      "mac_address": "24:6F:28:7A:B1:C0",
      "moisture_level": 42.5,
      "operating_mode": "AUTO",
      "pump_status": "OFF",
      "timestamp": "2026-10-21 15:30:00"
    }
  }
  ```

### 2. `GET /backend/api/get_status.php?mac_address=24:6F:28:7A:B1:C0`
Mengecek status pompa dan mode operasi terkini untuk mikrokontroler ESP32.
* **Response (200 OK):**
  ```json
  {
    "status": "success",
    "mac_address": "24:6F:28:7A:B1:C0",
    "name": "ESP32 - Tanaman Monstera",
    "operating_mode": "AUTO",
    "pump_status": "OFF",
    "last_seen": "2026-10-21 15:30:05",
    "server_time": "2026-10-21 15:30:05"
  }
  ```

### 3. `GET /backend/api/get_dashboard.php`
Mengambil data gabungan untuk auto-refresh dashboard frontend SPA setiap 5 detik.
* **Response (200 OK):** Berisi status perangkat, kelembapan terkini, 10 riwayat log sensor, dan ringkasan statistik (rata-rata, nilai min, nilai max).

### 4. `POST /backend/api/update_mode.php`
Mengubah mode operasi dari dashboard web (`AUTO` atau `MANUAL`).
* **Request Body (JSON):**
  ```json
  {
    "mac_address": "24:6F:28:7A:B1:C0",
    "operating_mode": "MANUAL"
  }
  ```

### 5. `POST /backend/api/update_pump.php`
Mengendalikan relay pompa secara manual saat berada di Mode MANUAL.
* **Request Body (JSON):**
  ```json
  {
    "mac_address": "24:6F:28:7A:B1:C0",
    "pump_status": "ON"
  }
  ```
  *(Akan ditolak dengan **HTTP 403 Forbidden** jika sistem masih berada dalam mode `AUTO`).*

---

## 👨‍💻 Kontribusi & Hak Cipta
Dibuat untuk keperluan akademik / tugas proyek Rekayasa Perangkat Lunak (RPL). Bebas dimodifikasi dan dikembangkan untuk keperluan penelitian dan pembelajaran IoT.
