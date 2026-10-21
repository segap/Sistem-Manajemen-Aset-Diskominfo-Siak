@echo off
title SIMIVA - Server Laravel
cd /d "%~dp0"

echo ========================================
echo  SIMIVA - Sistem Informasi Manajemen Aset
echo ========================================
echo.
echo Folder project:
echo %cd%
echo.

if not exist artisan (
    echo File artisan tidak ditemukan.
    echo Pastikan file ini dijalankan dari folder utama project SIMIVA.
    echo.
    pause
    exit /b 1
)

echo Menjalankan server Laravel...
echo Buka aplikasi di browser:
echo http://127.0.0.1:8000
echo.
php artisan serve

echo.
echo Server berhenti. Jika ada error, baca pesan di atas.
pause
