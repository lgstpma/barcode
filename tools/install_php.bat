@echo off
setlocal EnableExtensions
cd /d "%~dp0"
set "NOPAUSE=%~1"
set "DEST=%~dp0php"
set "ZIP=%TEMP%\php-nts-barcode.zip"
set "URL=https://windows.php.net/downloads/releases/archives/php-8.2.28-nts-Win32-vs16-x64.zip"

echo Descargando PHP 8.2 NTS (portable)...
echo Esto tarda 1-2 minutos. No cierre esta ventana.

set "CURL="
set "TAR="
call :find_curl
if exist "%SystemRoot%\System32\curl.exe" if not defined CURL set "CURL=%SystemRoot%\System32\curl.exe"
if exist "%SystemRoot%\System32\tar.exe" set "TAR=%SystemRoot%\System32\tar.exe"

if exist "%ZIP%" del /f /q "%ZIP%" >nul 2>&1

if defined CURL (
  echo Bajando con curl:
  echo   %CURL%
  "%CURL%" -L --fail --retry 3 --ssl-no-revoke -o "%ZIP%" "%URL%"
  if errorlevel 1 "%CURL%" -L --fail --retry 3 -o "%ZIP%" "%URL%"
) else (
  echo [ERROR] No hay curl. Esta PC no puede bajar HTTPS con bitsadmin.
  goto :copy_hint
)

if not exist "%ZIP%" goto :copy_hint
set "ZSIZE=0"
for %%A in ("%ZIP%") do set "ZSIZE=%%~zA"
if "%ZSIZE%"=="" goto :copy_hint
if %ZSIZE% LSS 1000000 goto :copy_hint

if exist "%DEST%" rmdir /s /q "%DEST%"
mkdir "%DEST%"

if defined TAR (
  echo Extrayendo...
  "%TAR%" -xf "%ZIP%" -C "%DEST%"
)

if not exist "%DEST%\php.exe" if defined CURL (
  for %%C in ("%CURL%") do set "TAR2=%%~dpCtar.exe"
)
if not exist "%DEST%\php.exe" if defined TAR2 if exist "%TAR2%" "%TAR2%" -xf "%ZIP%" -C "%DEST%"

if not exist "%DEST%\php.exe" goto :copy_hint

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
if /I not "%NOPAUSE%"=="/nopause" pause
exit /b 0

:copy_hint
echo.
echo No se pudo bajar PHP desde internet (error TLS / bitsadmin 0x80072f7d).
echo En la PC que YA funciona, copie toda la carpeta:
echo   tools\php
echo a esta PC, en:
echo   %DEST%
echo Debe quedar: %DEST%\php.exe
echo.
if /I not "%NOPAUSE%"=="/nopause" pause
exit /b 1

:find_curl
if exist "%ProgramFiles%\Git\mingw64\bin\curl.exe" (
  set "CURL=%ProgramFiles%\Git\mingw64\bin\curl.exe"
  if exist "%ProgramFiles%\Git\usr\bin\tar.exe" set "TAR=%ProgramFiles%\Git\usr\bin\tar.exe"
  goto :eof
)
for /d %%D in ("%LOCALAPPDATA%\GitHubDesktop\app-*") do (
  if exist "%%D\resources\app\git\mingw64\bin\curl.exe" (
    set "CURL=%%D\resources\app\git\mingw64\bin\curl.exe"
    if exist "%%D\resources\app\git\usr\bin\tar.exe" set "TAR=%%D\resources\app\git\usr\bin\tar.exe"
  )
)
goto :eof
