@echo off
REM Copia imagenes de producto (BMP/PNG/JPG) a printserver\ de este BARCODE.
REM Usar en la PC SERVICIOS (donde corre start_solo_web.bat).
REM Formatos 10/14 leen: printserver\{codigo}.png|.bmp|.jpg
setlocal
cd /d "%~dp0.."

set "DST=%CD%\printserver"
if not exist "%DST%" mkdir "%DST%"

echo.
echo Destino: %DST%
echo.
echo Pegue la carpeta ORIGEN (USB u otra PC) que tenga los .png/.bmp
echo Ejemplo: D:\backup\printserver
echo.
set /p "SRC=Carpeta origen: "
if "%SRC%"=="" (
  echo Cancelado.
  pause
  exit /b 1
)
if not exist "%SRC%\" (
  echo No existe: %SRC%
  pause
  exit /b 1
)

echo.
echo Copiando png/bmp/jpg ...
xcopy /Y /D "%SRC%\*.png" "%DST%\" >nul 2>&1
xcopy /Y /D "%SRC%\*.PNG" "%DST%\" >nul 2>&1
xcopy /Y /D "%SRC%\*.bmp" "%DST%\" >nul 2>&1
xcopy /Y /D "%SRC%\*.BMP" "%DST%\" >nul 2>&1
xcopy /Y /D "%SRC%\*.jpg" "%DST%\" >nul 2>&1
xcopy /Y /D "%SRC%\*.jpeg" "%DST%\" >nul 2>&1

echo.
echo Listo. Archivos en printserver:
dir /b "%DST%\*.png" "%DST%\*.bmp" "%DST%\*.jpg" 2>nul | find /c /v ""
echo.
pause
