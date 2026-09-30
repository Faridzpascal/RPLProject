# Smart Plant Dashboard

Smart Plant Dashboard is a simple full-stack IoT web application that helps users monitor and control an automated plant watering system using ESP32, Node.js Backend, and React Frontend.

## Project Structure

* `backend/` - Node.js + Express + Prisma + MySQL API Server
* `frontend/` - React + TypeScript + Vite + Tailwind CSS Web Dashboard
* `esp32/` - Arduino/C++ source code for the ESP32 Microcontroller
* `docker-compose.yml` - MySQL Database container

## Requirements
* Node.js (v18 or higher)
* Docker & Docker Compose
* Arduino IDE (for ESP32 programming)

## Setup & Installation

### 1. Database Setup
Start the MySQL database using Docker:
```bash
cd smart-plant
docker compose up -d
```

### 2. Backend Setup
```bash
cd smart-plant/backend
npm install
cp .env.example .env
```
Run Prisma migrations and seed the database:
```bash
npx prisma generate
npx prisma migrate dev --name init
npm run prisma:seed
```
Start the backend server:
```bash
npm run dev
```
The backend API will run on `http://localhost:3001/api`.

### 3. Frontend Setup
```bash
cd smart-plant/frontend
npm install
npm run dev
```
The frontend dashboard will run on `http://localhost:3000`.

### 4. ESP32 Setup
1. Open `esp32/smart_plant.ino` in Arduino IDE.
2. Install the required libraries in Arduino IDE:
   - `ArduinoJson`
3. Update the WiFi credentials in the code:
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```
4. Update the `telemetryUrl` and `actionUrl` with your computer's local IP address (e.g., `192.168.1.10`):
   ```cpp
   const char* telemetryUrl = "http://192.168.1.10:3001/api/iot/telemetry";
   const char* actionUrl = "http://192.168.1.10:3001/api/iot/action";
   ```
5. Ensure the MAC Address in the Arduino code matches the seeded data:
   ```cpp
   const char* macAddress = "AA:BB:CC:DD:EE:FF";
   ```

## Hardware Wiring Guide
* **Capacitive Soil Moisture Sensor**: Connect Analog Out to ESP32 Pin `34`, VCC to 3.3V, GND to GND.
* **Relay Module**: Connect IN to ESP32 Pin `26`, VCC to 5V (or VIN), GND to GND.
* **Mini Water Pump**: Connect in series with the Relay output and an external 5V/12V power supply.
