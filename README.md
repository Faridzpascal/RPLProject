# 🌱 Smart Plant: Sistem Monitoring Kelembapan Tanah & Otomatisasi Penyiraman Berbasis IoT & Web

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.x%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Chart.js](https://img.shields.io/badge/Chart.js-4.4-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://chartjs.org)

> **Catatan Pengembangan (Fase 1 - Pra-Hardware):**  
> Versi saat ini menggunakan **dummy soil moisture data** karena perangkat keras IoT (ESP32 dan sensor tanah) belum tersedia. Pada tahap pengembangan berikutnya, dummy data akan digantikan dengan data aktual dari **ESP32 + Capacitive Soil Moisture Sensor** melalui protokol komunikasi **MQTT**. Seluruh antarmuka web, API, dan skema database dirancang independen (*loosely coupled*), sehingga transisi ke hardware fisik nantinya tidak memerlukan perombakan dashboard.

---

## 1. Project Overview (Ringkasan Proyek)

Proyek ini dikembangkan untuk mata kuliah **Rekayasa Perangkat Lunak (RPL)** dengan judul:  
**"Rancang Bangun Sistem Smart Plant Berbasis IoT untuk Monitoring Kelembapan Tanah dan Otomatisasi Penyiraman Berbasis Web"**

Sistem ini memadukan konsep Internet of Things (IoT), RESTful API, dan Web Dashboard modern buatan sendiri (**tanpa menggunakan platform pihak ketiga seperti Blynk**). Sistem berfokus secara spesifik hanya pada satu parameter sensor, yaitu **kelembapan tanah (*soil moisture*)**, dan satu aktuator, yaitu **pompa air (*mini water pump*)** yang dikendalikan melalui relai.

---

## 2. Background (Latar Belakang)

Banyak penggemar tanaman hias dan pekerja kantoran mengalami kesulitan dalam menjaga rutinitas penyiraman tanaman. Kesibukan harian sering kali menyebabkan tanaman terabaikan hingga kering atau bahkan mati. Di sisi lain, penyiraman berlebihan juga dapat menyebabkan kebusukan akar tanaman. Diperlukan sebuah sistem cerdas yang mampu memantau kelembapan media tanam secara berkala dan mengambil tindakan penyiraman secara terukur.

---

## 3. Problem Statement (Rumusan Masalah)

1. Pemilik tanaman tidak mengetahui kondisi kelembapan tanah secara objektif dan berkala tanpa memeriksa secara fisik.
2. Risiko tanaman mati akibat kekurangan air ketika pemilik sedang bepergian atau sibuk.
3. Ketiadaan pencatatan riwayat kelembapan tanah dan aktivitas penyiraman untuk analisis kesehatan tanaman.

---

## 4. Objectives (Tujuan Proyek)

1. Membangun sistem monitoring kelembapan tanah secara *real-time* berbasis dashboard web mandiri.
2. Mengembangkan mekanisme penyiraman tanaman otomatis (*automatic watering*) berdasarkan ambang batas (*soil threshold*) yang dapat dikonfigurasi.
3. Menyediakan kontrol penyiraman manual (*manual watering*) yang aman melalui web dashboard.
4. Menerapkan arsitektur perangkat lunak yang *loosely coupled* dengan simulator data dummy yang siap bertransisi ke ESP32 + MQTT.

---

## 5. Features (Fitur Utama)

* 💧 **Soil Moisture Monitoring:** Menampilkan persentase kelembapan tanah (0–100%) secara *real-time* dengan visual progress bar dinamis.
* 🌱 **Plant Status Categorization:** Mengklasifikasikan kondisi tanaman secara otomatis:
  * `0% – 30%`: **DRY** (Tanah Kering / Butuh Air)
  * `31% – 60%`: **MODERATE** (Cukup Lembap)
  * `61% – 100%`: **GOOD** (Lembap Ideal)
* 🚿 **Manual Watering Simulation:** Tombol interaktif `WATER NOW` dengan animasi penghitung waktu (*countdown*) dan simulasi status pompa ON/OFF.
* 🤖 **Automatic Watering Automation:** Evaluasi otomatis saat kelembapan tanah berada di bawah nilai ambang batas (*soil threshold*).
* 🛡️ **Safety Cooldown Mechanism:** Mencegah pompa menyala berulang kali secara terus-menerus guna melindungi tanaman dari kebanjiran dan mengamankan aktuator.
* 📈 **Soil Moisture History (Chart.js):** Grafik tren fluktuasi kelembapan tanah bersumber dari database via REST API dengan filter waktu (*Today* dan *Last 7 Days*).
* ⚙️ **Plant Settings Management:** Pengaturan nama tanaman, ambang batas kelembapan, durasi penyiraman, dan sakelar aktivasi otomatisasi.
* 📡 **Device Status (Online/Offline):** Deteksi status koneksi perangkat berdasarkan kebaruan data sensor dalam jendela waktu toleransi.
* 📋 **Watering History Logs:** Pencatatan komprehensif riwayat penyiraman (Waktu, Mode Manual/Otomatis, Durasi, dan Status).
* 🧪 **Dual Simulator Engine:** Dilengkapi simulator GUI terintegrasi pada web dan script CLI standalone untuk demonstrasi pengujian.

---

## 6. Technology Stack

* **Backend Framework:** PHP 8.1+ / Laravel 10.x
* **Database Management System:** MySQL 8.x / MariaDB 10.4+
* **Frontend:** HTML5, CSS3, JavaScript (Fetch API native, no heavyweight SPA framework)
* **Styling & UI Components:** Bootstrap 5.3 & Bootstrap Icons
* **Data Visualization:** Chart.js 4.4
* **Hardware (Tahap Implementasi Nanti):**
  * Microcontroller: ESP32 DOIT DevKit V1
  * Sensor: Capacitive Soil Moisture Sensor v1.2
  * Actuator: 1-Channel 5V Relay Module & 5V Mini Submersible Water Pump
  * Protocol IoT: MQTT (Mosquitto Broker)

---

## 7. System Architecture (Arsitektur Sistem)

### Fase 1: Tahap Saat Ini (Dummy Data Simulation)

```text
+-----------------------+          +--------------------------------------+
|  Dummy Data Generator |          |          Laravel Backend             |
|   (CLI Script / Web)  |          |                                      |
|                       |---POST-->|  SimulatorController / Ingestion     |
+-----------------------+          |                 │                    |
                                   |                 ▼                    |
                                   |         WateringService              |
+-----------------------+          |      (Rule Engine & Cooldown)        |
|     Web Dashboard     |          |                 │                    |
|   (HTML/CSS/JS/Chart) |          |                 ▼                    |
|                       |<--REST---|         REST API Controllers         |
+-----------------------+          +-----------------┬--------------------+
                                                     │
                                                     ▼
                                           +--------------------+
                                           |   MySQL Database   |
                                           |   (smart_plant)    |
                                           +--------------------+
```

---

## 8. Database Structure & Relasi

Basis data terdiri dari 4 tabel yang ternormalisasi:

```text
[ users ] (1) ────< (N) [ plants ] (1) ────< (N) [ sensor_data ]
                              │
                              └─────────< (N) [ pump_logs ]
```

* **`users`**: Menyimpan akun pengguna (id, name, email, password, timestamps).
* **`plants`**: Menyimpan data pot pintar dan konfigurasinya (id, user_id, device_id, plant_name, soil_threshold, watering_duration, automatic_watering, timestamps).
* **`sensor_data`**: Menyimpan deret waktu pembacaan sensor (id, plant_id, soil_moisture, created_at).
* **`pump_logs`**: Menyimpan catatan kejadian pompa (id, plant_id, mode [manual/automatic], duration, status, created_at).

---

## 9. Installation (Panduan Instalasi)

### Prasyarat Sistem
* XAMPP (Apache, MySQL, PHP 8.1+)
* Composer (atau gunakan `composer.phar` yang disertakan)

### Langkah-langkah:
1. **Clone atau Buka Direktori Proyek:**
   ```bash
   cd c:\folderfaridz\tugas\RPLProject\RPLProject
   ```

2. **Pastikan MySQL XAMPP Berjalan:**
   Buka XAMPP Control Panel dan jalankan modul **MySQL**, atau jalankan mysqld secara lokal pada port `3306`.

3. **Inisialisasi Database:**
   Buat database baru bernama `smart_plant`:
   ```bash
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS smart_plant;"
   ```
   *(Opsional)* Anda juga dapat mengimpor file `database/smart_plant.sql`:
   ```bash
   mysql -u root smart_plant < database/smart_plant.sql
   ```

4. **Konfigurasi Environment Backend:**
   Masuk ke folder `backend`:
   ```bash
   cd backend
   ```
   Pastikan file `.env` memiliki konfigurasi database yang benar:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=smart_plant
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Jalankan Migrasi & Seeder:**
   ```bash
   php artisan migrate --seed
   ```

---

## 10. Running the Application (Menjalankan Aplikasi)

1. Jalankan web server Laravel dari folder `backend`:
   ```bash
   php artisan serve
   ```
2. Buka browser dan akses antarmuka dashboard di:  
   👉 **http://localhost:8000** atau **http://127.0.0.1:8000**

---

## 11. Running Dummy Data Simulator (Menjalankan Simulator)

Tersedia dua metode simulasi untuk pengujian:

### Metode A: Simulator Otomatis Berkelanjutan (CLI)
Buka terminal baru di folder `simulator` dan jalankan:
```bash
cd simulator
php dummy_generator.php 5
```
*(Atau pada Windows, cukup klik dua kali file `run_simulator.bat`)*

Script ini akan:
* Mengirimkan data sensor setiap 5 detik ke backend.
* Mensimulasikan pengeringan tanah alami secara perlahan (misal: 74% → 71% → 67% → ... → 26%).
* Begitu kelembapan menyentuh ambang batas (< 30%), sistem backend secara otomatis memicu penyiraman.
* Setelah penyiraman tercatat, simulator otomatis menaikkan kelembapan (efek tanah tersiram air) dan mengulang siklus.

### Metode B: Quick Simulator via Web Dashboard
Pada bagian atas dashboard web terdapat bar simulasi cepat:
* **Test 25% (Kering):** Langsung menguji kondisi tanah kering dan otomatisasi pompa.
* **Test 50% (Sedang):** Menguji kondisi sedang.
* **Test 75% (Lembap):** Menguji kondisi ideal.
* **Random Fluktuasi:** Menghasilkan data fluktuatif realistis secara instan.

---

## 12. API Documentation (Dokumentasi REST API)

| Method | Endpoint | Deskripsi | Parameter / Payload |
|---|---|---|---|
| `GET` | `/api/plants` | Mengambil seluruh daftar tanaman | - |
| `GET` | `/api/plants/{id}` | Mengambil detail tanaman & status terkini | - |
| `PUT` | `/api/plants/{id}/settings` | Memperbarui pengaturan tanaman | `{"plant_name", "soil_threshold", "watering_duration", "automatic_watering"}` |
| `GET` | `/api/plants/{id}/latest` | Mengambil pembacaan sensor terakhir | - |
| `GET` | `/api/plants/{id}/history` | Mengambil riwayat data kelembapan untuk grafik | `?filter=today` atau `?filter=7days` |
| `POST` | `/api/plants/{id}/pump` | Menjalankan penyiraman manual (*Water Now*) | `{"duration": 5}` |
| `GET` | `/api/plants/{id}/pump-logs` | Mengambil riwayat log penyiraman | - |
| `POST` | `/api/simulator/generate` | Menerima data sensor dari simulator | `{"device_id": "SP001", "soil_moisture": 68}` |

---

## 13. Testing Scenarios (Skenario Pengujian)

| Skenario | Langkah Uji | Hasil yang Diharapkan | Status |
|---|---|---|---|
| **Validasi Input Sensor** | Mengirimkan `soil_moisture: 150` ke `/api/simulator/generate` | HTTP 422 Unprocessable Entity (Ditolak) | ✅ Pass |
| **Kategori Status DRY** | Mengirimkan kelembapan 25% | Badge status berubah merah (`DRY`), pesan "Your plant needs water" | ✅ Pass |
| **Penyiraman Otomatis** | Kelembapan 25% dengan auto watering ON | Record baru masuk ke `pump_logs` dengan mode `automatic` | ✅ Pass |
| **Proteksi Cooldown** | Memicu penyiraman berturut-turut dalam < 45 detik | Sistem menolak spamming dan menjaga jeda aman | ✅ Pass |
| **Penyiraman Manual** | Klik tombol `WATER NOW` pada dashboard | Pompa simulasi status `ON`, hitungan mundur 5 detik, lalu kembali `OFF` | ✅ Pass |
| **Pembaruan Konfigurasi** | Mengubah threshold via slider/modal | Nilai threshold tersimpan di database dan berlaku pada evaluasi berikutnya | ✅ Pass |

---

## 14. Future IoT Integration (Migrasi ke ESP32 + MQTT)

Ketika modul ESP32 telah tersedia, sistem hanya membutuhkan penggantian lapisan pengirim data (*Ingestion Layer*) tanpa merombak dashboard:

1. **Topik Telemetri Sensor:**
   * Topic: `smartplant/SP001/sensor`
   * Payload format:
     ```json
     {
         "device_id": "SP001",
         "soil_moisture": 68
     }
     ```
2. **Topik Kendali Pompa:**
   * Topic: `smartplant/SP001/pump`
   * Payload format:
     ```json
     {
         "pump": true,
         "duration": 5
     }
     ```
3. **Penyambungan ke Backend:**
   Sebuah *worker consumer* MQTT di Laravel (misal via `php-mqtt/client`) akan mendengarkan pesan dari broker Mosquitto dan memanggil service yang sama persis seperti yang digunakan oleh endpoint simulator saat ini.

---

## 15. Limitations (Batasan Sistem)

* Hanya menggunakan 1 jenis sensor (*Capacitive Soil Moisture Sensor*) sesuai batasan ruang lingkup proyek.
* Tidak mencakup sensor cuaca, suhu udara, maupun pencahayaan.
* Pengujian saat ini murni menggunakan modul software simulator terintegrasi.