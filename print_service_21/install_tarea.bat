@echo off
REM Instala el servicio cola ZPL UNIFICADA (formatos 1,9,10,13,14,21)
REM Arranca al encender la PC como SYSTEM, sin ventana.
REM Ejecutar como Administrador EN LA PC DE LAS IMPRESORAS.
REM NO copia config.local.ps1 (eso es solo prueba).

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
echo  - Cola unificada: 1/9/10/13/14/21 (claim atomico)
echo  - Arranque: al encender la PC (cuenta SYSTEM)
echo.
echo Para pararlo: Administrador - Programador de tareas - %TASK% - Finalizar.
pause
