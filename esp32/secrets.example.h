#ifndef SECRETS_H
#define SECRETS_H

// Salin file ini menjadi "secrets.h" sebelum upload ke ESP32.
// File "secrets.h" di-ignore oleh Git agar kredensial tidak bocor ke repository.

// Kredensial Wi-Fi
const char* WIFI_SSID       = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD   = "PASSWORD_WIFI_ANDA";

// URL Backend API (Gunakan HTTPS di produksi, misal: "https://smartplant.example.com/backend/api")
// Untuk uji lokal XAMPP: "http://192.168.1.10/RPLProject/backend/api"
const char* SERVER_BASE_URL = "https://smartplant.example.com/backend/api";

// API Key perangkat (harus sama dengan DEVICE_API_KEY di server backend)
const char* DEVICE_API_KEY  = "smartplant-dev-device-key-change-me";

// Set false di produksi dan isi ROOT_CA_CERT dengan Root CA server HTTPS Anda
const bool ALLOW_INSECURE_TLS = true;
const char* ROOT_CA_CERT = "";

#endif

