@echo off
REM Define PSHEXE y WMICEXE con rutas absolutas (Win7 sin PATH).
REM Uso: call "%~dp0win_paths.bat"
set "PSHEXE="
set "WMICEXE="

if exist "%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" (
  set "PSHEXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
)
if not defined PSHEXE if exist "%SystemRoot%\SysWOW64\WindowsPowerShell\v1.0\powershell.exe" (
  set "PSHEXE=%SystemRoot%\SysWOW64\WindowsPowerShell\v1.0\powershell.exe"
)
where powershell.exe >nul 2>&1
if not errorlevel 1 if not defined PSHEXE set "PSHEXE=powershell.exe"

if exist "%SystemRoot%\System32\wbem\wmic.exe" (
  set "WMICEXE=%SystemRoot%\System32\wbem\wmic.exe"
)
where wmic.exe >nul 2>&1
if not errorlevel 1 if not defined WMICEXE set "WMICEXE=wmic.exe"
