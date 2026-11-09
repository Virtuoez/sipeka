@echo off
REM Klik dua kali untuk menjalankan website (hanya bisa dibuka dari komputer ini).
REM Pertama kali di PC baru, skrip ini otomatis menyiapkan PHP, database, dan lainnya.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\siapkan.ps1"
pause
