@echo off
REM Detiene el worker ZPL (tarea + proceso).
REM Si dice acceso denegado: clic derecho - Ejecutar como administrador.
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

echo Deteniendo tarea BarcodeEtiqueta21...
schtasks /End /TN "BarcodeEtiqueta21" >nul 2>&1

echo Deteniendo proceso print_etiqueta21.ps1...
if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate 2>nul
  if errorlevel 1 (
    echo.
    echo AVISO: no se pudo terminar el proceso ^(acceso denegado^).
    echo Cierre la ventana del worker / probar.bat, o ejecute este bat como Administrador.
  )
) else (
  echo AVISO: wmic no disponible. Cierre el worker manualmente si sigue corriendo.
)

ping -n 2 127.0.0.1 >nul
echo Listo.
exit /b 0
