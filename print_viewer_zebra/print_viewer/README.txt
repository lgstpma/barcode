PRINT VIEWER ZEBRA / WINDOWS 7

1) Instalar Python 3.8 de 32-bit o 64-bit en Windows 7.
2) Copiar esta carpeta, por ejemplo a C:\PrintViewer\
3) Ejecutar instalar.bat una sola vez.
4) En la impresora Zebra: Propiedades de impresora > Opciones avanzadas > marcar "Conservar documentos impresos".
5) Ejecutar ejecutar.bat como Administrador.
6) Abrir en el navegador: http://localhost:8088

Notas:
- Monitorea C:\Windows\System32\spool\PRINTERS.
- Copia cada SPL/SHD a archivo_impresiones\.
- Convierte ZPL con ^GFA/:Z64: a PNG para visualizarlo.
- Para otro equipo en red, cambiar SPOOL_DIR en ejecutar.bat, por ejemplo:
  set SPOOL_DIR=\\PC-LABELS\PRINTERS

Integracion con BARCODE:
- El reporte de impresion (exportarimg) muestra la cola isabel_zpl_queue
  y un enlace a http://127.0.0.1:8088/ (este viewer).
- Use ambos: cola MySQL = "en camino"; este viewer = "lo que Windows spooLeo".

Si no aparecen impresiones:
- Ejecutar como Administrador.
- Confirmar que existan archivos .SPL en la carpeta PRINTERS.
- Confirmar que la impresora tenga activado "Conservar documentos impresos".
