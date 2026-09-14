SERVICIO UNICO DE IMPRESION (print_service_21)
==============================================

Arquitectura
------------
  PC SERVICIOS (web)
    - start_solo_web.bat  → http://IP:8080/
    - Genera ZPL (formatos 1,5,9,10,14,21,GTIN…)
    - Imagenes: printserver\{codigo}.png|.bmp  (solo hace falta AQUI)
    - Encola en MySQL isabel_zpl_queue

  PC IMPRESORAS (Zebras USB)
    - Un solo worker: print_service_21
    - NO guarda formatos ni imagenes
    - Solo descarga ZPL de la API e imprime RAW

Factible un solo servicio: SI.
  print_service_1 y print_service_10 ya no se usan (eliminados).

Setup PC impresoras
-------------------
  1) git pull (o copiar carpeta BARCODE)
  2) Renombrar Zebras: GK420t_chica | GK420t_grande | GK420t_3x1.25 | GK420t_3x2
     (tools\setup_zebra_printers.bat)
  3) print_service_21\configurar_api_servicios.bat  → IP de Servicios
  4) print_service_21\arrancar_worker.bat
     (opcional Admin: install_tarea.bat para autoinicio)

Setup PC Servicios (imagenes 10/14)
-----------------------------------
  Las imagenes deben estar en:
    C:\Servicios\barcode\printserver\026218.png  (ejemplo)
  Si estan en otra PC/USB:
    tools\copiar_imagenes_printserver.bat

print_migrate.cfg
-----------------
  Lista las impresoras de la cola nueva (las 4 GK420t_*).
  Formato 13 sigue por IP (print_ip_13.cfg).
