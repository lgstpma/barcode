# Solo esta PC / solo este proyecto. No llama al servidor ni a \\pclabels.
# No fuerces impresora: cada producto usa lbls.printer.
# install_tarea.bat no copia este archivo.

$LocalQueueDir   = Join-Path $scriptDir "queue"
$ApiUrl          = ""
$PrinterDefault  = "VirtualZPLPrinter_Sistemas"
$PrinterForce    = ""
$PrinterFilter   = ""
$SkipUncPrinters = $true
$ShowPrinterList = $false

# Si en esta PC no existe el DeviceName de lbls, se redirige aqui.
$PrinterMap = @{
    "GK420t_grande" = "GK420t_3x2"
    "GK420t_2x3"    = "GK420t_3x2"
}
