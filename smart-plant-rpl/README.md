# 🌱 Smart Plant Dashboard - Sistem Monitoring & Kontrol IoT

Aplikasi web Internet of Things (IoT) sederhana, ringan, dan andal untuk monitoring kelembapan tanah tanaman dan kendali pompa penyiraman otomatis secara real-time. Dibuat sesuai standar proyek Rekayasa Perangkat Lunak (RPL) dan Tugas Kuliah IoT.

---

## 📌 Fitur Utama

1. **Monitoring Kelembapan Real-time**: Mengambil data kelembapan tanah dari sensor kapasitif via ESP32 dan menyimpannya ke MySQL.
2. **Auto-Refresh SPA (5 Detik)**: Dashboard web memperbarui data secara asinkron tanpa reload halaman menggunakan Vanilla JavaScript (Fetch API).
3. **Dual Mode Operasi (AUTO & MANUAL)**:
   - **Mode AUTO**: Mikrokontroler/Backend menyiram tanaman secara otomatis saat kelembapan tanah berada di bawah ambang batas (< 35.0%).
   - **Mode MANUAL**: Pengguna dapat menyalakan atau mematikan pompa air langsung melalui tombol di dashboard web.
4. **Riwayat 10 Log Terakhir**: Menampilkan tabel log pembacaan sensor terbaru lengkap dengan visualisasi progress bar dan klasifikasi kondisi tanah (Kering, Lembab, Basah).
5. **Indikator Status Online**: Mengetahui apakah mikrokontroler ESP32 sedang aktif mengirim data (heartbeat).

---

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript (Fetch API AJAX).
- **Backend**: Native PHP 8 menggunakan PDO (Prepared Statements, Bebas ORM).
- **Database**: MySQL / MariaDB (Relasional 2 tabel: `devices` dan `sensor_logs`).
- **Mikrokontroler & Hardware**:
  - ESP32 Development Board (30/38 pin)
  - Capacitive Soil Moisture Sensor v1.2 / v2.0
  - Modul Relay 1-Channel (5V Active LOW)
  - Mini Submersible Water Pump 5V DC + Selang
  - Breadboard & Kabel Jumper
- **Server**: XAMPP / Laragon (Apache & MySQL) atau PHP Built-in Server.

---

## 📁 Struktur Direktori

```plaintext
smart-plant-rpl/
├── backend/
│   ├── config/
│   │   └── database.php          # Koneksi database MySQL dengan PDO
│   └── api/
│       ├── update_sensor.php     # Endpoint POST untuk ESP32 mengirim data sensor
│       ├── get_status.php        # Endpoint GET untuk ESP32 mengecek mode & pompa
│       ├── get_dashboard.php     # Endpoint GET untuk frontend dashboard SPA
│       ├── update_mode.php       # Endpoint POST untuk mengubah mode AUTO/MANUAL
│       └── update_pump.php       # Endpoint POST untuk kontrol manual pompa ON/OFF
├── frontend/
│   ├── css/
│   │   └── style.css             # Desain custom tema tanaman & animasi
│   ├── js/
│   │   └── app.js                # Logika SPA, AJAX polling 5s, toast & kontrol
│   └── index.html                # Tampilan utama web dashboard (Bahasa Indonesia)
├── esp32/
│   └── smart_plant.ino           # Source code Arduino C++ ESP32 (WiFi.h & HTTPClient.h)
├── index.php                     # Redirector otomatis ke frontend/index.html
├── database.sql                  # Skrip SQL pembuatan tabel dan data dummy
└── README.md                     # Dokumentasi panduan lengkap proyek
```

---

## 🔌 Skematik & Panduan Rangkaian Hardware

### 1. Pinout Rangkaian

