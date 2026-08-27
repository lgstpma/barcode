@echo off
echo Envio ZPL directo a VirtualZPLPrinter_Sistemas (sin cola ni API).
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0enviar_prueba_directa.ps1"
pause
