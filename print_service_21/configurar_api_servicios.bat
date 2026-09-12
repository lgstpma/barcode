@echo off
REM ============================================================
REM  PC DE IMPRESORAS (donde esta VB6 / Zebras USB)
REM  Configura el worker para tomar jobs del BARCODE en Servicios.
REM ============================================================
REM  En Servicios debe estar abierto: start.bat  (o start_solo_web.bat)
REM  Firewall alli: tools\abrir_red_8080.bat (Admin, una vez)
REM ============================================================
setlocal EnableDelayedExpansion
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

set "DST=%ProgramData%\BarcodeEtiqueta21"
if not exist "%DST%" mkdir "%DST%" >nul 2>&1

echo.
echo IP o nombre de la PC de SERVICIOS (donde corre start.bat)
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
  echo # Dejar que print_migrate.cfg defina AcceptPrinters
) > "%DST%\config.local.ps1"

copy /Y "%~dp0print_etiqueta21.ps1" "%DST%\" >nul
copy /Y "%~dp0run_hidden.vbs" "%DST%\" >nul
if exist "%~dp0..\print_migrate.cfg" copy /Y "%~dp0..\print_migrate.cfg" "%DST%\" >nul

echo.
echo Config escrito:
echo   %DST%\config.local.ps1
echo   ApiUrl = http://%SIP%:8080/api_print_21.php
echo.
echo Probando API...
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -Command "try { $r = (New-Object Net.WebClient).DownloadString('http://%SIP%:8080/api_print_21.php?key=barcode21&claim=0'); if ($r -match 'ok') { 'OK: API responde' } else { 'AVISO: respuesta rara: ' + $r.Substring(0,[Math]::Min(80,$r.Length)) } } catch { 'FALLO: no llega a Servicios. Revise IP, start.bat y firewall 8080.' }"
echo.
echo Arrancar worker ahora?
choice /C SN /M "S=Si N=No"
if not errorlevel 2 (
  call "%~dp0arrancar_worker.bat"
)
echo.
echo Listo. Deje el worker corriendo en esta PC.
echo La gente imprime desde: http://%SIP%:8080/
pause
