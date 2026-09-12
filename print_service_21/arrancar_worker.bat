@echo off
REM Arranca el worker de cola ZPL en esta sesion (ve impresoras USB en Win7).
setlocal
cd /d "%~dp0"
call "%~dp0..\tools\win_paths.bat"

set "DST=%ProgramData%\BarcodeEtiqueta21"
if not exist "%DST%" mkdir "%DST%" >nul 2>&1

copy /Y "%~dp0print_etiqueta21.ps1" "%DST%\" >nul
copy /Y "%~dp0run_hidden.vbs" "%DST%\" >nul
if exist "%~dp0..\print_migrate.cfg" copy /Y "%~dp0..\print_migrate.cfg" "%DST%\" >nul
if not exist "%DST%\config.local.ps1" (
  if exist "%~dp0config.example.ps1" copy /Y "%~dp0config.example.ps1" "%DST%\config.local.ps1" >nul
)

REM Evitar duplicados
if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" get ProcessId 2>nul | findstr /r "[0-9]" >nul
  if not errorlevel 1 (
    echo  Worker print_service_21: ya estaba corriendo.
    exit /b 0
  )
)

wscript.exe //B "%DST%\run_hidden.vbs"
echo  Worker print_service_21: iniciado (cola MySQL / GK420t_chica).
exit /b 0
