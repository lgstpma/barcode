@echo off
REM Detiene el worker ZPL (tarea + todos los procesos print_etiqueta21.ps1).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

echo Deteniendo tarea BarcodeEtiqueta21...
schtasks /End /TN "BarcodeEtiqueta21" >nul 2>&1

echo Deteniendo proceso print_etiqueta21.ps1...
if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate >nul 2>&1
)

REM Fallback sin wmic (Windows 10/11 recientes)
if defined PSHEXE (
  "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -Command "Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object { $_.Name -match 'powershell|pwsh' -and $_.CommandLine -like '*print_etiqueta21.ps1*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }" >nul 2>&1
)

ping -n 2 127.0.0.1 >nul
echo Listo.
exit /b 0
