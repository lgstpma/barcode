@echo off
REM === Script para enviar archivo ZPL a impresora Zebra ZD421 en red ===
REM === IP: 192.168.10.25 | Puerto: 9100 ===

set "IP=192.168.10.25"
set "barcode.zpl"

echo Enviando %FILE% a la impresora Zebra en %IP% ...
powershell -Command "Get-Content '%FILE%' -Raw | Out-Printer -Name '\\%IP%\9100'" >nul 2>&1

REM Método alternativo con PowerShell TCP
powershell -Command "$s=New-Object Net.Sockets.TcpClient('%IP%',9100);$stream=$s.GetStream();$bytes=[System.IO.File]::ReadAllBytes('%FILE%');$stream.Write($bytes,0,$bytes.Length);$stream.Close();$s.Close()"

echo Impresión enviada correctamente.
pause
 