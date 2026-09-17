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
  2) Las Zebras ya deben llamarse exactamente:
     GK420t_chica | GK420t_grande | GK420t_3x1.25 | GK420t_3x2 | GK420t_2x3
     (sin renombrar automatico en start.bat)
  3) Si web e impresoras estan en OTRA PC: print_service_21\configurar_api_servicios.bat
     Si todo esta en la MISMA PC: no hace falta (usa 127.0.0.1)
  4) start.bat  (o print_service_21\arrancar_worker.bat)
     (opcional Admin: install_tarea.bat para autoinicio)

  El worker solo usa nombres exactos. Si falta una impresora, libera el job
  (no redirige a otra cola ni a PrinterDefault).

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
