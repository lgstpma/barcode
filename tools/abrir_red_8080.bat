@echo off
REM Abre el puerto 8080 en el firewall para que otras PCs entren a BARCODE.
REM Clic derecho - Ejecutar como administrador. Una sola vez por PC.
REM Compatible con Windows 7 y posteriores.

net session >nul 2>&1
if errorlevel 1 (
  echo ERROR: ejecute este archivo como Administrador.
  echo Clic derecho - Ejecutar como administrador.
  pause
  exit /b 1
)

echo Abriendo TCP 8080 ^(BARCODE^)...

netsh advfirewall firewall delete rule name="BARCODE 8080" >nul 2>&1
netsh advfirewall firewall add rule name="BARCODE 8080" dir=in action=allow protocol=TCP localport=8080
if errorlevel 1 (
  echo advfirewall no disponible, intento firewall clasico...
  netsh firewall add portopening protocol=TCP port=8080 name="BARCODE" mode=ENABLE
)

echo.
echo Listo. En la PC de Servicios deje start.bat abierto.
echo Desde otra PC use una de estas URLs:
echo.
setlocal EnableDelayedExpansion
for /f "tokens=2 delims=:" %%J in ('ipconfig ^| findstr /i /c:"IPv4"') do (
  set "IP=%%J"
  set "IP=!IP: =!"
  if not "!IP!"=="" echo   http://!IP!:8080/
)
echo.
pause
