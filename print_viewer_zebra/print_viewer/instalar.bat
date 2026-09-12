@echo off
REM Instala dependencias del Print Viewer Zebra (una vez).
setlocal
cd /d "%~dp0"
where python >nul 2>&1
if errorlevel 1 (
  echo Instale Python 3.8+ y vuelva a ejecutar.
  pause
  exit /b 1
)
python -m pip install -r requirements.txt
echo.
echo Listo. Luego: ejecutar.bat
pause
