@echo off
REM Detiene el servidor PHP en el puerto 8080 (BARCODE)
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080" ^| findstr "LISTENING"') do (
  echo Cerrando PID %%a ...
  taskkill /PID %%a /F >nul 2>&1
)
echo Listo.
pause
