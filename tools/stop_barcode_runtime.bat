@echo off
REM Cierra runtime BARCODE: web PHP :8080 y worker print_service_21.
setlocal EnableExtensions
cd /d "%~dp0.."
call "%~dp0win_paths.bat"

echo Cerrando BARCODE runtime...

for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080" ^| findstr "LISTENING"') do (
  echo  - PHP/puerto 8080 PID %%a
  taskkill /PID %%a /F >nul 2>&1
)

if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%php.exe%%8080%%'" call terminate >nul 2>&1
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate >nul 2>&1
  "%WMICEXE%" process where "CommandLine like '%%start.bat%%'" call terminate >nul 2>&1
)

timeout /t 2 /nobreak >nul
echo Runtime detenido.
exit /b 0
