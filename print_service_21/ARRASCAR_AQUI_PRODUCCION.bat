@echo off
REM Arranque rapido en produccion (C:\Servicios\barcode): worker + mensaje claro.
setlocal
cd /d "%~dp0"
echo.
echo ============================================
echo  PRODUCCION - arrancar worker de impresion
echo  Carpeta: %CD%
echo ============================================
echo.
echo Impresoras OK si diagnostico mostro GK420t_chica / _2x3 / etc.
echo Ahora hay que dejar CORRIENDO el worker.
echo.
call "%~dp0arrancar_worker.bat"
echo.
echo Si ve "Acepta impresoras" en el log, ya puede imprimir.
echo Log: %~dp0print_service.log
echo.
echo Deje start.bat corriendo para el web (:8080):
echo   %~dp0..\start.bat
echo.
pause
