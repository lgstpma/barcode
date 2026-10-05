# Lista impresoras relevantes (PS2 / Win7 OK).
$ErrorActionPreference = 'SilentlyContinue'
$list = @()
try {
  $list = @(Get-Printer -ErrorAction Stop)
} catch {
  $list = @(Get-WmiObject -Class Win32_Printer -ErrorAction SilentlyContinue)
}
$n = 0
foreach ($p in $list) {
  $name = ''
  $port = ''
  $drv = ''
  try { $name = [string]$p.Name } catch {}
  try { $port = [string]$p.PortName } catch {}
  try { $drv = [string]$p.DriverName } catch {}
  if ($name -match 'GK420|Zebra|2x3|chica|3x1|grande|ZDesigner') {
    Write-Host ("    " + $name + "  port=" + $port + "  drv=" + $drv)
    $n++
  }
}
if ($n -eq 0) {
  Write-Host '    (ninguna con nombre GK420/Zebra - listando todas...)'
  foreach ($p in $list) {
    try { Write-Host ("    " + [string]$p.Name) } catch {}
  }
}
