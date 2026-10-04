# Probes API claim for migrate printers (no marca jobs).
$ErrorActionPreference = 'Continue'
$api = 'http://127.0.0.1:8080/api_print_21.php'
$cfg = Join-Path $PSScriptRoot '..\print_migrate.cfg'
$printers = @()
if (Test-Path $cfg) {
  Get-Content $cfg | ForEach-Object {
    $t = $_.Trim()
    if ($t -and $t[0] -ne '#') { $printers += $t }
  }
}
if ($printers.Count -eq 0) {
  $printers = @('GK420t_chica','GK420t_2x3','GK420t_3x1.25','GK420t_grande')
}
$csv = [string]::Join(',', $printers)
Write-Host ("    API: $api")
Write-Host ("    printers=$csv")
try {
  $url = $api + '?key=barcode21&claim=0&printers=' + [uri]::EscapeDataString($csv)
  $r = Invoke-WebRequest -Uri $url -TimeoutSec 8 -UseBasicParsing
  $j = $r.Content | ConvertFrom-Json
  if ($j.ok) {
    Write-Host ("    OK API — pendientes visibles count=" + $j.count)
    if ($j.jobs) {
      $j.jobs | Select-Object -First 5 | ForEach-Object {
        Write-Host ("      id=$($_.id) etiq=$($_.etiqueta) printer=$($_.printer) item=$($_.itemid)")
      }
    }
  } else {
    Write-Host '    API respondio ok=false'
  }
} catch {
  Write-Host ('    FALLO API: ' + $_.Exception.Message)
  Write-Host '    Arranque start.bat (web :8080) en ESTA PC, o corrija ApiUrl en config.local.ps1'
}
