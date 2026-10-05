@echo off
REM Arranca (o reinicia) el worker desde ESTA carpeta del repo.
REM Compatible Win7: rutas absolutas, sin -Command anidado.
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

set "WORKERPS1=%~dp0print_etiqueta21.ps1"
if not exist "%WORKERPS1%" (
  echo ERROR: falta print_etiqueta21.ps1
  echo Buscado: %WORKERPS1%
  echo.
  echo Contenido de esta carpeta:
  dir /b "%~dp0"
  echo.
  echo Si falta tras sync, en la raiz del repo ejecute:
  echo   git checkout origin/master -- print_service_21/print_etiqueta21.ps1
  pause
  exit /b 1
)

echo Deteniendo worker anterior...
call "%~dp0stop_worker.bat"

if not defined PSHEXE (
  echo ERROR: no se encontro powershell.exe
  echo Ruta esperada: %SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe
  pause
  exit /b 1
)

echo Arrancando worker desde:
echo   %WORKERPS1%
echo   PowerShell: %PSHEXE%
if exist "%~dp0config.local.ps1" (
  echo   config.local.ps1: SI
) else (
  echo   config.local.ps1: no ^(usa 127.0.0.1^)
)
if exist "%~dp0..\print_migrate.cfg" (
  echo   print_migrate.cfg: SI
)

REM Arranque Win7-safe: -File con ruta absoluta (sin -Command anidado).
REM El worker reintenta API si :8080 aun no esta listo.
start "BARCODE-worker" /MIN "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%WORKERPS1%"

ping -n 2 127.0.0.1 >nul
echo.
echo Worker unico: iniciado.
echo Log: %~dp0print_service.log
echo Para parar: stop_worker.bat
exit /b 0
