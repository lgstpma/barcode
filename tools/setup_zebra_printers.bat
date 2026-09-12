@echo off
REM Sondea Zebras, imprime prueba, detecta formato y renombra a GK420t_*
setlocal
cd /d "%~dp0.."
call "%~dp0win_paths.bat"
echo.
if not defined PSHEXE (
  echo ERROR: no se encontro powershell.exe
  echo Busque en: %SystemRoot%\System32\WindowsPowerShell\v1.0\
  exit /b 1
)
"%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup_zebra_printers.ps1"
set ERR=%ERRORLEVEL%
echo.
exit /b %ERR%
