# Ultimas N lineas de un archivo. Compatible PowerShell 2 (Win7): sin -Tail.
$ErrorActionPreference = 'SilentlyContinue'
$path = $args[0]
$n = 15
if ($args.Count -gt 1) {
  try { $n = [int]$args[1] } catch { $n = 15 }
}
if (-not $path -or -not (Test-Path $path)) {
  Write-Host '(sin archivo)'
  exit 0
}
$lines = @(Get-Content $path)
if ($lines.Count -le $n) {
  $lines
} else {
  $lines[($lines.Count - $n)..($lines.Count - 1)]
}
