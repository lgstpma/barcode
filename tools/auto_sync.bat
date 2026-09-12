@echo off
REM Sync automatico para PC de servicio (.barcode_deploy):
REM  1) fetch GitHub
REM  2) si hay commits nuevos: CIERRA start.bat / PHP / worker, actualiza, REINICIA
REM  3) si ya esta al dia: no toca nada
REM Usado por la tarea BarcodeAutoSync cada 5 minutos.
setlocal EnableExtensions
cd /d "%~dp0.."
set "LOG=%CD%\tools\auto_sync.log"
set "GIT_TERMINAL_PROMPT=0"

call :log "---- auto_sync begin ----"

if not exist ".barcode_deploy" (
  call :log "sin .barcode_deploy - salir"
  exit /b 0
)

set "GIT="
where git >nul 2>&1
if not errorlevel 1 (
  for /f "delims=" %%I in ('where git') do (
    set "GIT=%%I"
    goto :have_git
  )
)
if exist "%ProgramFiles%\Git\cmd\git.exe" set "GIT=%ProgramFiles%\Git\cmd\git.exe"
if not defined GIT (
  for /d %%D in ("%LOCALAPPDATA%\GitHubDesktop\app-*") do (
    if exist "%%D\resources\app\git\cmd\git.exe" set "GIT=%%D\resources\app\git\cmd\git.exe"
  )
)

:have_git
if not defined GIT (
  call :log "git no encontrado"
  exit /b 0
)
if not exist ".git" (
  call :log "no es clone git"
  exit /b 0
)

"%GIT%" fetch --quiet origin
if errorlevel 1 (
  call :log "fetch fallo"
  exit /b 0
)

set "LOCAL="
set "REMOTE="
for /f "delims=" %%H in ('"%GIT%" rev-parse HEAD') do set "LOCAL=%%H"
for /f "delims=" %%H in ('"%GIT%" rev-parse origin/master') do set "REMOTE=%%H"

if "%LOCAL%"=="" (
  call :log "no se pudo leer HEAD"
  exit /b 0
)
if "%REMOTE%"=="" (
  call :log "no se pudo leer origin/master"
  exit /b 0
)

if /I "%LOCAL%"=="%REMOTE%" (
  call :log "ya al dia %LOCAL%"
  exit /b 0
)

call :log "UPDATE disponible local=%LOCAL% remote=%REMOTE%"
call :log "cerrando bats / PHP / worker..."
call "%~dp0stop_barcode_runtime.bat"

set "IPBAK=%TEMP%\barcode_print_ip_13.cfg.bak"
if exist "print_ip_13.cfg" copy /Y "print_ip_13.cfg" "%IPBAK%" >nul

"%GIT%" reset --hard origin/master >nul
if errorlevel 1 (
  call :log "reset --hard fallo"
  goto :restart_anyway
)
if exist "%IPBAK%" copy /Y "%IPBAK%" "print_ip_13.cfg" >nul
call :log "codigo actualizado a origin/master"

:restart_anyway
REM Worker primero
if exist "print_service_21\arrancar_worker.bat" (
  call "print_service_21\arrancar_worker.bat"
  call :log "worker reiniciado"
)

REM Web: nueva ventana start.bat (flag evita loop de update al relanzar)
echo. > "tools\.skip_update_once"
start "BARCODE" /D "%CD%" cmd /k start.bat
call :log "start.bat relanzado"
call :log "---- auto_sync done ----"
exit /b 0

:log
echo %date% %time% %~1>>"%LOG%"
echo %~1
goto :eof