| Komponen Hardware | Pin Komponen | Pin ESP32 / Sumber Daya | Catatan Penting |
| :--- | :--- | :--- | :--- |
| **Capacitive Soil Sensor** | `VCC` | ESP32 `3.3V` atau `VIN (5V)` | Disarankan 3.3V agar output analog <= 3.3V |
| | `GND` | ESP32 `GND` | Ground bersama (Common Ground) |
| | `AOUT` (Analog Out) | ESP32 `GPIO 34` | Gunakan ADC1 (GPIO 34 aman dipakai bersama WiFi) |
| **Modul Relay 1-Channel** | `VCC` | ESP32 `VIN` (5V) | Suplai tegangan modul relay |
| | `GND` | ESP32 `GND` | Ground bersama |
| | `IN` (Signal) | ESP32 `GPIO 26` | Kontrol sinyal relay (Active LOW) |
| **Pompa Air Mini 5V** | Positif (+) | Relay `COM` (Common) | Jalur saklar pompa |
| | Negatif (-) | Power Supply / Charger `GND` | Jalur langsung ke ground power supply |
| **Adaptor / Power Suplai** | Positif 5V | Relay `NO` (Normally Open) | Sirkuit tertutup saat relay ON |

> **⚠️ PENTING TENTANG PIN ADC ESP32:**
> Selalu gunakan pin analog pada blok **ADC1** (seperti **GPIO 34**, GPIO 35, GPIO 36/VP, GPIO 39/VN). Jangan menggunakan pin ADC2 (GPIO 2, 4, 12, 13, 14, 15, 25, 26, 27) untuk pembacaan analog ketika modul Wi-Fi aktif, karena ADC2 digunakan oleh driver Wi-Fi internal ESP32.

---

## 🎯 Panduan Kalibrasi Sensor Kelembapan Tanah

Sensor kapasitif menghasilkan tegangan analog (0 - 4095 pada ADC 12-bit ESP32). Karena setiap sensor memiliki toleransi komponen yang berbeda, lakukan kalibrasi sederhana:

1. Buka Serial Monitor di Arduino IDE pada baud rate `115200`.
2. **Uji Udara Bebas (Kering):**
   - Biarkan sensor di udara terbuka kering tanpa menyentuh apa pun.
   - Amati nilai raw ADC pada Serial Monitor (biasanya berkisar antara **3200 - 3500**).
   - Masukkan nilai ini ke variabel `ADC_DRY` pada file `smart_plant.ino`.
3. **Uji Air (Basah 100%):**
   - Celupkan ujung sensor ke dalam gelas berisi air (jangan sampai melebihi batas garis komponen elektronik!).
   - Amati nilai raw ADC (biasanya berkisar antara **1300 - 1600**).
   - Masukkan nilai ini ke variabel `ADC_WET` pada file `smart_plant.ino`.

---

## 🚀 Panduan Menjalankan Aplikasi Web

### Opsi A: Menggunakan XAMPP / Laragon (Metode Kuliah Standar)

1. **Jalankan Apache & MySQL**:
   - Buka XAMPP Control Panel atau Laragon.
   - Klik **Start** pada modul **Apache** dan **MySQL**.

2. **Salin Folder Proyek**:
   - Pindahkan/salin folder `smart-plant-rpl` ke folder web server Anda:
     - XAMPP: `C:\xampp\htdocs\smart-plant-rpl`
     - Laragon: `C:\laragon\www\smart-plant-rpl`

3. **Import Database**:
   - Buka browser dan akses `http://localhost/phpmyadmin`.
   - Buat database baru bernama `smart_plant_db` (atau langsung klik menu **Import**).
   - Pilih file `smart-plant-rpl/database.sql` lalu klik **Import / Go**.

4. **Buka Web Dashboard**:
   - Akses via browser:
     ```
     http://localhost/smart-plant-rpl/
     ```
   - Dashboard akan langsung tampil lengkap dengan data awal dummy.

---

### Opsi B: Menggunakan PHP Built-in Server (Cepat & Praktis)

1. Pastikan service MySQL sudah aktif (via XAMPP atau Docker).
2. Buat database dan import `database.sql`:
   ```bash
   mysql -u root -p < database.sql
   ```
