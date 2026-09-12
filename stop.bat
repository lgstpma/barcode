@echo off
REM Detiene el servidor PHP y el worker de impresion BARCODE
call "%~dp0tools\stop_barcode_runtime.bat"
echo Listo.
pause
