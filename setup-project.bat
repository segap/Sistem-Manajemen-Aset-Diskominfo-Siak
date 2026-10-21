@echo off
title SIMIVA - Setup Project
cd /d "%~dp0"

echo ========================================
echo  Setup SIMIVA
echo ========================================
echo.

if not exist artisan (
    echo File artisan tidak ditemukan.
    echo Jalankan setup-project.bat dari folder utama project SIMIVA.
    echo.
    pause
    exit /b 1
)

if not exist .env (
    echo Membuat file .env dari .env.example...
    copy .env.example .env >nul
)

echo.
echo Menginstall dependency PHP...
composer install
if errorlevel 1 goto failed

echo.
echo Menginstall dependency tampilan...
call npm install
if errorlevel 1 goto failed

echo.
echo Membuat APP_KEY jika belum tersedia...
php artisan key:generate --ansi
if errorlevel 1 goto failed

echo.
echo Menjalankan migrasi dan seeder...
php artisan migrate --seed
if errorlevel 1 goto failed

echo.
echo Build tampilan production...
call npm run build
if errorlevel 1 goto failed

echo.
echo Setup selesai.
echo Jalankan project dengan:
echo php artisan serve
echo atau klik run-project.bat
echo.
pause
exit /b 0

:failed
echo.
echo Setup gagal. Baca pesan error di atas.
echo Pastikan Composer, Node.js, PHP, dan database MySQL sudah aktif.
echo.
pause
exit /b 1
