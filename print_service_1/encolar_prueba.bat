@echo off
echo Encola un ZPL de prueba en print_service_1\queue (solo este proyecto).
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0encolar_prueba.ps1"
pause
