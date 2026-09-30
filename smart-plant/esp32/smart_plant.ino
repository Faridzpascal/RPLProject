#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* telemetryUrl = "http://YOUR_BACKEND_IP:3001/api/iot/telemetry";
const char* actionUrl = "http://YOUR_BACKEND_IP:3001/api/iot/action";

const char* macAddress = "AA:BB:CC:DD:EE:FF";

const int SENSOR_PIN = 34; // Capacitive Soil Moisture Sensor Analog Pin
const int RELAY_PIN = 26;  // Relay Pin for Pump

const int DRY_VALUE = 4095; // Adjust based on your sensor calibration
const int WET_VALUE = 1000; // Adjust based on your sensor calibration

String currentMode = "AUTO";
String currentPumpStatus = "OFF";

unsigned long lastTelemetryTime = 0;
const unsigned long TELEMETRY_INTERVAL = 5000; // 5 seconds

void setup() {
  Serial.begin(115200);
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH); // Assuming active-low relay, HIGH = OFF

  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(" Connected!");
}

float getMoistureLevel() {
  int sensorValue = analogRead(SENSOR_PIN);
  float percentage = map(sensorValue, DRY_VALUE, WET_VALUE, 0, 100);
  if (percentage < 0) percentage = 0;
  if (percentage > 100) percentage = 100;
  return percentage;
}

void setPump(String status) {
  if (status == "ON") {
    digitalWrite(RELAY_PIN, LOW); // Active-low relay
  } else {
    digitalWrite(RELAY_PIN, HIGH);
  }
  currentPumpStatus = status;
}

void reportAction(String action) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(actionUrl);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<200> doc;
    doc["MacAddress"] = macAddress;
    doc["Mode"] = currentMode;
    doc["Action"] = action;

    String requestBody;
    serializeJson(doc, requestBody);

    int httpResponseCode = http.POST(requestBody);
    http.end();
  }
}

void loop() {
  unsigned long currentTime = millis();
  
  if (currentTime - lastTelemetryTime >= TELEMETRY_INTERVAL) {
    lastTelemetryTime = currentTime;
    float moisture = getMoistureLevel();

    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      http.begin(telemetryUrl);
      http.addHeader("Content-Type", "application/json");

      StaticJsonDocument<200> doc;
      doc["MacAddress"] = macAddress;
      doc["MoistureLevel"] = moisture;

      String requestBody;
      serializeJson(doc, requestBody);

      int httpResponseCode = http.POST(requestBody);

      if (httpResponseCode > 0) {
        String responseBody = http.getString();
        StaticJsonDocument<200> responseDoc;
        DeserializationError error = deserializeJson(responseDoc, responseBody);

        if (!error) {
          String backendMode = responseDoc["OperatingMode"].as<String>();
          String backendPumpStatus = responseDoc["PumpStatus"].as<String>();
          currentMode = backendMode;

          if (currentMode == "MANUAL") {
            if (currentPumpStatus != backendPumpStatus) {
              setPump(backendPumpStatus);
            }
          }
        }
      }
      http.end();
    }

    // Auto Mode Logic
    if (currentMode == "AUTO") {
      String expectedPumpStatus = currentPumpStatus;
      if (moisture < 30.0) {
        expectedPumpStatus = "ON";
      } else if (moisture > 70.0) {
        expectedPumpStatus = "OFF";
      }

      if (currentPumpStatus != expectedPumpStatus) {
        setPump(expectedPumpStatus);
        reportAction(expectedPumpStatus);
      }
    }
  }
}
