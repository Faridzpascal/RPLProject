# Sequence Diagram - Smart Plant IoT

Dokumen ini memodelkan interaksi antar objek secara sekuensial berdasarkan waktu.

---

## 1. Sequence Diagram: Monitoring & Realtime Polling

```mermaid
sequenceDiagram
    autonumber
    actor User
    participant UI as Web Dashboard (Browser)
    participant SC as SensorController (API)
    participant PL as Plant Model
    participant DB as MySQL Database

    User->>UI: Buka URL Dashboard
    UI->>SC: GET /api/plants/1/latest
    SC->>PL: find(1) with latestSensorData
    PL->>DB: SELECT * FROM sensor_data WHERE plant_id=1 ORDER BY created_at DESC LIMIT 1
    DB-->>PL: Sensor Record
    PL-->>SC: Plant & Sensor Object
    SC-->>UI: JSON {soil_moisture: 68, plant_status: "GOOD", is_online: true}
    UI-->>User: Render Nilai %, Status GOOD, Device ONLINE
    
    loop Setiap 5 Detik Polling
        UI->>SC: GET /api/plants/1/latest
        SC-->>UI: Update JSON
        UI-->>User: Segarkan Tampilan UI
    end
```

---

## 2. Sequence Diagram: Dummy Sensor Ingestion & Automatic Watering

```mermaid
sequenceDiagram
    autonumber
    participant SIM as Simulator CLI / Web Trigger
    participant SIC as SimulatorController
    participant WS as WateringService
    participant DB as MySQL Database
    participant UI as Web Dashboard

    SIM->>SIC: POST /api/simulator/generate {"device_id": "SP001", "soil_moisture": 25}
    SIC->>SIC: Validate Range (0 - 100%)
    SIC->>DB: INSERT INTO sensor_data (plant_id, soil_moisture, created_at)
    SIC->>WS: evaluateAutomaticWatering(plant, 25)
    WS->>DB: SELECT * FROM pump_logs WHERE plant_id=1 ORDER BY created_at DESC LIMIT 1
    DB-->>WS: Last Log
    
    alt 25 < threshold (30) AND cooldown > 45s AND auto_watering = true
        WS->>DB: INSERT INTO pump_logs (plant_id, mode: 'automatic', duration: 5, status: 'completed')
        WS-->>SIC: PumpLog Created
    else Kondisi Tidak Terpenuhi
        WS-->>SIC: null
    end
    
    SIC-->>SIM: 201 Created {success: true, auto_watering_triggered: true}
    
    Note over UI,SIC: Pada siklus polling berikutnya
    UI->>SIC: GET /api/plants/1/latest & GET /api/plants/1/pump-logs
    SIC-->>UI: Data Terbaru & Log Penyiraman Baru
    UI->>UI: Update Tampilan & Tambah Baris Log
```