3. Buka terminal di dalam folder `smart-plant-rpl`:
   ```bash
   cd smart-plant-rpl
   php -S 0.0.0.0:8000
   ```
4. Buka browser:
   ```
   http://localhost:8000/
   ```

---

## 📲 Panduan Konfigurasi & Upload ESP32

1. Buka software **Arduino IDE**.
2. Pastikan Board ESP32 sudah terinstal di Board Manager:
   - *Tools > Board > ESP32 Arduino > ESP32 Dev Module*.
3. Buka file `esp32/smart_plant.ino`.
4. Sesuaikan konfigurasi Wi-Fi dan IP Server:
   ```cpp
   const char* WIFI_SSID     = "NAMA_WIFI_HOTSPOT_ANDA";
   const char* WIFI_PASSWORD = "PASSWORD_WIFI";

   // Ganti dengan IP laptop Anda di jaringan WiFi yang sama:
   const char* SERVER_BASE_URL = "http://192.168.1.10/smart-plant-rpl/backend/api";
   ```
   > **Tips Mengetahui IP Laptop:** Buka CMD/Terminal di Windows, ketik `ipconfig`, lalu cari alamat **IPv4 Address** pada adapter Wireless LAN Wi-Fi (misalnya `192.168.1.15`). Pastikan ESP32 dan Laptop terhubung ke hotspot/jaringan WiFi yang sama.

5. Hubungkan ESP32 ke laptop menggunakan kabel USB data.
6. Pilih port COM yang sesuai di menu *Tools > Port*.
7. Klik tombol **Upload** (tanda panah ke kanan).
8. Setelah selesai upload, buka **Serial Monitor** (Baud rate `115200`) untuk melihat log koneksi Wi-Fi dan pengiriman data ke server.

---

## 📡 Dokumentasi Endpoint API

### 1. `POST /backend/api/update_sensor.php`
Mengirim data kelembapan dari ESP32 ke backend.
- **Request Body (JSON):**
  ```json
  {
    "mac_address": "24:6F:28:7A:B1:C0",
    "moisture_level": 42.5
  }
  ```
- **Response (200 OK):**
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
      "timestamp": "2026-09-30 18:30:00"
    }
  }
  ```

### 2. `GET /backend/api/get_status.php?mac_address=24:6F:28:7A:B1:C0`
Mengecek status pompa dan mode operasi terkini untuk mikrokontroler ESP32.
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "mac_address": "24:6F:28:7A:B1:C0",
    "name": "ESP32 - Tanaman Monstera",
    "operating_mode": "MANUAL",
    "pump_status": "ON",
    "last_seen": "2026-09-30 18:30:05",
    "server_time": "2026-09-30 18:30:05"
  }
  ```

### 3. `GET /backend/api/get_dashboard.php`
Mengambil seluruh data gabungan untuk auto-refresh dashboard frontend SPA setiap 5 detik.
- **Response (200 OK):** Berisi status perangkat, kelembapan terkini, 10 riwayat log sensor, dan ringkasan statistik (rata-rata, nilai min, nilai max).

### 4. `POST /backend/api/update_mode.php`
Mengubah mode operasi dari dashboard web.
- **Request Body (JSON):**
  ```json
  {
    "mac_address": "24:6F:28:7A:B1:C0",
    "operating_mode": "MANUAL"
  }
  ```

### 5. `POST /backend/api/update_pump.php`
Mengendalikan relay pompa secara manual saat berada di mode MANUAL.
- **Request Body (JSON):**
  ```json
  {
    "mac_address": "24:6F:28:7A:B1:C0",
    "pump_status": "ON"
  }
  ```
  *(Akan ditolak dengan HTTP 403 jika mode masih dalam `AUTO`).*

---

## 👨‍💻 Kontribusi & Hak Cipta
Dibuat untuk keperluan akademik / tugas proyek Rekayasa Perangkat Lunak (RPL). Bebas dimodifikasi dan dikembangkan untuk keperluan penelitian dan pembelajaran IoT.
