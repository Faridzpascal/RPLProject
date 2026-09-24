# System Architecture - Smart Plant IoT

Dokumen ini menjelaskan arsitektur sistem Smart Plant pada dua fase: fase pengembangan saat ini (Dummy Data) dan fase produksi masa depan (ESP32 + MQTT).

---

## 1. Arsitektur Fase 1 (Current Implementation: Dummy Data Simulation)

Pada tahap awal tanpa hardware fisik, sistem menggunakan simulator software yang menyuplai data sensor ke backend Laravel melalui REST API.

```mermaid
graph TD
    subgraph Data Source Layer
        SIM[Dummy Data Generator CLI / Web Simulator]
    end

    subgraph Application & Business Logic Layer (Laravel)
        ING[Simulator Ingestion Controller<br>POST /api/simulator/generate]
        VAL[Input Validator<br>Range: 0 - 100%]
        AUT[Watering Rule Engine<br>WateringService]
        API[REST API Layer<br>Plants, Sensor, Pump, Settings]
    end

    subgraph Persistence Layer
        DB[(MySQL Database<br>smart_plant)]
    end

    subgraph Presentation Layer (Client)
        UI[Web Dashboard<br>HTML5, CSS3, JavaScript, Bootstrap 5, Chart.js]
    end

    SIM -->|HTTP POST JSON| ING
    ING --> VAL
    VAL -->|Valid| AUT
    AUT -->|Insert Sensor Record & Auto-Pump Log| DB
    UI -->|Polling GET /api/plants/1/latest<br>GET /api/plants/1/history| API
    UI -->|Action POST /api/plants/1/pump<br>PUT /api/plants/1/settings| API
    API -->|Read / Write| DB
```

---

## 2. Arsitektur Fase 2 (Future IoT Hardware Integration: ESP32 + MQTT)

Ketika hardware sudah siap, sumber data digantikan secara transparan oleh mikrokontroler ESP32 tanpa mengubah REST API maupun tampilan Web Dashboard.

```mermaid
graph TD
    subgraph Hardware Layer
        SNS[Capacitive Soil Moisture Sensor] -->|Analog Read (ADC)| ESP[ESP32 Microcontroller]
        ESP -->|Digital Out GPIO| REL[1-Channel Relay Module]
        REL --> PMP[Mini Submersible Water Pump]
    end

    subgraph IoT Communication Layer
        BRK[MQTT Broker<br>Mosquitto / EMQX]
    end

    subgraph Backend Application (Laravel)
        SUB[MQTT Consumer Worker<br>Topic: smartplant/+/sensor]
        PUB[MQTT Publisher Service<br>Topic: smartplant/+/pump]
        SVC[WateringService & DB Ingestion]
        API[REST API Endpoints]
        DB[(MySQL Database)]
    end

    subgraph Presentation Layer
        UI[Web Dashboard<br>(TIDAK BERUBAH SAMA SEKALI)]
    end

    ESP -->|Publish Sensor JSON via WiFi| BRK
    BRK -->|Deliver Message| SUB
    SUB --> SVC
    SVC --> DB
    UI -->|Fetch & Control via REST API| API
    API --> DB
    API -->|Send Pump Command| PUB
    PUB -->|Publish Relay Trigger| BRK
    BRK -->|Deliver Command| ESP
```

---

## 3. Layer Abstraction & Loose Coupling

Pemisahan arsitektur ini menerapkan prinsip Software Engineering **Low Coupling & High Cohesion**:
1. **Frontend** hanya mengetahui kontrak data REST API (`device_id`, `soil_moisture`, `timestamp`).
2. **Backend** memproses data sensor yang seragam baik yang berasal dari HTTP POST Simulator maupun pesan MQTT ESP32.
3. **Database** mengisolasi entitas fisik ke dalam model relasional yang ternormalisasi.
