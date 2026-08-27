# BARCODE — etiquetas ZPL (Sofy)

Proyecto migrado desde `z:\barcode4.0`. **No requiere XAMPP** para arrancar.

## Arranque rápido

1. Doble clic en `start.bat`
2. Se abre el navegador en `http://127.0.0.1:8080/`
3. Para detener: Ctrl+C en la consola, o `stop.bat`

PHP portable va en `tools\php\` (ya incluido). MySQL sigue siendo el remoto configurado en `conections.php` (no hace falta MySQL local).

## Qué incluye

- Generación ZPL formatos 1, 5, 9, 10, 13, 14, 21, GTIN
- Cola `isabel_zpl_queue` + `print_service_21`
- Editor JSON de layouts (`label_layouts/`)
- Diseños BMP en `printserver/`

## Notas

- El original en `z:\barcode4.0` **no se borró** (copia independiente).
- IP impresora formato #13: `print_ip_13.cfg`
- Historial de trabajo Cursor: `docs/historial/`

## Reinstalar PHP portable

Si falta `tools\php\php.exe`, ejecute `tools\install_php.bat`.
