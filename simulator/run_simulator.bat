@echo off
title Smart Plant Dummy Sensor Simulator
echo Menjalankan Simulator Data Sensor Smart Plant...
echo Target: http://127.0.0.1:8000/api/simulator/generate
echo.
"C:\xampp\php\php.exe" dummy_generator.php 5
pause
