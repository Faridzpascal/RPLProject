/*
 ==============================================================================
 Proyek: Smart Plant Dashboard (RPL IoT Project)
 File  : smart_plant.ino
 Target: ESP32 Dev Module / NodeMCU-32S
 Sensor: Capacitive Soil Moisture Sensor v1.2 / v2.0
 Aktor : Modul Relay 1-Channel (Active LOW) + Mini Water Pump 5V
 Server: XAMPP / Laragon (Native PHP 8 + MySQL)
 Library: WiFi.h, HTTPClient.h (Standard bawaan Board ESP32 Arduino IDE)
 ==============================================================================
*/

#include <WiFi.h>
#include <HTTPClient.h>

// ============================================================================
// 1. PENGATURAN KONEKSI WI-FI & SERVER BACKEND
// ============================================================================
// Ganti dengan SSID dan Password WiFi / Hotspot Anda
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

// Masukkan IP Komputer / Server lokal yang menjalankan XAMPP/Laragon
// Contoh: "http://192.168.1.10/smart-plant-rpl/backend/api"
// (Gunakan perintah 'ipconfig' di Command Prompt Windows untuk melihat IP laptop)
const char* SERVER_BASE_URL = "http://192.168.1.10/smart-plant-rpl/backend/api";

// URL Endpoint API
String urlUpdateSensor = String(SERVER_BASE_URL) + "/update_sensor.php";
String urlGetStatus    = String(SERVER_BASE_URL) + "/get_status.php";

// ============================================================================
// 2. DEFINISI PIN HARDWARE ESP32
// ============================================================================
// PIN SENSOR TANAH: Gunakan pin ADC1 (GPIO 32, 33, 34, 35, 36, 39)
// PERHATIAN: Pin ADC2 tidak bisa dipakai saat Wi-Fi aktif!
const int PIN_SOIL_SENSOR = 34; // Pin Analog AOUT Sensor ke GPIO 34

// PIN RELAY: Mengendalikan saklar pompa air
const int PIN_RELAY = 26;       // Pin IN Modul Relay ke GPIO 26

// PIN LED INDIKATOR ON-BOARD: Indikator transmisi data
const int PIN_LED_BUILDIN = 2;  // LED bawaan ESP32 (GPIO 2)

// ============================================================================
// 3. KALIBRASI SENSOR KELEMBAPAN TANAH KAPASITIF
// ============================================================================
// Nilai ADC 12-bit ESP32 (0 - 4095)
// Kering di udara bebas (Air Value): ~3200 - 3500
// Basah dicelup air (Water Value)  : ~1300 - 1600
const int ADC_DRY   = 3300;     // Nilai ADC saat tanah kering kerontang (0%)
const int ADC_WET   = 1400;     // Nilai ADC saat tanah basah penuh (100%)

// Ambang batas kelembapan untuk Mode Otomatis (Persen)
const float MOISTURE_THRESHOLD_AUTO = 35.0; // Jika < 35%, siram otomatis

// Modul relay pada umumnya Active-LOW:
// LOW  = Relay menyala (pompa ON)
// HIGH = Relay mati (pompa OFF)
const bool RELAY_ACTIVE_LOW = true;

// ============================================================================
// 4. VARIABEL SISTEM & INTERVAL PENGIRIMAN
// ============================================================================
String macAddress = "";
String operatingMode = "AUTO"; // Nilai default: 'AUTO' atau 'MANUAL'
String pumpStatus    = "OFF";  // Nilai default: 'ON' atau 'OFF'
float currentMoisture = 0.0;

// Interval waktu non-blocking menggunakan millis()
unsigned long lastSensorSendTime = 0;
const unsigned long INTERVAL_SEND_SENSOR = 5000; // Kirim data sensor tiap 5 detik

unsigned long lastStatusPollTime = 0;
const unsigned long INTERVAL_POLL_STATUS = 3000; // Cek perintah dari web tiap 3 detik

// ============================================================================
// FUNCTION DECLARATIONS (PROTOTYPES)
// ============================================================================
void connectToWiFi();
float readMoisturePercentage();
void sendSensorData(float moisture);
void checkServerStatus();
void controlPump(bool state);
String extractJsonValue(String json, String key);

