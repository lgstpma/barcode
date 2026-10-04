@echo off
REM Diagnostico del servicio UNICO (PC impresoras / produccion).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

echo ========================================
echo  DIAGNOSTICO worker / impresoras / cola
echo  Carpeta: %CD%
echo ========================================
echo.

echo [1] print_migrate.cfg (nombres que DEBE tener Windows):
if exist "%~dp0..\print_migrate.cfg" (type "%~dp0..\print_migrate.cfg") else echo    FALTA
echo.

echo [2] config.local.ps1 (ApiUrl debe apuntar al web que encola):
if exist "%~dp0config.local.ps1" (type "%~dp0config.local.ps1") else echo    Falta — ejecute configurar_api_servicios.bat
echo.

echo [3] Impresoras Windows con GK420 / Zebra / 2x3 / chica:
if defined PSHEXE (
  "%PSHEXE%" -NoProfile -Command "Get-Printer | Where-Object { $_.Name -match 'GK420|Zebra|2x3|chica|3x1|grande' } | Select-Object Name, PortName, DriverName | Format-Table -AutoSize"
) else (
  echo    PowerShell no encontrado
)
echo.

echo [4] Worker print_etiqueta21 corriendo?
if defined PSHEXE (
  "%PSHEXE%" -NoProfile -Command "$n=@(Get-CimInstance Win32_Process -EA SilentlyContinue | Where-Object { $_.CommandLine -like '*print_etiqueta21.ps1*' }).Count; if($n -gt 0){'    SI - procesos='+$n} else {'    NO - ejecute arrancar_worker.bat o start.bat'}"
) else (
  echo    (sin powershell)
)
echo.

echo [5] API claim (pendientes para impresoras del cfg):
if defined PSHEXE (
  "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0_probe_claim.ps1"
) else (
  echo    omitido
)
echo.

echo [6] Ultimas lineas del log:
if exist "%~dp0print_service.log" (
  powershell -NoProfile -Command "Get-Content -Path '%~dp0print_service.log' -Tail 15"
) else (
  echo    No hay print_service.log — el worker no ha arrancado aqui
)
echo.
echo Si [3] no muestra exactamente GK420t_2x3 / GK420t_chica / etc.,
echo renombre la impresora en Windows al nombre del cfg (mismo texto).
echo.
pause
