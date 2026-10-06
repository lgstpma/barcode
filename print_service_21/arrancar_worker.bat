@echo off
REM Arranca el worker. Win7-safe. Resuelve rutas sin duplicar carpetas.
setlocal EnableExtensions
set "BATDIR=%~dp0"
if "%BATDIR:~-1%"=="\" set "BATDIR=%BATDIR:~0,-1%"

REM Subir a la raiz del repo (carpeta que contiene start.bat)
set "ROOT=%BATDIR%"
if exist "%ROOT%\start.bat" goto :root_ok
if exist "%ROOT%\..\start.bat" (
  for %%I in ("%ROOT%\..") do set "ROOT=%%~fI"
  goto :root_ok
)
if exist "%ROOT%\..\..\start.bat" (
  for %%I in ("%ROOT%\..\..") do set "ROOT=%%~fI"
  goto :root_ok
)
:root_ok

set "SVC=%ROOT%\print_service_21"
set "WORKERPS1=%SVC%\print_etiqueta21.ps1"

cd /d "%SVC%" 2>nul
if errorlevel 1 (
  echo ERROR: no se pudo entrar a:
  echo   %SVC%
  pause
  exit /b 1
)

call "%ROOT%\tools\win_paths.bat"

if not exist "%WORKERPS1%" (
  echo ERROR: falta print_etiqueta21.ps1
  echo Buscado: %WORKERPS1%
  echo ROOT: %ROOT%
  echo BATDIR: %BATDIR%
  echo.
  echo dir print_service_21:
  dir /b "%SVC%"
  echo.
  echo Intentando restaurar desde git...
  if exist "%ROOT%\.git" (
    pushd "%ROOT%"
    git checkout origin/master -- print_service_21/print_etiqueta21.ps1 2>nul
    popd
  )
)
if not exist "%WORKERPS1%" (
  echo ERROR: sigue faltando:
  echo   %WORKERPS1%
  pause
  exit /b 1
)

echo Deteniendo worker anterior...
call "%SVC%\stop_worker.bat"

if not defined PSHEXE (
  echo ERROR: no se encontro powershell.exe
  pause
  exit /b 1
)

echo Arrancando worker desde:
echo   %WORKERPS1%
echo   PowerShell: %PSHEXE%
if exist "%SVC%\config.local.ps1" (echo   config.local.ps1: SI) else (echo   config.local.ps1: no)
if exist "%ROOT%\print_migrate.cfg" (echo   print_migrate.cfg: SI)

REM Consola a archivo (Win7): si el ps1 muere al parsear, queda el error aqui.
echo ----- %date% %time% arrancar ----->>"%SVC%\worker_console.log"
start "BARCODE-worker" /MIN cmd /c ""%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%WORKERPS1%" >>"%SVC%\worker_console.log" 2>&1"

ping -n 5 127.0.0.1 >nul
set "ALIVE=0"
"%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%SVC%\_diag_worker.ps1" | findstr /I "SI" >nul
if not errorlevel 1 set "ALIVE=1"

echo.
if "%ALIVE%"=="1" (
  echo Worker unico: iniciado y CORRIENDO.
) else (
  echo [ERROR] Worker NO quedo corriendo.
  echo.
  echo --- worker_boot.txt ---
  if exist "%SVC%\worker_boot.txt" (type "%SVC%\worker_boot.txt") else echo   ^(no existe - el ps1 ni llego a arrancar^)
  echo.
  echo --- ultimas lineas print_service.log ---
  if exist "%SVC%\_tail_log.ps1" (
    "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%SVC%\_tail_log.ps1" "%SVC%\print_service.log" 15
  ) else if exist "%SVC%\print_service.log" (
    REM Fallback PS2 sin -Tail
    "%PSHEXE%" -NoProfile -Command "$l=@(Get-Content '%SVC%\print_service.log' -EA SilentlyContinue); if($l.Count -gt 0){$l[[Math]::Max(0,$l.Count-15)..($l.Count-1)]}"
  ) else (
    echo   ^(no hay print_service.log^)
  )
  echo.
  echo --- worker_console.log ---
  if exist "%SVC%\_tail_log.ps1" (
    "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%SVC%\_tail_log.ps1" "%SVC%\worker_console.log" 20
  ) else if exist "%SVC%\worker_console.log" (
    type "%SVC%\worker_console.log"
  )
  echo.
  echo Si salio por config.local.ps1: ejecute reparar_config_local.bat
  echo Luego otra vez: arrancar_worker.bat
)
echo Log: %SVC%\print_service.log
echo Para parar: stop_worker.bat
exit /b 0
