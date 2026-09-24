# Entity Relationship Diagram (ERD) - Smart Plant IoT

Dokumen ini menjelaskan struktur data konseptual, entitas, atribut, dan kardinalitas relasi database pada sistem Smart Plant.

---

## 1. Diagram ERD (Crow's Foot Notation)

```mermaid
erDiagram
    USERS ||--o{ PLANTS : "owns (1:N)"
    PLANTS ||--o{ SENSOR_DATA : "records (1:N)"
    PLANTS ||--o{ PUMP_LOGS : "logs (1:N)"

    USERS {
        bigint_unsigned id PK
        varchar_255 name
        varchar_255 email UK
        varchar_255 password
        timestamp created_at
        timestamp updated_at
    }

    PLANTS {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        varchar_50 device_id UK "Unique hardware identifier"
        varchar_100 plant_name "Name of plant"
        int soil_threshold "Dry threshold percentage (default: 30%)"
        int watering_duration "Pump active duration in seconds (default: 5s)"
        boolean automatic_watering "Toggle automated watering (default: true)"
        timestamp created_at
        timestamp updated_at
    }

    SENSOR_DATA {
        bigint_unsigned id PK
        bigint_unsigned plant_id FK
        tinyint_unsigned soil_moisture "Moisture value (0-100%)"
        timestamp created_at "Indexed timestamp of measurement"
    }

    PUMP_LOGS {
        bigint_unsigned id PK
        bigint_unsigned plant_id FK
        enum mode "'manual', 'automatic'"
        int duration "Duration in seconds"
        enum status "'running', 'completed', 'failed'"
        timestamp created_at "Indexed timestamp of watering event"
    }
```

---

## 2. Deskripsi Relasi & Kardinalitas

1. **USERS ke PLANTS (One-to-Many / 1 : N):**
   * Seorang pengguna dapat memiliki satu atau lebih tanaman/pot pintar.
   * `ON DELETE CASCADE`: Jika akun pengguna dihapus, seluruh tanaman miliknya akan ikut terhapus.

2. **PLANTS ke SENSOR_DATA (One-to-Many / 1 : N):**
   * Satu pot tanaman mencatat ribuan riwayat data kelembapan tanah sepanjang waktu (*time-series*).
   * Nilai kelembapan disimpan dalam `tinyint unsigned` (rentang 0 s.d. 100).
   * `ON DELETE CASCADE`: Menghapus pot akan membersihkan data telemetri historisnya.

3. **PLANTS ke PUMP_LOGS (One-to-Many / 1 : N):**
   * Satu pot tanaman memiliki catatan berkala setiap kali pompa air diaktifkan, baik oleh instruksi manual tombol web maupun oleh aturan otomatisasi sistem.
