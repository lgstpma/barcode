# BARCODE — etiquetas ZPL (Sofy)

Proyecto migrado desde `z:\barcode4.0`. **No requiere XAMPP** para arrancar.

Repo: https://github.com/lgstpma/barcode

## Instalar en otra computadora (GitHub)

Así se instala y se mantiene actualizado **por GitHub**, no con USB.

### Primera vez

1. Instale [GitHub Desktop](https://desktop.github.com/) e inicie sesión (cuenta **lgstpma**).
2. **File → Clone repository → GitHub.com**
3. Elija **lgstpma/barcode**
4. Carpeta local, por ejemplo `C:\BARCODE` o `D:\GITHUB\BARCODE`
5. Clone
6. En esa carpeta, doble clic en `tools\install_php.bat` (una sola vez; baja PHP portable; necesita internet).
7. Doble clic en `start.bat`
8. Se abre `http://127.0.0.1:8080/`

PHP **no** está en GitHub (`tools\php\` está en `.gitignore`). Por eso el paso 6 es obligatorio en cada PC nueva.

### Actualizar esa PC (cuando haya cambios)

En GitHub Desktop, con el repo abierto:

1. **Fetch origin**
2. **Pull origin**

Luego vuelva a abrir `start.bat` si el servidor estaba corriendo.

### Red

La PC debe alcanzar:

- MySQL de producción (`conections.php`: `64.210.64.60:3333`)
- Odoo
- Impresora #13: IP en `print_ip_13.cfg`

Si la sucursal usa otra Zebra, edite `print_ip_13.cfg` (ese archivo sí se puede cambiar local y no hace falta subirlo).

### Fotos de etiquetas 10 / 14

Los `.bmp` de `printserver\` no van en GitHub (pesan mucho). Si esa PC imprime esos formatos, copie los BMP desde la PC original a `printserver\`.

## Arranque rápido (esta PC)

1. Doble clic en `start.bat`
2. Se abre el navegador en `http://127.0.0.1:8080/`
3. Para detener: Ctrl+C en la consola, o `stop.bat`

MySQL sigue siendo el remoto de `conections.php` (no hace falta MySQL local).

## Qué incluye

- Generación ZPL formatos 1, 5, 9, 10, 13, 14, 21, GTIN
- Cola local / `print_service_21`
- Editor JSON de layouts (`label_layouts/`)

## Notas

- IP impresora formato #13: `print_ip_13.cfg`
- Si falta `tools\php\php.exe`, ejecute `tools\install_php.bat`.
