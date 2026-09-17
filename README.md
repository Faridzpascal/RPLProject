# RPLProject

Tema (Mengerucut):
Sistem Penyiraman Tanaman Cerdas (Smart Plant) Berbasis IoT dan Web Monitoring

Deskripsi masalah:
Di rumah saya ada banyak tanaman hias, tapi karena semua anggota keluarga sibuk bekerja, kadang kami tidak sempat atau bahkan sering lupa untuk menyiramnya. Akibatnya, tanaman berisiko kering atau mati karena kurangnya perawatan rutin setiap hari.

Profil target pengguna:
Pemilik rumah, pekerja kantoran, atau orang yang hobi memelihara tanaman tapi punya rutinitas yang sibuk sehingga tidak bisa menyiram tanaman secara langsung setiap harinya.

Manfaat aplikasi:
Sistem ini sangat menghemat waktu dan tenaga karena penyiraman tidak harus dilakukan manual tiap hari. Kesehatan tanaman juga lebih terjamin karena disiram tepat waktu sesuai kondisi tanah. Selain itu, pengguna bisa dengan mudah memantau dan mengontrol alat ini dari jarak jauh melalui web.

Daftar fitur inti:
Dalam estimasi waktu 12 pertemuan, fitur yang realistis untuk diselesaikan adalah:

Pembacaan tingkat kelembapan tanah menggunakan Soil Moisture Sensor.

Mode penyiraman otomatis: ESP32 akan memicu relay untuk menyalakan mini pump saat sensor mendeteksi tanah sudah kering.

Mode penyiraman manual: Terdapat tombol pada dashboard web untuk menyalakan atau mematikan pompa secara manual kapan saja.

Dashboard web monitoring sederhana untuk memantau data kelembapan tanah dan status pompa (sedang menyala atau mati).

Fitur yang tidak dikerjakan:
Pada project ini saya tidak membuat fitur pemantauan cuaca, suhu, atau nutrisi pupuk (fokus murni pada kelembapan air). Saya juga tidak membuat aplikasi mobile khusus Android/iOS karena interface difokuskan pada web monitoring saja. Selain itu, tidak ada integrasi kamera untuk melihat tanaman, dan sistem pompanya dibuat terpusat (tidak dibedakan untuk masing-masing pot/zona).

Rencana Fitur yang akan dibuat: Fitur pemantauan suhu dan kelembaban udara


Kriteria aplikasi dinyatakan berhasil:
Aplikasi ini dinyatakan sukses apabila sensor bisa membaca kelembapan tanah dengan akurat dan datanya berhasil terkirim ke ESP32 lalu ditampilkan di web tanpa hambatan. Selain itu, pompa air harus bisa menyala otomatis dengan tepat ketika tanah kering, dan fitur tombol on/off manual di web juga berfungsi normal untuk mengontrol pompa.
