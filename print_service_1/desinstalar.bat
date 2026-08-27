@echo off
net session >nul 2>&1
if errorlevel 1 (
  echo Ejecuta como Administrador.
  pause
  exit /b 1
)
schtasks /End /TN "BarcodeEtiqueta1" >nul 2>&1
schtasks /Delete /TN "BarcodeEtiqueta1" /F >nul 2>&1
echo Tarea BarcodeEtiqueta1 eliminada.
pause
