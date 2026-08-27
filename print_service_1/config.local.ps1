# Solo esta PC / solo este proyecto. No llama al servidor ni a MySQL.
# Impresora virtual ZPL 3.3.0: en GK420t_chica usa etiqueta 1.00" ancho x 0.50" alto, 203 dpi, sin rotar.
# install_tarea.bat no copia este archivo.

$LocalQueueDir   = Join-Path $scriptDir "queue"
$ApiUrl          = ""
$PrinterDefault  = "GK420t_chica"
$PrinterForce    = "GK420t_chica"
$PrinterFilter   = ""
$SkipUncPrinters = $true
$ShowPrinterList = $false
