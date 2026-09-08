@echo off
setlocal
cd /d "%~dp0"

set "PHP=%~dp0tools\php\php.exe"
set "HOST=127.0.0.1"
set "PORT=8080"

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
    echo [ERROR] Sigue faltando PHP. Ejecute tools\install_php.bat a mano.
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
echo  BARCODE - servidor local (sin XAMPP)
echo  URL: http://%HOST%:%PORT%/
echo  Carpeta: %CD%
echo  Ctrl+C para detener
echo ============================================
echo.

REM Abrir navegador
start "" "http://%HOST%:%PORT%/"

REM Usar -t . (ya estamos en la carpeta) para evitar el bug de \" al final de %%~dp0
"%PHP%" -c "%~dp0tools\php\php.ini" -S %HOST%:%PORT% -t . router.php
set ERR=%ERRORLEVEL%
if not "%ERR%"=="0" (
  echo.
  echo [ERROR] El servidor termino con codigo %ERR%
  pause
)
exit /b %ERR%
