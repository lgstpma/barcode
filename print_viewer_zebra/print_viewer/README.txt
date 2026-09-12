PRINT VIEWER ZEBRA / WINDOWS 7 (sin instalar Python del sistema)

Por que en la PC vieja funcionaba:
  Ahi ya habia Python instalado (con Flask/Pillow).
  En la PC nueva a menudo no se puede instalar Python facilmente.

Ahora el visor NO necesita Flask ni Pillow ni instalador de Python:
  usa solo la libreria estandar + un Python 3.8 portable.

Pasos en la PC de impresoras (Win7):

1) ejecutar UNA VEZ:
     instalar_portable.bat
   (descarga python-3.8.10-embed a portable\python\)

   Si la descarga falla (TLS en Win7):
   - En otra PC baje el zip amd64 o win32 de python.org 3.8.10 embed
   - Copielo como: portable\python38.zip
   - Vuelva a ejecutar instalar_portable.bat

2) En la Zebra:
   Propiedades → Avanzadas → Conservar documentos impresos

3) Ejecutar como Administrador:
     ejecutar.bat
   Dejar la ventana abierta.

4) Abrir: http://127.0.0.1:8088/

Notas:
- Monitorea C:\Windows\System32\spool\PRINTERS
- Guarda SPL/SHD/PNG en archivo_impresiones\
- BARCODE (etiquetas) usa puerto 8080; este visor usa 8088 (opcional)

Migracion split:
- Servicios: start_solo_web.bat
- PC impresoras: print_service_21\configurar_api_servicios.bat
  + este Print Viewer si quieren ver el spool
