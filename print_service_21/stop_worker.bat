@echo off
REM Detiene el worker ZPL (tarea + todos los procesos print_etiqueta21.ps1).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

echo Deteniendo tarea BarcodeEtiqueta21...
schtasks /End /TN "BarcodeEtiqueta21" >nul 2>&1

echo Deteniendo proceso print_etiqueta21.ps1...
if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate >nul 2>&1
)

REM Fallback sin wmic (Win7: Get-WmiObject; Win10+: Get-CimInstance)
if defined PSHEXE (
  "%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -Command "$procs=@(); try { $procs=@(Get-CimInstance Win32_Process -EA Stop) } catch { $procs=@(Get-WmiObject Win32_Process -EA SilentlyContinue) }; foreach($p in $procs){ $c=[string]$p.CommandLine; if($c -like '*print_etiqueta21.ps1*'){ try { Stop-Process -Id $p.ProcessId -Force -EA SilentlyContinue } catch {} } }" >nul 2>&1
)

ping -n 2 127.0.0.1 >nul
echo Listo.
exit /b 0
