@echo off
set CODE=%~1
if "%CODE%"=="" (
  echo Etiqueta 10: imprime cualquier codigo (BMP + impresora de lbls).
  echo Ejemplo: enviar_prueba_directa.bat 026015
  echo.
  set /p CODE=Codigo de barras: 
)
if "%CODE%"=="" (
  echo Falta el codigo.
  pause
  exit /b 1
)
echo.
echo Imprimiendo etiqueta 10 codigo %CODE% ...
echo En Virtual ZPL usa el tamano que muestre el script (203 dpi, sin rotar).
echo No usa el servidor de produccion ni \\pclabels.
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0enviar_prueba_directa.ps1" -Codigo %CODE%
pause
