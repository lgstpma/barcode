@echo off
REM Sondea Zebras, imprime prueba, detecta formato y renombra a GK420t_*
setlocal
cd /d "%~dp0.."
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup_zebra_printers.ps1"
set ERR=%ERRORLEVEL%
echo.
exit /b %ERR%
