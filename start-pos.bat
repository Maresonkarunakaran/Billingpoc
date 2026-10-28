@echo off
title POS 2.0 - Server Launcher
echo ========================================================
echo Starting POS 2.0 Multi-Branch ERP ^& POS Application
echo ========================================================

set "PHP_PATH=C:\Users\ELCOT\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe"
set "MARIADB_PATH=C:\Program Files\MariaDB 13.0\bin"
set "DATA_DIR=d:\pos 2.0\mariadb_data"
set "APP_DIR=d:\pos 2.0"
set "PATH=%PHP_PATH%;%PATH%"

echo [1/2] Checking MariaDB Database Server...
netstat -ano | findstr :3306 >nul
if %errorlevel% neq 0 (
    echo Starting MariaDB daemon on port 3306...
    start "MariaDB Server" /min "%MARIADB_PATH%\mariadbd.exe" --datadir="%DATA_DIR%" --port=3306 --console
    timeout /t 3 >nul
) else (
    echo MariaDB is already running.
)

echo [2/2] Starting Laravel Web Server on http://127.0.0.1:8000 ...
cd /d "%APP_DIR%"
echo Open your browser at: http://127.0.0.1:8000
php -d memory_limit=512M artisan serve --host=127.0.0.1 --port=8000
pause
