@echo off
REM Marca esta PC como servicio: sync forzado + tarea que cierra/reinicia bats.
REM UNA vez en C:\Servicios\barcode. NO en la PC de desarrollo.
setlocal
cd /d "%~dp0.."
echo. > ".barcode_deploy"
echo Creado .barcode_deploy en:
echo   %CD%
echo.
echo Siguiente ^(Admin^): tools\install_auto_sync.bat
echo Eso sincroniza cada 5 min: si hay update, CIERRA start.bat/PHP/worker,
echo actualiza desde GitHub y vuelve a abrir start.bat.
pause