// ============================================================================
// SETUP FUNCTION
// ============================================================================
void setup() {
    Serial.begin(115200);
    delay(1000);

    Serial.println("\n========================================================");
    Serial.println("   SMART PLANT IOT - ESP32 WATERING SYSTEM");
    Serial.println("   Proyek Rekayasa Perangkat Lunak (RPL)");
    Serial.println("========================================================");

    // Inisialisasi Pin
    pinMode(PIN_RELAY, OUTPUT);
    pinMode(PIN_LED_BUILDIN, OUTPUT);
    pinMode(PIN_SOIL_SENSOR, INPUT);

    // Matikan pompa pertama kali (Kondisi aman)
    controlPump(false);
    digitalWrite(PIN_LED_BUILDIN, LOW);

    // Hubungkan ke WiFi
    connectToWiFi();

    // Dapatkan MAC Address unik ESP32
    macAddress = WiFi.macAddress();
    Serial.print("[INFO] MAC Address ESP32: ");
    Serial.println(macAddress);

    Serial.println("[INFO] Sistem siap. Memulai loop monitoring...\n");
}

// ============================================================================
// MAIN LOOP FUNCTION
// ============================================================================
void loop() {
    // Pastikan koneksi Wi-Fi tetap terhubung
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[WARNING] Koneksi Wi-Fi terputus! Mencoba menghubungkan kembali...");
        connectToWiFi();
    }

    unsigned long currentMillis = millis();

    // 1. TUGAS A: BACA SENSOR & KIRIM KE SERVER (Setiap 5 detik)
    if (currentMillis - lastSensorSendTime >= INTERVAL_SEND_SENSOR) {
        lastSensorSendTime = currentMillis;

        // Baca nilai sensor kelembapan tanah
        currentMoisture = readMoisturePercentage();

        Serial.println("\n--------------------------------------------------------");
        Serial.print("[SENSOR] Kelembapan Tanah Terbaca: ");
        Serial.print(currentMoisture, 1);
        Serial.println("%");

        // Kirim data ke backend via HTTP POST
        sendSensorData(currentMoisture);
    }

    // 2. TUGAS B: CEK STATUS & PERINTAH DARI WEB DASHBOARD (Setiap 3 detik)
    if (currentMillis - lastStatusPollTime >= INTERVAL_POLL_STATUS) {
        lastStatusPollTime = currentMillis;
        checkServerStatus();
    }

    // 3. TUGAS C: LOGIKA PENGENDALIAN AKTOR (RELAY POMPA AIR)
    if (operatingMode == "MANUAL") {
        // Pada mode MANUAL: Mengikuti perintah langsung dari tombol web dashboard
        if (pumpStatus == "ON") {
            controlPump(true);
        } else {
            controlPump(false);
        }
    } else {
        // Pada mode AUTO: Pompa menyala otomatis jika kelembapan tanah di bawah ambang batas
        if (currentMoisture < MOISTURE_THRESHOLD_AUTO) {
            Serial.println("[LOGIKA AUTO] Tanah kering (< 35%) -> Pompa dinyalakan!");
            controlPump(true);
        } else {
            controlPump(false);
        }
    }

    delay(50); // Delay kecil untuk stabilitas task loop
}

// ============================================================================
// FUNGSI MEMBACA KELEMBAPAN TANAH (ADC Sampling & Konversi %)
// ============================================================================
float readMoisturePercentage() {
    // Lakukan multi-sampling (5 kali) untuk stabilitas pembacaan
    long totalRaw = 0;
    const int sampleCount = 5;
    for (int i = 0; i < sampleCount; i++) {
        totalRaw += analogRead(PIN_SOIL_SENSOR);
        delay(20);
    }
    int avgRaw = totalRaw / sampleCount;

    // Batasi pembacaan ADC pada rentang kalibrasi
    if (avgRaw > ADC_DRY) avgRaw = ADC_DRY;
    if (avgRaw < ADC_WET) avgRaw = ADC_WET;

    // Pemetaan (Mapping) terbalik:
    // Nilai ADC tinggi = Tanah Kering (0%)
    // Nilai ADC rendah  = Tanah Basah (100%)
    float percentage = (float)(ADC_DRY - avgRaw) * 100.0 / (float)(ADC_DRY - ADC_WET);

    if (percentage < 0.0) percentage = 0.0;
    if (percentage > 100.0) percentage = 100.0;

    return percentage;
}

