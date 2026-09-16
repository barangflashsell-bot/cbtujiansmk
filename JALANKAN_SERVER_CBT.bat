@echo off
setlocal enabledelayedexpansion
title SERVER CBT SMK PESANTREN BUSTANUL ULUM (WI-FI / LAN LOKAL)
cls
color 0A

echo ===============================================================================
echo      SERVER CBT UJIAN MANDIRI - SMK PESANTREN BUSTANUL ULUM
echo ===============================================================================
echo  Sistem Ujian Berbasis Komputer & Android Offline (100%% Tanpa Internet)
echo ===============================================================================
echo.

:: 1. Deteksi Instalasi PHP (Mendukung Laptop Baru, XAMPP, Laragon, dll.)
where php >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PATH=D:\xampp\php;%PATH%"
    ) else if exist "C:\laragon\bin\php" (
        for /d %%d in (C:\laragon\bin\php\*) do set "PATH=%%d;%PATH%"
    ) else (
        color 0C
        echo [PERINGATAN] PHP belum terdeteksi di komputer ini.
        echo Pastikan PHP sudah terinstal atau pasang XAMPP/Laragon,
        echo kemudian tambahkan folder PHP ke Environment Variables (PATH).
        echo.
        pause
        exit /b 1
    )
)

:: 2. Masuk ke folder SERVER
cd /d "%~dp0SERVER"

:: Pastikan berkas .env ada
if not exist ".env" (
    if exist ".env.example" (
        echo [INFO] Menyiapkan berkas konfigurasi .env baru...
        copy ".env.example" ".env" >nul
        php artisan key:generate --force
    )
)

:: 3. Deteksi Otomatis Alamat IP Jaringan Wi-Fi / LAN Komputer Ini Secara Real-Time
set "DETECTED_IP=192.168.1.11"
for /f "tokens=1 delims=," %%a in ('php detect_ip.php') do (
    if not "%%a"=="" set "DETECTED_IP=%%a"
)

echo  ===============================================================================
echo   INFORMASI ALAMAT AKSES SERVER (OTOMATIS MENYESUAIKAN JARINGAN WI-FI SAAT INI)
echo  ===============================================================================
echo.
echo   [1] BROWSER KOMPUTER SERVER INI:
echo       -> http://localhost:8000
echo.
echo   [2] UNTUK SISWA & GURU (BROWSER HP / LAPTOP / APLIKASI ANDROID CBT):
echo       -> http://!DETECTED_IP!:8000
echo.
echo   [3] PANDUAN SISWA MENGGUNAKAN APLIKASI ANDROID CBT:
echo       a. Hubungkan HP siswa ke Wi-Fi / Hotspot yang sama dengan komputer ini.
echo       b. Buka aplikasi CBT Peserta di HP.
echo       c. Masukkan Server URL: http://!DETECTED_IP!:8000
echo       d. Klik "Cek Koneksi" (harus hijau / Terhubung), lalu login dengan NIS.
echo.
echo  ===============================================================================
echo   Menjalankan Server CBT di semua jaringan (Host: 0.0.0.0 Port: 8000)...
echo   (JANGAN TUTUP JENDELA INI SELAMA UJIAN BERLANGSUNG)
echo  ===============================================================================
echo.

php artisan serve --host=0.0.0.0 --port=8000

pause
