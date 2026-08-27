@echo off
REM === Script para enviar archivo ZPL a impresora Zebra GK420t por USB compartida ===

setlocal
set "PRINTER=\\PCLABELS\GK420t_chica"
set "FILE=%~dp0chica.zpl"

echo Enviando %FILE% a la impresora Zebra %PRINTER% ...

copy /b "%FILE%" "%PRINTER%"

if errorlevel 1 (
    echo.
    echo ERROR: No se pudo enviar la impresion.
) else (
    echo.
    echo ✅ Impresion enviada correctamente.
)

pause