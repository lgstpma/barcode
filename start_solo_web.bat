@echo off
REM ============================================================
REM  PC DE SERVICIOS — solo web + API (sin worker local)
REM  La gente entra aqui a imprimir. El worker vive en la PC
REM  de las impresoras (VB6 / Zebras).
REM ============================================================
setlocal EnableDelayedExpansion
cd /d "%~dp0"

set "PHP=%~dp0tools\php\php.exe"
set "HOST=0.0.0.0"
set "PORT=8080"
set "LOCALURL=http://127.0.0.1:%PORT%/"

if exist "%~dp0tools\update_from_github.bat" (
  call "%~dp0tools\update_from_github.bat"
)

if not exist "%PHP%" (
  echo Falta tools\php\php.exe
  if exist "%~dp0tools\install_php.bat" call "%~dp0tools\install_php.bat" /nopause
  if not exist "%PHP%" (
    echo [ERROR] Instale PHP portable.
    pause
    exit /b 1
  )
)

echo.
echo ============================================
echo  BARCODE — SOLO WEB ^(PC Servicios^)
echo  Worker NO se arranca aqui.
echo  Impresoras: worker en la PC VB6/Zebras.
echo ============================================
echo.
echo  En esta PC:  %LOCALURL%
echo  Desde la red:
for /f "tokens=2 delims=:" %%J in ('ipconfig ^| findstr /i /c:"IPv4"') do (
  set "IP=%%J"
  set "IP=!IP: =!"
  if not "!IP!"=="" echo    http://!IP!:%PORT%/
)
echo.
echo  Si otra PC no entra: tools\abrir_red_8080.bat ^(Admin^)
echo  En PC impresoras: print_service_21\configurar_api_servicios.bat
echo  Ctrl+C detiene el web
echo ============================================
echo.

start "" "%LOCALURL%"
"%PHP%" -c "%~dp0tools\php\php.ini" -S %HOST%:%PORT% -t . router.php
set ERR=%ERRORLEVEL%
if not "%ERR%"=="0" (
  echo [ERROR] codigo %ERR%
  pause
)
exit /b %ERR%
