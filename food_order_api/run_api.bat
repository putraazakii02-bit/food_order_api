@echo off
title FoodieGo REST API Server - Tugas Pak Bonda
echo ========================================================
echo    FoodieGo REST API Server (Laragon & MySQL)
echo    Tugas Pemrograman Mobile - Pak Bonda (Kelas F)
echo ========================================================
echo.

SET PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe

IF EXIST "%PHP_PATH%" (
    SET PHP_CMD="%PHP_PATH%"
) ELSE (
    SET PHP_CMD=php
)

echo [PENTING] Pastikan Laragon sudah dalam kondisi "Start All" agar MySQL aktif.
echo Menggunakan PHP: %PHP_CMD%
echo.
echo [1] API Server: http://localhost:8000
echo [2] Interactive Test Dashboard: http://localhost:8000/test_dashboard.html
echo.
echo Tekan CTRL + C untuk menghentikan server.
echo ========================================================
echo.

%PHP_CMD% -S 0.0.0.0:8000 index.php
pause
