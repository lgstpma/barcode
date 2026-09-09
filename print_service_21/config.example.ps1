# Copia a %ProgramData%\BarcodeEtiqueta21\config.local.ps1 si no existe.
# start.bat debe quedar abierto en esta PC (API en 8080).

$ApiUrl = "http://127.0.0.1:8080/api_print_21.php"
$LocalQueueDir = ""

# Si se define, pisa print_migrate.cfg. Dejar comentado para seguir el cfg.
# $AcceptPrinters = "GK420t_chica"