// ============================================================================
// FUNGSI MENGONTROL RELAY POMPA AIR
// ============================================================================
void controlPump(bool turnOn) {
    if (turnOn) {
        // Aktifkan Relay
        digitalWrite(PIN_RELAY, RELAY_ACTIVE_LOW ? LOW : HIGH);
        digitalWrite(PIN_LED_BUILDIN, HIGH);
    } else {
        // Matikan Relay
        digitalWrite(PIN_RELAY, RELAY_ACTIVE_LOW ? HIGH : LOW);
        digitalWrite(PIN_LED_BUILDIN, LOW);
    }
}

// ============================================================================
// FUNGSI MENGIRIM DATA SENSOR KE BACKEND (HTTP POST)
// ============================================================================
void sendSensorData(float moisture) {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    http.begin(urlUpdateSensor);
    http.addHeader("Content-Type", "application/json");

    // Bentuk JSON payload secara manual tanpa dependensi library eksternal
    String jsonPayload = "{\"mac_address\":\"" + macAddress + "\",\"moisture_level\":" + String(moisture, 2) + "}";

    Serial.print("[HTTP POST] Mengirim ke: ");
    Serial.println(urlUpdateSensor);
    Serial.print("[PAYLOAD] ");
    Serial.println(jsonPayload);

    int httpResponseCode = http.POST(jsonPayload);

    if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.print("[HTTP RESPONSE ");
        Serial.print(httpResponseCode);
        Serial.print("]: ");
        Serial.println(response);

        // Update mode & pump status dari respon server jika ada
        String mode = extractJsonValue(response, "operating_mode");
        String pump = extractJsonValue(response, "pump_status");
        if (mode.length() > 0) operatingMode = mode;
        if (pump.length() > 0) pumpStatus = pump;
    } else {
        Serial.print("[HTTP ERROR] Gagal mengirim POST. Kode error: ");
        Serial.println(httpResponseCode);
    }

    http.end();
}

// ============================================================================
// FUNGSI CEK STATUS DAN MODE DARI SERVER (HTTP GET)
// ============================================================================
void checkServerStatus() {
    if (WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    String requestUrl = urlGetStatus + "?mac_address=" + macAddress;

    http.begin(requestUrl);
    int httpResponseCode = http.GET();

    if (httpResponseCode == 200) {
        String response = http.getString();

        // Ekstraksi nilai 'operating_mode' dan 'pump_status' dari JSON response
        String mode = extractJsonValue(response, "operating_mode");
        String pump = extractJsonValue(response, "pump_status");

        if (mode.length() > 0 && (mode == "AUTO" || mode == "MANUAL")) {
            operatingMode = mode;
        }

        if (pump.length() > 0 && (pump == "ON" || pump == "OFF")) {
            pumpStatus = pump;
        }

        Serial.print("[STATUS SERVER] Mode: ");
        Serial.print(operatingMode);
        Serial.print(" | Pompa: ");
        Serial.println(pumpStatus);
    } else {
        Serial.print("[HTTP GET ERROR] Gagal mengambil status. Kode: ");
        Serial.println(httpResponseCode);
    }

    http.end();
}

// ============================================================================
// FUNGSI PARSING JSON SEDERHANA (Zero Dependency)
// ============================================================================
// Mengekstrak nilai string dari JSON tanpa perlu library ArduinoJson tambahan
String extractJsonValue(String json, String key) {
    String searchKey = "\"" + key + "\":\"";
    int startIndex = json.indexOf(searchKey);
    if (startIndex != -1) {
        startIndex += searchKey.length();
        int endIndex = json.indexOf("\"", startIndex);
        if (endIndex != -1) {
            return json.substring(startIndex, endIndex);
        }
    }
    return "";
}

// ============================================================================
// FUNGSI KONEKSI KE JARINGAN WI-FI
// ============================================================================
void connectToWiFi() {
    Serial.print("[WIFI] Menghubungkan ke: ");
    Serial.println(WIFI_SSID);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 25) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\n[WIFI] Berhasil terhubung!");
        Serial.print("[WIFI] Alamat IP ESP32: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("\n[WIFI] Gagal terhubung dalam waktu tunggu. Cek SSID/Password.");
    }
}
