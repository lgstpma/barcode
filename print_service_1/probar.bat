@echo off
echo Prueba LOCAL etiqueta 1 (solo barcode4.0)
echo  - Cola de archivos: print_service_1\queue
echo  - Impresora: GK420t_chica (virtual local)
echo  - No llama al servidor, ni MySQL, ni \\pclabels
echo.
echo 1) Deja esta ventana abierta
echo 2) Ejecuta encolar_prueba.bat (o enviar_prueba_directa.bat)
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0print_etiqueta1.ps1"
