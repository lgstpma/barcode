# Servicio UNICO de impresion (PC de las Zebras).
#
# NO genera formatos. Solo:
#   1) Pide jobs a la API del BARCODE (PC Servicios)
#   2) Envia el ZPL RAW a la impresora Windows local
#
# Formatos 1,5,9,10,13,14,21,GTIN se arman en PHP (exportarimg / etc.)
# Imagenes BMP/PNG de formatos 10/14: carpeta printserver/ en PC Servicios.

# CASO A — API en la misma PC:
# $ApiUrl = "http://127.0.0.1:8080/api_print_21.php"

# CASO B — API en Servicios (recomendado):
$ApiUrl = "http://192.168.1.50:8080/api_print_21.php"
$LocalQueueDir = ""

# Impresoras: las define print_migrate.cfg (no hace falta listar aqui).
# $AcceptPrinters = "GK420t_chica,GK420t_grande,GK420t_3x1.25,GK420t_3x2"
