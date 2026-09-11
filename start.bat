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

echo ============================================
echo  BARCODE - servidor (sin XAMPP)
echo  En esta PC:  %LOCALURL%
echo  Puerto:      %PORT%  (escucha en todas las IPs)
echo  Carpeta:     %CD%
echo  Desde otra PC:
for /f "tokens=2 delims=:" %%J in ('ipconfig ^| findstr /i /c:"IPv4"') do (
  set "IP=%%J"
  set "IP=!IP: =!"
  if not "!IP!"=="" echo    http://!IP!:%PORT%/
)
echo  Ctrl+C para detener
echo ============================================
echo.
echo Si otra PC no entra, ejecute como Administrador:
echo   tools\abrir_red_8080.bat
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
