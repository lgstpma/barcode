@echo off
REM Quita tareas viejas de print_service_1 / 10 / 21 y procesos worker.
REM Ejecutar como Administrador en la PC de impresoras.
setlocal
net session >nul 2>&1
if errorlevel 1 (
  echo Ejecute como Administrador.
  pause
  exit /b 1
)

cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat" 2>nul

echo Deteniendo workers...
schtasks /End /TN "BarcodeEtiqueta21" >nul 2>&1
schtasks /End /TN "BarcodeEtiqueta1" >nul 2>&1
schtasks /End /TN "BarcodeEtiqueta10" >nul 2>&1
schtasks /Delete /TN "BarcodeEtiqueta21" /F >nul 2>&1
schtasks /Delete /TN "BarcodeEtiqueta1" /F >nul 2>&1
schtasks /Delete /TN "BarcodeEtiqueta10" /F >nul 2>&1

if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate >nul 2>&1
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta1.ps1%%'" call terminate >nul 2>&1
  "%WMICEXE%" process where "CommandLine like '%%zpl_etiqueta10.ps1%%'" call terminate >nul 2>&1
)

echo.
echo Tareas viejas eliminadas.
echo Para el servicio UNICO use: arrancar_worker.bat  o  install_tarea.bat
pause
