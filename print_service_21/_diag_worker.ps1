# Cuenta procesos print_etiqueta21 (PS2 / Win7: WMI, no CimInstance).
$ErrorActionPreference = 'SilentlyContinue'
$n = 0
try {
  $procs = @(Get-WmiObject Win32_Process -ErrorAction SilentlyContinue)
  foreach ($p in $procs) {
    $cmd = [string]$p.CommandLine
    if ($cmd -and ($cmd -like '*print_etiqueta21.ps1*')) { $n++ }
  }
} catch {}
if ($n -gt 0) {
  Write-Host ('    SI - procesos=' + $n)
} else {
  Write-Host '    NO - ejecute arrancar_worker.bat o start.bat'
}
