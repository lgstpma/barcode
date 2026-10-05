@echo off
REM Diagnostico worker / impresoras / cola (Win7 OK: sin PATH, sin Get-CimInstance).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

echo ========================================
echo  DIAGNOSTICO worker / impresoras / cola
echo  Carpeta: %CD%
echo ========================================
echo.

if not defined PSHEXE (
  echo ERROR: no se encontro powershell.exe
  echo Ruta esperada: %SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe
  pause
  exit /b 1
)
echo PowerShell: %PSHEXE%
echo.

echo [1] print_migrate.cfg (nombres que DEBE tener Windows):
if exist "%~dp0..\print_migrate.cfg" (type "%~dp0..\print_migrate.cfg") else echo    FALTA
echo.

echo [2] config.local.ps1 (ApiUrl debe apuntar al web que encola):
if exist "%~dp0config.local.ps1" (type "%~dp0config.local.ps1") else echo    Falta - ejecute configurar_api_servicios.bat
echo.

echo [3] Impresoras Windows (GK420 / Zebra / 2x3 / chica):
"%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0_diag_printers.ps1"
echo.

echo [4] Worker print_etiqueta21 corriendo?
"%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0_diag_worker.ps1"
echo.

echo [5] API claim (pendientes para impresoras del cfg):
"%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0_probe_claim.ps1"
echo.

echo [6] Ultimas lineas del log:
if exist "%~dp0print_service.log" (
  "%PSHEXE%" -NoProfile -Command "Get-Content -LiteralPath '%~dp0print_service.log' | Select-Object -Last 15"
) else (
  echo    No hay print_service.log - el worker no ha arrancado aqui
)
echo.
echo Si [3] no muestra exactamente GK420t_2x3 / GK420t_chica / etc.,
echo renombre la impresora en Windows al nombre del cfg (mismo texto).
echo.
echo Siguiente: arrancar_worker.bat  o  start.bat
echo.
pause
