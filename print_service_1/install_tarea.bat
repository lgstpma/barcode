@echo off
REM Instala el servicio de etiqueta 1 para que arranque al encender la PC
REM como SYSTEM (administrador) y sin ventana (no se cancela cerrando CMD).
REM Ejecutar este .bat como Administrador EN LA PC DE LA IMPRESORA.

net session >nul 2>&1
if errorlevel 1 (
  echo ERROR: ejecuta este archivo como Administrador.
  echo Clic derecho - Ejecutar como administrador.
  pause
  exit /b 1
)

set "SRC=%~dp0"
set "DST=%ProgramData%\BarcodeEtiqueta1"
set "TASK=BarcodeEtiqueta1"

if not exist "%DST%" mkdir "%DST%"
copy /Y "%SRC%print_etiqueta1.ps1" "%DST%\" >nul
copy /Y "%SRC%run_hidden.vbs" "%DST%\" >nul

set "TR=wscript.exe //B \"%DST%\run_hidden.vbs\""

schtasks /Delete /TN "%TASK%" /F >nul 2>&1

REM Al encender Windows, como SYSTEM, 1 minuto de espera (red/impresora)
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
echo  - Arranque: al encender la PC (cuenta SYSTEM)
echo  - Sin ventana: no se cierra con una X
echo.
echo Un usuario normal no puede detener un proceso de SYSTEM.
echo Para pararlo: Administrador - Programador de tareas - %TASK% - Finalizar.
pause
