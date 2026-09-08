@echo off
REM Actualiza el proyecto desde GitHub (solo avance rapido, no borra cambios locales).
setlocal EnableExtensions
cd /d "%~dp0.."
set "GIT="
set "GIT_TERMINAL_PROMPT=0"

where git >nul 2>&1
if not errorlevel 1 (
  for /f "delims=" %%I in ('where git') do (
    set "GIT=%%I"
    goto :have_git
  )
)

if exist "%ProgramFiles%\Git\cmd\git.exe" (
  set "GIT=%ProgramFiles%\Git\cmd\git.exe"
  goto :have_git
)

for /d %%D in ("%LOCALAPPDATA%\GitHubDesktop\app-*") do (
  if exist "%%D\resources\app\git\cmd\git.exe" set "GIT=%%D\resources\app\git\cmd\git.exe"
)

:have_git
if not defined GIT (
  echo [aviso] No se encontro git. Abra GitHub Desktop y haga Pull una vez.
  exit /b 0
)

if not exist ".git" (
  echo [aviso] Esta carpeta no es un clone de Git. No se puede actualizar solo.
  exit /b 0
)

echo Actualizando desde GitHub...
"%GIT%" fetch --quiet origin
if errorlevel 1 (
  echo [aviso] No se pudo conectar a GitHub. Se usa la version que ya esta.
  exit /b 0
)

"%GIT%" pull --ff-only --quiet origin master
if errorlevel 1 (
  echo [aviso] No se pudo aplicar el Pull automatico ^(cambios locales o red^).
  echo         El sistema arranca igual. En GitHub Desktop: Fetch + Pull.
  exit /b 0
)

echo Listo: codigo al dia con GitHub.
exit /b 0
