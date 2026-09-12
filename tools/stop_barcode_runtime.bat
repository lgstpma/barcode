@echo off
REM Cierra runtime BARCODE: web PHP :8080 y worker print_service_21.
REM Sin pause (para tareas / sync automatico).
setlocal EnableExtensions
cd /d "%~dp0.."

echo Cerrando BARCODE runtime...

REM PHP escuchando en 8080
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080" ^| findstr "LISTENING"') do (
  echo  - PHP/puerto 8080 PID %%a
  taskkill /PID %%a /F >nul 2>&1
)

REM php -S ... 8080 por linea de comando
wmic process where "CommandLine like '%%php.exe%%8080%%'" call terminate >nul 2>&1

REM Worker PowerShell print_etiqueta21
wmic process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate >nul 2>&1

REM Consolas start.bat / cmd con titulo tipico
wmic process where "CommandLine like '%%start.bat%%'" call terminate >nul 2>&1

timeout /t 2 /nobreak >nul
echo Runtime detenido.
exit /b 0
