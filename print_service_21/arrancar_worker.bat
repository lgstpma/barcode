@echo off
REM Arranca (o reinicia) el worker de cola ZPL en esta sesion (ve impresoras USB en Win7).
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

REM Matar worker viejo para cargar script nuevo (evita quedar en estado 3 eterno)
if defined WMICEXE (
  "%WMICEXE%" process where "CommandLine like '%%print_etiqueta21.ps1%%'" call terminate >nul 2>&1
)
ping -n 2 127.0.0.1 >nul

wscript.exe //B "%DST%\run_hidden.vbs"
echo  Worker print_service_21: reiniciado (cola MySQL / GK420t_chica).
echo  Log: %DST%\print_service.log
exit /b 0
