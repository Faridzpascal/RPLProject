# Activity Diagram - Smart Plant IoT

Dokumen ini memodelkan alur aktivitas sistem untuk dua proses utama: Ingestion Data & Evaluasi Penyiraman Otomatis, serta Penyiraman Manual.

---

## 1. Alur Aktivitas: Ingestion Data Sensor & Evaluasi Otomatis

```mermaid
stateDiagram-v2
    [*] --> GenerateData: Simulator membangkitkan data kelembapan
    GenerateData --> SendPayload: Kirim HTTP POST /api/simulator/generate
    
    state Backend {
        SendPayload --> ValidateInput: Validasi format dan range (0-100%)
        
        state check_validation <<choice>>
        ValidateInput --> check_validation
        check_validation --> Return422: Tidak Valid (Nilai di luar 0-100%)
        check_validation --> SaveSensorData: Valid
        
        SaveSensorData --> EvaluateAutoWatering: Cek Aturan Otomatisasi
        
        state check_auto <<choice>>
        EvaluateAutoWatering --> check_auto
        check_auto --> SavePumpLog: Auto ON & Moisture < Threshold & Jeda Aman
        check_auto --> RespondSuccess: Kondisi Siram Tidak Terpenuhi
        
        SavePumpLog --> RespondSuccess: Catat log mode automatic
    }
    
    Return422 --> [*]: Selesai (Error Response)
    RespondSuccess --> RefreshDashboard: Dashboard melakukan polling
    RefreshDashboard --> [*]: UI & Chart ter-update
```

---

## 2. Alur Aktivitas: Penyiraman Manual (Manual Watering)

```mermaid
stateDiagram-v2
    [*] --> ClickWaterNow: User klik tombol [WATER NOW]
    ClickWaterNow --> SendPumpRequest: Frontend kirim POST /api/plants/1/pump
    
    state BackendService {
        SendPumpRequest --> CheckCooldown: Periksa status cooldown (10 detik)
        
        state check_lock <<choice>>
        CheckCooldown --> check_lock
        check_lock --> Return429: Sedang Cooldown / Sibuk
        check_lock --> RecordPumpLog: Aman (Idle)
        
        RecordPumpLog --> ReturnSuccess: Simpan log mode manual ke database
    }
    
    Return429 --> ShowErrorToast: Tampilkan notifikasi peringatan
    ShowErrorToast --> [*]
    
    ReturnSuccess --> AnimateCountdown: Tampilkan status ON & Countdown durasi di UI
    AnimateCountdown --> ResetPumpUI: Durasi selesai -> Kembalikan status OFF
    ResetPumpUI --> ReloadLogs: Segarkan tabel Watering History
    ReloadLogs --> [*]
```
