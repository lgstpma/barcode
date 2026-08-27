@echo off
net session >nul 2>&1
if errorlevel 1 (
  echo Ejecuta como Administrador.
  pause
  exit /b 1
)
schtasks /End /TN "BarcodeEtiqueta21" >nul 2>&1
schtasks /Delete /TN "BarcodeEtiqueta21" /F >nul 2>&1
echo Tarea BarcodeEtiqueta21 eliminada.
pause
