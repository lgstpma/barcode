@echo off
REM Instala el servicio cola ZPL UNIFICADA (formatos 1,9,10,13,14,21)
REM Arranca al encender la PC como SYSTEM, sin ventana.
REM Ejecutar como Administrador EN LA PC DE LAS IMPRESORAS.
REM Copia print_migrate.cfg. No pisa config.local.ps1 si ya existe.

net session >nul 2>&1
if errorlevel 1 (
  echo ERROR: ejecuta este archivo como Administrador.
  echo Clic derecho - Ejecutar como administrador.
  pause
  exit /b 1
)

set "SRC=%~dp0"
set "DST=%ProgramData%\BarcodeEtiqueta21"
set "TASK=BarcodeEtiqueta21"

if not exist "%DST%" mkdir "%DST%"
copy /Y "%SRC%print_etiqueta21.ps1" "%DST%\" >nul
copy /Y "%SRC%run_hidden.vbs" "%DST%\" >nul
if exist "%SRC%..\print_migrate.cfg" copy /Y "%SRC%..\print_migrate.cfg" "%DST%\" >nul
if not exist "%DST%\config.local.ps1" (
  if exist "%SRC%config.example.ps1" copy /Y "%SRC%config.example.ps1" "%DST%\config.local.ps1"
)

set "TR=wscript.exe //B \"%DST%\run_hidden.vbs\""

schtasks /Delete /TN "%TASK%" /F >nul 2>&1

schtasks /Create /TN "%TASK%" /SC ONSTART /DELAY 0001:00 /RU SYSTEM /RL HIGHEST /F /TR "%TR%"
if errorlevel 1 (
  echo DELAY no soportado, creando arranque inmediato...
  schtasks /Create /TN "%TASK%" /SC ONSTART /RU SYSTEM /RL HIGHEST /F /TR "%TR%"
)

if errorlevel 1 (
  echo ERROR: no se pudo crear la tarea programada.
  pause
  exit /b 1
)

echo Arrancando ahora...
schtasks /Run /TN "%TASK%"

echo.
echo Listo.
echo  - Tarea: %TASK%
echo  - Carpeta: %DST%
echo  - Cola: impresoras de print_migrate.cfg (inicio: GK420t_chica)
echo  - API: http://127.0.0.1:8080/api_print_21.php  (deje start.bat abierto)
echo  - Arranque: al encender la PC (cuenta SYSTEM)
echo.
echo Tras un git pull, vuelva a ejecutar este .bat para copiar el script y el cfg.
echo Para pararlo: Administrador - Programador de tareas - %TASK% - Finalizar.
pause
