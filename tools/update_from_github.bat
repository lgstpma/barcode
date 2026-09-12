@echo off
REM Sincroniza esta copia con GitHub (origin/master).
REM Llamado desde start.bat: solo actualiza archivos (NO cierra esta ventana).
REM La tarea BarcodeAutoSync usa tools\auto_sync.bat (cierra y reinicia).
setlocal EnableExtensions
cd /d "%~dp0.."
set "GIT="
set "GIT_TERMINAL_PROMPT=0"
set "UPDATED=0"

if exist "tools\.skip_update_once" (
  del /f /q "tools\.skip_update_once" >nul 2>&1
  echo [sync] omitido ^(relanzado por auto_sync^).
  exit /b 0
)
if /I "%BARCODE_SKIP_UPDATE%"=="1" (
  echo [sync] omitido ^(relanzado por auto_sync^).
  exit /b 0
)

where git >nul 2>&1
if not errorlevel 1 (
  for /f "delims=" %%I in ('where git') do (
    set "GIT=%%I"
    goto :have_git
  )
)

if exist "%ProgramFiles%\Git\cmd\git.exe" (
  set "GIT=%ProgramFiles%\Git\cmd\git.exe"
  goto :have_git
)

for /d %%D in ("%LOCALAPPDATA%\GitHubDesktop\app-*") do (
  if exist "%%D\resources\app\git\cmd\git.exe" set "GIT=%%D\resources\app\git\cmd\git.exe"
)

:have_git
if not defined GIT (
  echo [aviso] No se encontro git. Abra GitHub Desktop y haga Pull una vez.
  exit /b 0
)

if not exist ".git" (
  echo [aviso] Esta carpeta no es un clone de Git. No se puede actualizar solo.
  exit /b 0
)

echo Actualizando desde GitHub...
"%GIT%" fetch --quiet origin
if errorlevel 1 (
  echo [aviso] No se pudo conectar a GitHub. Se usa la version que ya esta.
  exit /b 0
)

set "LOCAL="
set "REMOTE="
for /f "delims=" %%H in ('"%GIT%" rev-parse HEAD') do set "LOCAL=%%H"
for /f "delims=" %%H in ('"%GIT%" rev-parse origin/master') do set "REMOTE=%%H"
if /I not "%LOCAL%"=="" if /I "%LOCAL%"=="%REMOTE%" (
  echo Listo: ya estaba al dia con GitHub.
  exit /b 0
)

if exist ".barcode_deploy" goto :force_sync
if /I "%BARCODE_FORCE_SYNC%"=="1" goto :force_sync

REM --- modo suave ---
"%GIT%" checkout -- start.bat tools\install_php.bat 2>nul
"%GIT%" pull --ff-only --quiet origin master
if errorlevel 1 (
  echo [aviso] Pull bloqueado. Reintento limpiando archivos de arranque...
  "%GIT%" checkout -- start.bat tools\install_php.bat print_service_21\install_tarea.bat print_service_21\print_etiqueta21.ps1 README.md 2>nul
  "%GIT%" pull --ff-only --quiet origin master
)
if errorlevel 1 (
  echo [aviso] No se pudo aplicar el Pull. En GitHub Desktop: Fetch + Pull.
  echo         O ejecute tools\marcar_pc_servicio.bat en esta PC.
  exit /b 0
)
set "UPDATED=1"
goto :after_sync

:force_sync
set "IPBAK=%TEMP%\barcode_print_ip_13.cfg.bak"
if exist "print_ip_13.cfg" copy /Y "print_ip_13.cfg" "%IPBAK%" >nul
"%GIT%" reset --hard origin/master >nul
if errorlevel 1 (
  echo [aviso] reset --hard fallo. Se usa la version local.
  exit /b 0
)
if exist "%IPBAK%" copy /Y "%IPBAK%" "print_ip_13.cfg" >nul
set "UPDATED=1"
echo Listo: PC de servicio igualada a GitHub ^(print_ip_13.cfg local conservado^).

:after_sync
if "%UPDATED%"=="1" (
  if not exist ".barcode_deploy" echo Listo: codigo al dia con GitHub.
  if exist "print_service_21\arrancar_worker.bat" (
    call "print_service_21\arrancar_worker.bat"
  )
)
exit /b 0
