@echo off
REM ============================================================
REM  PC DE IMPRESORAS — apunta el worker al BARCODE de Servicios
REM ============================================================
setlocal EnableDelayedExpansion
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

REM Config junto al script (no ProgramData = sin acceso denegado)
set "CFG=%~dp0config.local.ps1"

echo.
echo IP o nombre de la PC de SERVICIOS (donde corre start_solo_web.bat)
echo Ejemplo: 192.168.1.50
echo.
set /p "SIP=IP Servicios: "
if "%SIP%"=="" (
  echo Cancelado.
  pause
  exit /b 1
)

(
  echo # Generado por configurar_api_servicios.bat
  echo # Worker en ESTA PC ^(impresoras^); API en Servicios.
  echo $ApiUrl = "http://%SIP%:8080/api_print_21.php"
  echo $LocalQueueDir = ""
) > "%CFG%"

if not exist "%CFG%" (
  echo ERROR: no se pudo escribir %CFG%
  echo Ejecute como el usuario que usa la PC ^(o Admin^).
  pause
  exit /b 1
)

echo.
echo Config escrito:
echo   %CFG%
echo   ApiUrl = http://%SIP%:8080/api_print_21.php
echo.
echo Probando API...
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -Command "try { $r = (New-Object Net.WebClient).DownloadString('http://%SIP%:8080/api_print_21.php?key=barcode21&claim=0'); if ($r -match 'ok') { 'OK: API responde' } else { 'AVISO: respuesta rara' } } catch { 'FALLO: no llega a Servicios. Revise IP, start_solo_web.bat y firewall 8080.' }"
echo.
echo Arrancar worker ahora?
choice /C SN /M "S=Si N=No"
if not errorlevel 2 (
  call "%~dp0arrancar_worker.bat"
)
echo.
echo Listo. Deje el worker corriendo en esta PC.
echo La gente imprime desde: http://%SIP%:8080/
echo Si "acceso denegado" al reiniciar: ejecute stop_worker.bat como Admin una vez.
pause
