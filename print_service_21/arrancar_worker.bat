@echo off
REM Arranca (o reinicia) el worker desde ESTA carpeta del repo.
REM No usa ProgramData (evita "acceso denegado" en Win7).
REM PC de impresoras: ver impresoras USB. API puede ser remota (config.local.ps1).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

if not exist "%~dp0print_etiqueta21.ps1" (
  echo ERROR: falta print_etiqueta21.ps1
  pause
  exit /b 1
)

echo Deteniendo worker anterior...
call "%~dp0stop_worker.bat"

if not defined PSHEXE (
  echo ERROR: no se encontro powershell.exe
  pause
  exit /b 1
)

echo Arrancando worker desde:
echo   %~dp0print_etiqueta21.ps1
if exist "%~dp0config.local.ps1" (
  echo   config.local.ps1: SI
) else (
  echo   config.local.ps1: no ^(usa 127.0.0.1 — en PC impresoras ejecute configurar_api_servicios.bat^)
)
if exist "%~dp0..\print_migrate.cfg" (
  echo   print_migrate.cfg: SI
)

REM Esperar a que start.bat levante :8080 (PHP es de un solo hilo; si el worker
REM pega antes, satura y la web parece colgada).
start "BARCODE-worker" /MIN "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -Command "Start-Sleep -Seconds 4; & '%~dp0print_etiqueta21.ps1'"

ping -n 2 127.0.0.1 >nul
echo.
echo Worker unico: se inicia en ~4s (espera a que el web este en :8080).
echo Log: %~dp0print_service.log
echo Para parar: stop_worker.bat
exit /b 0
