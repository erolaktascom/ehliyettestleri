@echo off
title EhliyetGo - MEB E-Sinav Hazirlik Portali
set PATH=C:\xampp\php;%PATH%
cd /d "%~dp0"

echo ===================================================
echo     EhliyetGo - MEB E-Sinav Hazirlik Portali
echo ===================================================
echo.
echo Sunucu baslatiliyor: http://127.0.0.1:8000
echo Admin Paneli:       http://127.0.0.1:8000/admin
echo.
echo Kapatmak icin bu pencereyi kapatabilir veya Ctrl+C yapabilirsiniz.
echo.

start "" "http://127.0.0.1:8000"
php artisan serve --port=8000
