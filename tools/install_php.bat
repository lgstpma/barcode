@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
set "NOPAUSE=%~1"
set "DEST=%~dp0php"
set "ZIP=%TEMP%\php-nts-barcode.zip"
set "URL=https://windows.php.net/downloads/releases/archives/php-8.2.28-nts-Win32-vs16-x64.zip"

set "PS=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%PS%" set "PS=%SystemRoot%\SysWOW64\WindowsPowerShell\v1.0\powershell.exe"
if not exist "%PS%" set "PS="

set "CURL=%SystemRoot%\System32\curl.exe"
if not exist "%CURL%" set "CURL="
set "TAR=%SystemRoot%\System32\tar.exe"
if not exist "%TAR%" set "TAR="
set "CERT=%SystemRoot%\System32\certutil.exe"
if not exist "%CERT%" set "CERT="
set "BITS=%SystemRoot%\System32\bitsadmin.exe"
if not exist "%BITS%" set "BITS="

echo Descargando PHP 8.2 NTS (portable, ~30 MB)...
echo Esto tarda 1-2 minutos. No cierre esta ventana.

if exist "%ZIP%" del /f /q "%ZIP%" >nul 2>&1
set "GOT=0"

if defined CURL (
  echo Bajando con curl...
  "%CURL%" -L --fail --retry 2 -o "%ZIP%" "%URL%"
  if exist "%ZIP%" set "GOT=1"
)

if "!GOT!"=="0" if defined CERT (
  echo Bajando con certutil...
  "%CERT%" -urlcache -split -f "%URL%" "%ZIP%"
  if exist "%ZIP%" set "GOT=1"
)

if "!GOT!"=="0" if defined BITS (
  echo Bajando con bitsadmin...
  "%BITS%" /transfer "barcode-php" /download /priority foreground "%URL%" "%ZIP%"
  if exist "%ZIP%" set "GOT=1"
)

if "!GOT!"=="0" if defined PS (
  echo Bajando con PowerShell WebClient (sin Invoke-WebRequest)...
  "%PS%" -NoProfile -ExecutionPolicy Bypass -Command "try { [Net.ServicePointManager]::SecurityProtocol = 3072 } catch {}; (New-Object System.Net.WebClient).DownloadFile('%URL%', '%ZIP%')"
  if exist "%ZIP%" set "GOT=1"
)

for %%A in ("%ZIP%") do set "ZSIZE=%%~zA"
if not defined ZSIZE set "ZSIZE=0"
if !ZSIZE! LSS 1000000 (
  echo Fallo la descarga (archivo vacio o incompleto). Revise internet o firewall.
  echo URL: %URL%
  if /I not "%NOPAUSE%"=="/nopause" pause
  exit /b 1
)

if exist "%DEST%" rmdir /s /q "%DEST%"
mkdir "%DEST%"
set "UNZ=0"

if defined TAR (
  echo Extrayendo con tar...
  "%TAR%" -xf "%ZIP%" -C "%DEST%"
  if exist "%DEST%\php.exe" set "UNZ=1"
)

if "!UNZ!"=="0" if defined PS (
  echo Extrayendo con Shell.Application...
  "%PS%" -NoProfile -ExecutionPolicy Bypass -Command "$s=New-Object -ComObject Shell.Application; $z=$s.NameSpace('%ZIP%'); $d=$s.NameSpace('%DEST%'); if($z -and $d){$d.CopyHere($z.Items(),20); Start-Sleep -s 8}"
  if exist "%DEST%\php.exe" set "UNZ=1"
)

if not exist "%DEST%\php.exe" (
  echo [ERROR] No se pudo extraer php.exe
  if /I not "%NOPAUSE%"=="/nopause" pause
  exit /b 1
)

if exist "%DEST%\php.ini-development" copy /Y "%DEST%\php.ini-development" "%DEST%\php.ini" >nul
echo.>> "%DEST%\php.ini"
echo extension_dir = "ext">> "%DEST%\php.ini"
echo extension=mysqli>> "%DEST%\php.ini"
echo extension=gd>> "%DEST%\php.ini"
echo extension=mbstring>> "%DEST%\php.ini"
echo extension=openssl>> "%DEST%\php.ini"
echo extension=curl>> "%DEST%\php.ini"
echo extension=fileinfo>> "%DEST%\php.ini"

echo OK: %DEST%\php.exe
"%DEST%\php.exe" -v
if errorlevel 1 (
  echo [aviso] php.exe existe pero no arranco. Esta PC puede ser Windows viejo; PHP 8.2 pide Windows 10+.
)
if /I not "%NOPAUSE%"=="/nopause" pause
exit /b 0
