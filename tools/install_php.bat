@echo off
setlocal
cd /d "%~dp0"
set "DEST=%~dp0php"
set "ZIP=%TEMP%\php-nts-barcode.zip"
set "URL=https://windows.php.net/downloads/releases/archives/php-8.2.28-nts-Win32-vs16-x64.zip"

echo Descargando PHP 8.2 NTS...
powershell -NoProfile -Command "Invoke-WebRequest -Uri '%URL%' -OutFile '%ZIP%' -UseBasicParsing"
if errorlevel 1 (
  echo Fallo descarga.
  pause
  exit /b 1
)
if exist "%DEST%" rmdir /s /q "%DEST%"
mkdir "%DEST%"
powershell -NoProfile -Command "Expand-Archive -Path '%ZIP%' -DestinationPath '%DEST%' -Force"
copy /Y "%DEST%\php.ini-development" "%DEST%\php.ini" >nul
powershell -NoProfile -Command ^
  "$i=Get-Content '%DEST%\php.ini' -Raw;" ^
  "$i=$i -replace ';extension=mysqli','extension=mysqli';" ^
  "$i=$i -replace ';extension=gd','extension=gd';" ^
  "$i=$i -replace ';extension=mbstring','extension=mbstring';" ^
  "$i=$i -replace ';extension=openssl','extension=openssl';" ^
  "$i=$i -replace ';extension=curl','extension=curl';" ^
  "$i=$i -replace ';extension=fileinfo','extension=fileinfo';" ^
  "$i=$i -replace ';?\s*extension_dir\s*=\s*\"ext\"','extension_dir = \"ext\"';" ^
  "Set-Content '%DEST%\php.ini' $i -Encoding ASCII"
echo OK: %DEST%\php.exe
"%DEST%\php.exe" -v
pause
