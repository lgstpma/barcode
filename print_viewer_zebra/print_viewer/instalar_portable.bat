@echo off
REM Descarga Python 3.8 portable (Win7) — NO instala nada en el sistema.
REM Solo descomprime en: portable\python\
setlocal EnableDelayedExpansion
cd /d "%~dp0"

set "PORTABLE=%~dp0portable"
set "PYDIR=%PORTABLE%\python"
set "ZIP=%PORTABLE%\python38.zip"

if exist "%PYDIR%\python.exe" (
  echo Ya existe: %PYDIR%\python.exe
  echo Si quiere reinstalar, borre la carpeta portable\ y vuelva a ejecutar.
  pause
  exit /b 0
)

if not exist "%PORTABLE%" mkdir "%PORTABLE%"

echo Detectando arquitectura...
set "ARCH=amd64"
if /I "%PROCESSOR_ARCHITECTURE%"=="x86" (
  if not defined PROCESSOR_ARCHITEW6432 set "ARCH=win32"
)

if "%ARCH%"=="win32" (
  set "URL=https://www.python.org/ftp/python/3.8.10/python-3.8.10-embed-win32.zip"
) else (
  set "URL=https://www.python.org/ftp/python/3.8.10/python-3.8.10-embed-amd64.zip"
)

echo.
echo Descargando Python 3.8.10 embeddable ^(%ARCH%^)...
echo %URL%
echo.

REM Preferir PowerShell; en Win7 a veces falla TLS — reintento con bitsadmin
set "PSHEXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
if exist "%PSHEXE%" (
  "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -Command ^
    "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; ^
     (New-Object Net.WebClient).DownloadFile('%URL%', '%ZIP%')"
)

if not exist "%ZIP%" (
  echo PowerShell fallo; intento bitsadmin...
  bitsadmin /transfer PrintViewerPython /download /priority normal "%URL%" "%ZIP%"
)

if not exist "%ZIP%" (
  echo.
  echo [ERROR] No se pudo descargar Python.
  echo En esta PC Win7 puede faltar TLS 1.2 / actualizaciones.
  echo.
  echo Alternativa manual:
  echo  1^) En otra PC con internet descargue:
  echo     %URL%
  echo  2^) Copie el zip aqui como:
  echo     %ZIP%
  echo  3^) Vuelva a ejecutar este bat.
  echo.
  pause
  exit /b 1
)

echo Descomprimiendo...
if exist "%PYDIR%" rd /s /q "%PYDIR%"
mkdir "%PYDIR%"

set "VBS=%TEMP%\pv_unzip.vbs"
(
  echo Set s = CreateObject^("Shell.Application"^)
  echo Set z = s.NameSpace^("%ZIP%"^)
  echo Set d = s.NameSpace^("%PYDIR%"^)
  echo d.CopyHere z.Items^, 16
) > "%VBS%"
cscript //nologo "%VBS%"
del "%VBS%" >nul 2>&1

REM Esperar a que termine la copia del zip
ping -n 3 127.0.0.1 >nul

if not exist "%PYDIR%\python.exe" (
  echo [ERROR] No aparecio python.exe tras descomprimir.
  pause
  exit /b 1
)

REM Habilitar import de stdlib en embeddable (python38._pth)
for %%F in ("%PYDIR%\python*._pth") do (
  "%PSHEXE%" -NoProfile -Command ^
    "$p='%%~fF'; if(Test-Path $p){ $t=Get-Content $p; $t=$t -replace '#import site','import site'; Set-Content -Path $p -Value $t -Encoding ASCII }"
)

echo.
echo OK — Python portable listo:
echo   %PYDIR%\python.exe
echo.
echo Ahora ejecute: ejecutar.bat
echo ^(mejor clic derecho - Ejecutar como administrador^)
echo.
pause
exit /b 0
