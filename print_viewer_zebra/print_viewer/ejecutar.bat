@echo off
REM Visor del spooler Windows (SPL) - lo que salio a la impresora.
REM Requiere: en la Zebra, "Conservar documentos impresos".
REM Abrir http://localhost:8088
setlocal
cd /d "%~dp0"
if not defined SPOOL_DIR set "SPOOL_DIR=C:\Windows\System32\spool\PRINTERS"
if not defined ARCHIVE_DIR set "ARCHIVE_DIR=%~dp0archivo_impresiones"
if not defined PORT set "PORT=8088"
echo Spool: %SPOOL_DIR%
echo Archivo: %ARCHIVE_DIR%
echo URL: http://127.0.0.1:%PORT%/
echo.
python print_viewer.py
pause
