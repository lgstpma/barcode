@echo off
setlocal
cd /d "%~dp0"

set "PHP=%~dp0tools\php\php.exe"
set "HOST=127.0.0.1"
set "PORT=8080"
set "DOCROOT=%~dp0"

if not exist "%PHP%" (
  echo [ERROR] No se encontro PHP portable en tools\php\php.exe
  echo Ejecute tools\install_php.bat o copie PHP NTS a tools\php\
  pause
  exit /b 1
)

echo ============================================
echo  BARCODE - servidor local (sin XAMPP)
echo  URL: http://%HOST%:%PORT%/
echo  Carpeta: %DOCROOT%
echo  Ctrl+C para detener
echo ============================================
echo.

REM Abrir navegador
start "" "http://%HOST%:%PORT%/"

"%PHP%" -c "%~dp0tools\php\php.ini" -S %HOST%:%PORT% -t "%DOCROOT%" "%~dp0router.php"
set ERR=%ERRORLEVEL%
if not "%ERR%"=="0" (
  echo.
  echo [ERROR] El servidor termino con codigo %ERR%
  pause
)
exit /b %ERR%
