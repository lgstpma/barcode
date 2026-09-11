@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

set "PHP=%~dp0tools\php\php.exe"
REM 0.0.0.0 = accesible desde otras PCs de la red. El navegador local usa 127.0.0.1.
set "HOST=0.0.0.0"
set "PORT=8080"
set "LOCALURL=http://127.0.0.1:%PORT%/"

if exist "%~dp0tools\update_from_github.bat" (
  call "%~dp0tools\update_from_github.bat"
)

if not exist "%PHP%" (
  echo No esta tools\php\php.exe ^(GitHub no lo sube^).
  echo Se va a descargar PHP portable ahora...
  echo.
  if not exist "%~dp0tools\install_php.bat" (
    echo [ERROR] Falta tools\install_php.bat
    pause
    exit /b 1
  )
  call "%~dp0tools\install_php.bat" /nopause
  if not exist "%PHP%" (
    echo [ERROR] Sigue faltando PHP.
    echo Copie tools\php de la PC que ya imprime, a:
    echo   %~dp0tools\php
    echo Debe existir: %PHP%
    pause
    exit /b 1
  )
)

if not exist "%~dp0router.php" (
  echo [ERROR] No se encontro router.php en %CD%
  pause
  exit /b 1
)

echo.
echo ============================================
echo  BARCODE - arranque unificado
echo ============================================
echo  1^) Chequeo impresoras migradas + formatos
echo  2^) Worker cola ZPL ^(print_service_21^)
echo  3^) Servidor web en :%PORT%
echo ============================================
echo.

"%PHP%" "%~dp0tools\check_migrate_printers.php"
set "CHK=%ERRORLEVEL%"
if not "%CHK%"=="0" (
  echo.
  echo [AVISO] Hay problemas con la impresora migrada.
  echo Si acaba de conectar la Zebra, instalela como GK420t_chica y reabra start.bat.
  echo.
  choice /C SN /M "Continuar de todos modos (S=Si N=No)"
  if errorlevel 2 exit /b 1
  if errorlevel 1 goto :after_check
)
:after_check

echo.
if exist "%~dp0print_service_21\arrancar_worker.bat" (
  call "%~dp0print_service_21\arrancar_worker.bat"
) else (
  echo [AVISO] Falta print_service_21\arrancar_worker.bat
)

echo.
echo ============================================
echo  Servidor web
echo  En esta PC:  %LOCALURL%
echo  Puerto:      %PORT%  (escucha en todas las IPs)
echo  Carpeta:     %CD%
echo  Desde otra PC:
for /f "tokens=2 delims=:" %%J in ('ipconfig ^| findstr /i /c:"IPv4"') do (
  set "IP=%%J"
  set "IP=!IP: =!"
  if not "!IP!"=="" echo    http://!IP!:%PORT%/
)
echo  Ctrl+C detiene el web ^(el worker sigue en segundo plano^)
echo ============================================
echo.
echo Si otra PC no entra: tools\abrir_red_8080.bat ^(Admin, una vez^)
echo.

start "" "%LOCALURL%"

"%PHP%" -c "%~dp0tools\php\php.ini" -S %HOST%:%PORT% -t . router.php
set ERR=%ERRORLEVEL%
if not "%ERR%"=="0" (
  echo.
  echo [ERROR] El servidor termino con codigo %ERR%
  pause
)
exit /b %ERR%
