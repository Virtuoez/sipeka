@echo off
REM Untuk PC kelurahan: website bisa dibuka juga dari PC/HP lain yang satu WiFi/LAN kantor.
REM Jangan dipakai di WiFi umum (kos, kafe), karena orang lain di jaringan itu bisa membukanya.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\siapkan.ps1" -HostIp 0.0.0.0
pause
