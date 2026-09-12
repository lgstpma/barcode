@echo off
REM Instala tarea BarcodeAutoSync (cada 5 min).
REM Si hay commits nuevos en GitHub: cierra bats, actualiza, reinicia start.bat.
REM Ejecutar UNA vez como Administrador en PC de Servicios.
setlocal
cd /d "%~dp0.."

net session >nul 2>&1
if errorlevel 1 (
  echo ERROR: Ejecutar como Administrador.
  pause
  exit /b 1
)

if not exist ".barcode_deploy" (
  echo Primero ejecute: tools\marcar_pc_servicio.bat
  pause
  exit /b 1
)

set "TASK=BarcodeAutoSync"
set "HERE=%CD%"
set "TR=cmd.exe /c \"cd /d \"%HERE%\" ^&^& call tools\auto_sync.bat\""

schtasks /Delete /TN "%TASK%" /F >nul 2>&1
schtasks /Create /TN "%TASK%" /SC MINUTE /MO 5 /RL HIGHEST /F /TR "%TR%"
if errorlevel 1 (
  echo ERROR creando tarea.
  pause
  exit /b 1
)

echo.
echo Listo. Tarea: %TASK% cada 5 minutos.
echo Log: tools\auto_sync.log
echo.
echo Probar ahora ^(si hay update cierra y reinicia^):
call "%HERE%\tools\auto_sync.bat"
echo.
pause
