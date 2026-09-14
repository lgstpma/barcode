@echo off
REM Diagnostico del servicio UNICO (PC impresoras).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

echo ========================================
echo  DIAGNOSTICO worker unico / impresoras
echo ========================================
echo.

echo [1] print_migrate.cfg:
if exist "%~dp0..\print_migrate.cfg" (type "%~dp0..\print_migrate.cfg") else echo    FALTA
echo.

echo [2] config.local.ps1:
if exist "%~dp0config.local.ps1" (type "%~dp0config.local.ps1") else echo    Falta — ejecute configurar_api_servicios.bat
echo.

echo [3] API local (si Servicios es otra PC, ignore fallo aqui):
if exist "%~dp0..\tools\php\php.exe" (
  "%~dp0..\tools\php\php.exe" -r "echo @file_get_contents('http://127.0.0.1:8080/api_print_21.php?key=barcode21&claim=0') ? 'OK API local' : 'API local no responde (normal si API es remota)'; echo PHP_EOL;"
) else (
  echo    PHP no en esta copia
)
echo.

echo [4] Impresoras GK420t_*:
if defined WMICEXE (
  "%WMICEXE%" printer get name 2>nul | findstr /i "GK420t"
) else (
  echo    wmic no disponible
)
echo.

echo [5] Worker:
if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" get ProcessId 2>nul | findstr /r "[0-9]"
  if errorlevel 1 echo    No hay proceso print_etiqueta21
)
echo.
echo Imagenes 10/14: solo en PC Servicios → printserver\
pause
