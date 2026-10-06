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
"%GIT%" fetch origin --prune
if errorlevel 1 (
  echo [ERROR] fetch fallo. Suele ser login/credenciales o red a GitHub.
  echo         Abra GitHub Desktop en esta PC ^> Fetch origin, o inicie sesion.
  echo %date% %time% forzar_sync fetch FALLO>>"%LOG%"
  pause
  exit /b 1
)

REM Forzar ref master (a veces fetch "ok" deja origin/master viejo).
"%GIT%" fetch origin refs/heads/master:refs/remotes/origin/master 2>nul

set "LOCAL="
set "REMOTE="
set "GITHUB="
for /f "delims=" %%H in ('"%GIT%" rev-parse HEAD') do set "LOCAL=%%H"
for /f "delims=" %%H in ('"%GIT%" rev-parse origin/master') do set "REMOTE=%%H"
for /f "tokens=1" %%H in ('"%GIT%" ls-remote origin refs/heads/master 2^>nul') do set "GITHUB=%%H"
echo Local:   %LOCAL%
echo Remote:  %REMOTE%
echo GitHub:  %GITHUB%
echo.

if defined GITHUB if /I not "%REMOTE%"=="%GITHUB%" (
  echo [AVISO] origin/master local != GitHub. Re-fetch forzando master...
  "%GIT%" fetch origin +refs/heads/master:refs/remotes/origin/master
  for /f "delims=" %%H in ('"%GIT%" rev-parse origin/master') do set "REMOTE=%%H"
  echo Remote ahora: %REMOTE%
  echo.
)

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
echo [4/4] arrancando start.bat ^(PHP primero; el worker espera ~5s^)...
echo. > "tools\.skip_update_once"
start "BARCODE" /D "%CD%" cmd /k start.bat

REM Esperar a que :8080 escuche (Win7: sin timeout nativo fiable)
set "UP=0"
for /L %%I in (1,1,20) do (
  netstat -ano | findstr ":8080" | findstr "LISTENING" >nul
  if not errorlevel 1 (
    set "UP=1"
    goto :web_up
  )
  ping -n 2 127.0.0.1 >nul
)
:web_up
if "%UP%"=="1" (
  echo Web :8080 LISTENING.
) else (
  echo [AVISO] :8080 aun no escucha. Deje la ventana BARCODE abierta.
)

echo %date% %time% forzar_sync OK local=%LOCAL% remote=%REMOTE%>>"%LOG%"
echo.
echo Listo. Debe quedar en:
"%GIT%" log -1 --oneline
echo.
echo Si el hash no es el de GitHub master, el fetch no trajo el remoto.
echo En la ventana BARCODE debe verse el servidor PHP.
echo Luego: print_service_21\diagnostico.bat  ^([4]=SI^)
pause
exit /b 0
