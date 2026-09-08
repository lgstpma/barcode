@echo off
setlocal EnableExtensions
cd /d "%~dp0"
set "NOPAUSE=%~1"
set "DEST=%~dp0php"
set "ZIP=%TEMP%\php-nts-barcode.zip"
set "URL=https://windows.php.net/downloads/releases/archives/php-8.2.28-nts-Win32-vs16-x64.zip"

set "PS="
if exist "%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" (
  set "PS=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
) else if exist "%SystemRoot%\SysWOW64\WindowsPowerShell\v1.0\powershell.exe" (
  set "PS=%SystemRoot%\SysWOW64\WindowsPowerShell\v1.0\powershell.exe"
)
set "CURL="
if exist "%SystemRoot%\System32\curl.exe" set "CURL=%SystemRoot%\System32\curl.exe"
set "TAR="
if exist "%SystemRoot%\System32\tar.exe" set "TAR=%SystemRoot%\System32\tar.exe"

echo Descargando PHP 8.2 NTS (portable, ~30 MB)...
echo Esto tarda 1-2 minutos. No cierre esta ventana.

if exist "%ZIP%" del /f /q "%ZIP%" >nul 2>&1

if defined PS (
  echo Usando PowerShell...
  "%PS%" -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri '%URL%' -OutFile '%ZIP%' -UseBasicParsing"
) else if defined CURL (
  echo PowerShell no esta en PATH. Usando curl...
  "%CURL%" -L --fail -o "%ZIP%" "%URL%"
) else (
  echo [ERROR] No se encontro PowerShell ni curl.
  echo Instale Git for Windows o copie tools\php desde la PC original.
  if /I not "%NOPAUSE%"=="/nopause" pause
  exit /b 1
)
if not exist "%ZIP%" (
  echo Fallo la descarga. Revise internet o firewall.
  if /I not "%NOPAUSE%"=="/nopause" pause
  exit /b 1
)

if exist "%DEST%" rmdir /s /q "%DEST%"
mkdir "%DEST%"

if defined PS (
  "%PS%" -NoProfile -ExecutionPolicy Bypass -Command "Expand-Archive -Path '%ZIP%' -DestinationPath '%DEST%' -Force"
) else if defined TAR (
  echo Extrayendo con tar...
  "%TAR%" -xf "%ZIP%" -C "%DEST%"
) else (
  echo [ERROR] No se pudo extraer el ZIP (falta PowerShell y tar).
  if /I not "%NOPAUSE%"=="/nopause" pause
  exit /b 1
)

if exist "%DEST%\php.ini-development" copy /Y "%DEST%\php.ini-development" "%DEST%\php.ini" >nul

if defined PS (
  "%PS%" -NoProfile -ExecutionPolicy Bypass -Command ^
    "$i=Get-Content '%DEST%\php.ini' -Raw;" ^
    "$i=$i -replace ';extension=mysqli','extension=mysqli';" ^
    "$i=$i -replace ';extension=gd','extension=gd';" ^
    "$i=$i -replace ';extension=mbstring','extension=mbstring';" ^
    "$i=$i -replace ';extension=openssl','extension=openssl';" ^
    "$i=$i -replace ';extension=curl','extension=curl';" ^
    "$i=$i -replace ';extension=fileinfo','extension=fileinfo';" ^
    "$i=$i -replace ';?\s*extension_dir\s*=\s*\"ext\"','extension_dir = \"ext\"';" ^
    "Set-Content '%DEST%\php.ini' $i -Encoding ASCII"
) else (
  echo.>> "%DEST%\php.ini"
  echo extension_dir = "ext">> "%DEST%\php.ini"
  echo extension=mysqli>> "%DEST%\php.ini"
  echo extension=gd>> "%DEST%\php.ini"
  echo extension=mbstring>> "%DEST%\php.ini"
  echo extension=openssl>> "%DEST%\php.ini"
  echo extension=curl>> "%DEST%\php.ini"
  echo extension=fileinfo>> "%DEST%\php.ini"
)

if not exist "%DEST%\php.exe" (
  echo [ERROR] No quedo php.exe en %DEST%
  if /I not "%NOPAUSE%"=="/nopause" pause
  exit /b 1
)
echo OK: %DEST%\php.exe
"%DEST%\php.exe" -v
if /I not "%NOPAUSE%"=="/nopause" pause
exit /b 0
