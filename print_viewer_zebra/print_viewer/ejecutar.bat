@echo off
REM Visor spooler Windows — usa Python portable si existe (no necesita instalar Python).
REM Requiere: Zebra → Propiedades → Avanzadas → Conservar documentos impresos.
REM Abrir http://127.0.0.1:8088
setlocal
cd /d "%~dp0"

if not defined SPOOL_DIR set "SPOOL_DIR=C:\Windows\System32\spool\PRINTERS"
if not defined ARCHIVE_DIR set "ARCHIVE_DIR=%~dp0archivo_impresiones"
if not defined PORT set "PORT=8088"

set "PY="
if exist "%~dp0portable\python\python.exe" set "PY=%~dp0portable\python\python.exe"
if not defined PY where python >nul 2>&1 && set "PY=python"
if not defined PY where py >nul 2>&1 && set "PY=py"

if not defined PY (
  echo No hay Python.
  echo Ejecute primero: instalar_portable.bat
  echo ^(descarga Python 3.8 portable, sin instalar en Windows^)
  pause
  exit /b 1
)

echo Python: %PY%
echo Spool:  %SPOOL_DIR%
echo URL:    http://127.0.0.1:%PORT%/
echo.
"%PY%" "%~dp0print_viewer.py"
pause
