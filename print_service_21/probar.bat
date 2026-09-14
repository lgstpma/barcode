@echo off
echo ========================================
echo  Worker UNICO - cola ZPL (todas las Zebras)
echo ========================================
echo  Lee print_migrate.cfg (chica/grande/3x1.25/3x2)
echo  NO genera formatos: solo imprime ZPL de la API
echo.
echo  Ctrl+C para parar. NO instala tarea.
echo.
call "%~dp0..\tools\win_paths.bat"
if not defined PSHEXE (
  echo ERROR: no se encontro powershell.exe
  pause
  exit /b 1
)
"%PSHEXE%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0print_etiqueta21.ps1"
pause
