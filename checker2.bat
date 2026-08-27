@echo off
REM === Script para enviar archivo ZPL a impresora Zebra ZD421 en red ===
REM === IP: 192.168.10.43 | Puerto: 9100 ===

setlocal
set "IP=192.168.10.66"
set "FILE=%~dp0barcode.zpl"

echo Enviando %FILE% a la impresora Zebra en %IP% ...

powershell -Command ^
    "$s=New-Object Net.Sockets.TcpClient('%IP%',9100);" ^
    "$stream=$s.GetStream();" ^
    "$bytes=[System.IO.File]::ReadAllBytes('%FILE%');" ^
    "$stream.Write($bytes,0,$bytes.Length);" ^
    "$stream.Close();" ^
    "$s.Close();"

echo.
echo ✅ Impresión enviada correctamente.
pause