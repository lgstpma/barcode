@echo off
echo ========================================
echo Cola ZPL UNIFICADA - tu PC
echo ========================================
echo  API: http://127.0.0.1:8080/api_print_21.php
echo  print_migrate.cfg (inicio: GK420t_chica = formatos 1/9/21/GTIN)
echo  #1 #9 #21 GTIN -^> GK420t_chica
echo  #10 #14 -^> impresora del job (lbls)
echo.
echo  Ctrl+C para parar. NO instala tarea.
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0print_etiqueta21.ps1"
pause
