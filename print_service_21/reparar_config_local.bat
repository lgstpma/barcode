@echo off
REM Repara config.local.ps1 en produccion (AcceptPrinters en una sola linea).
REM No borra ApiUrl ni WorkerId.
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

set "CFG=%~dp0config.local.ps1"
set "BAK=%~dp0config.local.ps1.bak"

echo ========================================
echo  Reparar config.local.ps1
echo ========================================
echo.

if exist "%CFG%" (
  copy /Y "%CFG%" "%BAK%" >nul
  echo Backup: %BAK%
)

REM Mantener ApiUrl si se puede leer; si no, 127.0.0.1
set "API=http://127.0.0.1:8080/api_print_21.php"
if defined PSHEXE if exist "%CFG%" (
  for /f "usebackq delims=" %%A in (`"%PSHEXE%" -NoProfile -Command "try { . '%CFG%'; if($ApiUrl){$ApiUrl} } catch {''}"`) do set "API=%%A"
)

(
echo # Generado/reparado por reparar_config_local.bat
echo # AcceptPrinters DEBE ir en una sola linea.
echo $LocalQueueDir   = ""
echo $ApiUrl          = "%API%"
echo $PrinterDefault  = "GK420t_chica"
echo $PrinterForce    = ""
echo $PrinterFilter   = ""
echo $AcceptPrinters  = "GK420t_chica,GK420t_grande,GK420t_3x1.25,GK420t_2x3"
echo $PrinterAliases  = @{}
echo $SkipUncPrinters = $true
echo $ShowPrinterList = $true
echo $SleepSec        = 2
echo $WorkerId        = $env:COMPUTERNAME + "-zpl"
) > "%CFG%"

echo.
echo Escrito:
type "%CFG%"
echo.
echo Ahora ejecute: arrancar_worker.bat
echo Luego: diagnostico.bat  ^(apartado [4] debe decir SI^)
echo.
pause
