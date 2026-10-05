@echo off
REM Fuerza esta carpeta a origin/master y reinicia worker + start.bat.
REM Usar en la PC de impresoras / servicios cuando no actualiza sola.
REM NO usar en la PC de desarrollo si hay trabajo sin push.
setlocal EnableExtensions
cd /d "%~dp0.."
set "GIT_TERMINAL_PROMPT=0"
set "LOG=%CD%\tools\auto_sync.log"

echo ============================================
echo  BARCODE - forzar sync a origin/master
echo  Carpeta: %CD%
echo ============================================
echo.

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
  echo [ERROR] No se encontro git. Abra GitHub Desktop e inicie sesion.
  pause
  exit /b 1
)
if not exist ".git" (
  echo [ERROR] Esta carpeta no es un clone git: %CD%
  pause
  exit /b 1
)

echo Git: %GIT%
"%GIT%" remote -v
echo.

echo [1/4] fetch origin...
"%GIT%" fetch origin
if errorlevel 1 (
  echo [ERROR] fetch fallo. Suele ser login/credenciales o red a GitHub.
  echo         Abra GitHub Desktop en esta PC ^> Fetch origin, o inicie sesion.
  echo %date% %time% forzar_sync fetch FALLO>>"%LOG%"
  pause
  exit /b 1
)

set "LOCAL="
set "REMOTE="
for /f "delims=" %%H in ('"%GIT%" rev-parse HEAD') do set "LOCAL=%%H"
for /f "delims=" %%H in ('"%GIT%" rev-parse origin/master') do set "REMOTE=%%H"
echo Local:  %LOCAL%
echo Remote: %REMOTE%
echo.

if /I "%LOCAL%"=="%REMOTE%" (
  echo Ya estaba en origin/master. Igual se reinicia runtime.
) else (
  echo [2/4] reset --hard origin/master ^(descarta commits locales de esta PC^)...
  set "IPBAK=%TEMP%\barcode_print_ip_13.cfg.bak"
  if exist "print_ip_13.cfg" copy /Y "print_ip_13.cfg" "%IPBAK%" >nul
  "%GIT%" reset --hard origin/master
  if errorlevel 1 (
    echo [ERROR] reset --hard fallo.
    echo %date% %time% forzar_sync reset FALLO>>"%LOG%"
    pause
    exit /b 1
  )
  if exist "%IPBAK%" copy /Y "%IPBAK%" "print_ip_13.cfg" >nul
)

echo.
echo [3/4] cerrando PHP/worker...
call "%~dp0stop_barcode_runtime.bat"

REM Asegurar worker ps1 presente tras reset (Win7 / antivirus a veces lo deja a medias)
if not exist "print_service_21\print_etiqueta21.ps1" (
  echo [AVISO] Faltaba print_etiqueta21.ps1 — restaurando desde origin/master...
  "%GIT%" checkout origin/master -- print_service_21/print_etiqueta21.ps1
)
if not exist "print_service_21\print_etiqueta21.ps1" (
  echo [ERROR] Sigue faltando print_service_21\print_etiqueta21.ps1
  echo         Revise antivirus o ejecute: dir print_service_21
  pause
  exit /b 1
)

echo.
echo [4/4] arrancando worker + start.bat...
if exist "print_service_21\arrancar_worker.bat" (
  call "print_service_21\arrancar_worker.bat"
) else (
  echo [ERROR] falta print_service_21\arrancar_worker.bat
)
echo. > "tools\.skip_update_once"
start "BARCODE" /D "%CD%" cmd /k start.bat

echo %date% %time% forzar_sync OK local=%LOCAL% remote=%REMOTE%>>"%LOG%"
echo.
echo Listo. Debe quedar en:
"%GIT%" log -1 --oneline
echo.
echo Si el hash no es el de GitHub master, el fetch no trajo el remoto.
pause
exit /b 0
