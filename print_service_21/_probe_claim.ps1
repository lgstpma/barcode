# Probe API claim - PS2 compatible ($PSScriptRoot puede ser null).
$ErrorActionPreference = 'Continue'
if (-not $PSScriptRoot) {
  $PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
}
$api = 'http://127.0.0.1:8080/api_print_21.php'
$localCfg = Join-Path $PSScriptRoot 'config.local.ps1'
if (Test-Path $localCfg) {
  try { . $localCfg } catch {}
  if ($ApiUrl -and $ApiUrl -ne '') {
    $api = $ApiUrl
    if ($api.IndexOf('?') -ge 0) { $api = $api.Substring(0, $api.IndexOf('?')) }
  }
}
$cfg = Join-Path $PSScriptRoot '..\print_migrate.cfg'
$printers = @()
if (Test-Path $cfg) {
  Get-Content $cfg | ForEach-Object {
    $t = ([string]$_).Trim()
    if ($t -ne '' -and $t[0] -ne '#') { $printers += $t }
  }
}
if ($printers.Count -eq 0) {
  $printers = @('GK420t_chica','GK420t_2x3','GK420t_3x1.25','GK420t_grande')
}
$csv = [string]::Join(',', $printers)
Write-Host ('    API: ' + $api)
Write-Host ('    printers=' + $csv)
try {
  $url = $api + '?key=barcode21&claim=0&printers=' + [System.Uri]::EscapeDataString($csv)
  $wc = New-Object System.Net.WebClient
  $wc.Encoding = [System.Text.Encoding]::UTF8
  $raw = $wc.DownloadString($url)
  $wc.Dispose()
  if ($raw -match '"ok"\s*:\s*true') {
    $count = '?'
    if ($raw -match '"count"\s*:\s*(\d+)') { $count = $Matches[1] }
    Write-Host ('    OK API - pendientes visibles count=' + $count)
    # Mostrar primero jobs de impresoras vivas (2x3, chica, etc.)
    $shown = 0
    $rx = [regex]'"id"\s*:\s*(\d+)[^}]*?"itemid"\s*:\s*"([^"]*)"[^}]*?"etiqueta"\s*:\s*"([^"]*)"[^}]*?"printer"\s*:\s*"([^"]*)"'
    foreach ($m in $rx.Matches($raw)) {
      $pr = $m.Groups[4].Value
      if ($pr -notmatch 'GK420t_(chica|2x3|3x1|grande)') { continue }
      if ($shown -ge 8) { break }
      Write-Host ('      id=' + $m.Groups[1].Value + ' etiq=' + $m.Groups[3].Value + ' printer=' + $pr + ' item=' + $m.Groups[2].Value)
      $shown++
    }
    if ($shown -eq 0) {
      Write-Host '      (no hay pendientes con printer GK420t_chica/2x3/3x1.25/grande en el lote)'
      Write-Host '      Puede haber jobs viejos con printer=GK420t_3x2 (impresora que no existe).'
    }
  } else {
    Write-Host '    API respondio pero no ok=true'
  }
} catch {
  Write-Host ('    FALLO API: ' + $_.Exception.Message)
  Write-Host '    Arranque start.bat (web :8080) en ESTA PC'
}
