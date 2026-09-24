#  Smart Plant: Sistem Penyiraman Tanaman Cerdas Berbasis IoT & Web Monitoring

> Solusi otomatisasi perawatan tanaman hias untuk gaya hidup sibuk, memadukan perangkat IoT (ESP32) dengan kemudahan pemantauan melalui *Web Dashboard*.

---

##  Latar Belakang Masalah

Banyak rumah memiliki koleksi tanaman hias, namun kesibukan harian seringkali membuat rutinitas penyiraman terabaikan. Akibatnya, tanaman berisiko kering atau bahkan mati karena kurangnya perawatan rutin. Proyek ini hadir untuk mengatasi masalah tersebut dengan mengotomatiskan proses penyiraman berdasarkan kondisi aktual tanah.

##  Target Pengguna

* **Pemilik Rumah & Pekerja Kantoran:** Yang memiliki mobilitas tinggi.
* **Hobbyist Tanaman Hias:** Yang memiliki rutinitas padat namun tetap ingin koleksinya terawat tanpa harus menyiram secara manual setiap hari.

##  Manfaat Sistem

* **Efisiensi Waktu & Tenaga:** Penyiraman otomatis mengambil alih tugas manual harian.
* **Kesehatan Tanaman Terjamin:** Tanaman mendapat asupan air tepat waktu sesuai dengan tingkat kelembapan tanah.
* **Aksesibilitas Fleksibel:** Pengguna dapat memantau dan mengontrol sistem secara nirkabel melalui *dashboard* web.

---

##  Fitur Inti

*(Dirancang untuk diselesaikan dalam estimasi waktu 12 pertemuan)*

* ✅ **Pembacaan Kelembapan Tanah:** Menggunakan *Soil Moisture Sensor* secara *real-time*.
* ✅ **Mode Otomatis:** Mikrokontroler (ESP32) secara mandiri memicu *relay* untuk menyalakan *mini pump* saat sensor mendeteksi tanah kering.
* ✅ **Mode Manual:** Tersedia tombol *On/Off* interaktif pada *dashboard* web untuk mengontrol pompa secara paksa kapan saja.
* ✅ **Dashboard Web Monitoring:** Antarmuka antarmuka yang menampilkan status kelembapan tanah dan indikator status pompa (Nyala/Mati).

##  Batasan Proyek (Out-of-Scope)

Agar pengembangan tetap fokus sesuai alokasi waktu, proyek ini **TIDAK** mencakup:

* ❌ Pemantauan parameter ekstra (cuaca, suhu lingkungan, atau nutrisi pupuk).
* ❌ Pembuatan aplikasi *mobile native* (Android/iOS) — antarmuka murni difokuskan pada web.
* ❌ Integrasi modul kamera untuk pemantauan visual.
* ❌ Sistem pompa multi-zona (sistem pompa yang dirancang bersifat terpusat, tidak dibedakan per pot).

---

##  Kriteria Keberhasilan

Sistem ini dinyatakan sukses apabila telah memenuhi indikator berikut:

1. Sensor mampu membaca tingkat kelembapan tanah dengan akurasi yang baik.
2. Data sensor berhasil diproses oleh ESP32 dan dikirimkan ke antarmuka web tanpa hambatan/jeda yang fatal.
3. Pompa air merespons dengan menyala secara otomatis ketika kelembapan tanah menyentuh batas bawah (kering).
4. Fungsi *override* (tombol on/off manual) pada web berfungsi normal untuk mengambil alih kontrol pompa.