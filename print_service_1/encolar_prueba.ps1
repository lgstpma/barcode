# Encola el ZPL real de etiqueta 1 (layout VB convertido) en queue\.
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptDir "zpl_etiqueta1.ps1")

$queueDir = Join-Path $scriptDir "queue"
if (-not (Test-Path $queueDir)) {
    New-Item -ItemType Directory -Path $queueDir | Out-Null
}

$zpl = Build-ZplEtiqueta1 -Codigo "025127" -Descrip "Galletas de mantequilla con chispas de chocolate" -Precio 8.5 -Cant 1 -Expir 15 -FechaManufact (Get-Date)

$name = "prueba_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".zpl"
$path = Join-Path $queueDir $name
[System.IO.File]::WriteAllText($path, $zpl, [System.Text.Encoding]::ASCII)
Write-Host "Encolado local (formato etiqueta 1): $path"
Write-Host "Impresora: GK420t_chica (virtual local). Deja abierto probar.bat."
