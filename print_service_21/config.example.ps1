# Copia a %ProgramData%\BarcodeEtiqueta21\config.local.ps1
#
# CASO A — todo en la misma PC (API + impresoras):
# $ApiUrl = "http://127.0.0.1:8080/api_print_21.php"
#
# CASO B — migracion: web en Servicios, worker en PC de impresoras (VB6):
# Sustituya la IP por la de la PC Servicios:
$ApiUrl = "http://192.168.1.50:8080/api_print_21.php"
$LocalQueueDir = ""

# Opcional: pisa print_migrate.cfg. Mejor dejar el cfg.
# $AcceptPrinters = "GK420t_chica"
