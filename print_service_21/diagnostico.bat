@echo off
REM Diagnostico rapido chica / cola ZPL en la PC de Servicios.
REM Ejecutar con start.bat YA abierto.
setlocal
cd /d "%~dp0.."

echo ========================================
echo  DIAGNOSTICO print_service_21 / chica
echo ========================================
echo.

echo [1] Puerto 8080 (start.bat debe estar abierto):
netstat -ano | findstr ":8080" | findstr "LISTENING"
if errorlevel 1 (
  echo    NO escucha 8080. Abra start.bat y deje la ventana abierta.
) else (
  echo    OK: hay listener en 8080.
)
echo.

echo [2] API local:
"%CD%\tools\php\php.exe" -r "echo @file_get_contents('http://127.0.0.1:8080/api_print_21.php?key=barcode21&claim=0') ? 'OK respuesta API' : 'FALLO API'; echo PHP_EOL;"
echo.

echo [3] Ultimos jobs MySQL (isabel_zpl_queue):
"%CD%\tools\php\php.exe" -r "echo @file_get_contents('http://127.0.0.1:8080/queue_diag.php?key=barcode21');"
echo.

echo [4] Tarea programada BarcodeEtiqueta21:
schtasks /Query /TN "BarcodeEtiqueta21" /FO LIST /V 2>nul
if errorlevel 1 echo    NO instalada. Ejecute print_service_21\install_tarea.bat como Admin.
echo.

echo [5] Impresoras Windows (nombre exacto debe ser GK420t_chica):
wmic printer get name
echo.

echo [6] Log del servicio (si existe):
if exist "%ProgramData%\BarcodeEtiqueta21\print_service.log" (
  echo --- ultimas lineas ProgramData ---
  powershell -NoProfile -Command "Get-Content '%ProgramData%\BarcodeEtiqueta21\print_service.log' -Tail 30"
) else if exist "%~dp0print_service.log" (
  echo --- ultimas lineas carpeta repo ---
  powershell -NoProfile -Command "Get-Content '%~dp0print_service.log' -Tail 30"
) else (
  echo    No hay log. El worker no ha arrancado o no pudo escribir.
)
echo.

echo [7] print_migrate.cfg en ProgramData:
if exist "%ProgramData%\BarcodeEtiqueta21\print_migrate.cfg" (
  type "%ProgramData%\BarcodeEtiqueta21\print_migrate.cfg"
) else (
  echo    FALTA. Vuelva a ejecutar install_tarea.bat
)
echo.
echo Si la tarea corre como SYSTEM y no imprime, reinstalela (install_tarea.bat)
echo para que use el usuario actual, o pruebe con probar.bat en esta sesion.
echo.
pause
