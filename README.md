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
8. Se abre `http://127.0.0.1:8080/` en esa PC
9. Desde otra PC de la red: `http://IP-DE-ESA-MAQUINA:8080/` (la consola de `start.bat` muestra la IP). Si no entra, una vez como Administrador: `tools\abrir_red_8080.bat`

PHP **no** está en GitHub (`tools\php\` está en `.gitignore`). Por eso el paso 6 es obligatorio en cada PC nueva.

### Actualizar esa PC

`start.bat` hace **Pull automatico** al abrir (si hay internet y Git/GitHub Desktop).

La primera vez hay que traer ese cambio a mano: en GitHub Desktop, **Fetch origin** y **Pull origin**. Desde entonces, cada vez que abran `start.bat` se actualiza sola.

Si el Pull automatico falla (sin red o cambios locales), el sistema arranca igual con lo que ya tiene.

### Red

La PC debe alcanzar:

- MySQL de producción (`conections.php`: `64.210.64.60:3333`)
- Odoo
- Impresora #13: IP en `print_ip_13.cfg`

Si la sucursal usa otra Zebra, edite `print_ip_13.cfg` en esa PC. Un Pull no deberia pisarlo si solo cambia la IP y no hay conflicto; si Git avisa conflicto, dejen la IP local.

### Fotos de etiquetas 10 / 14

Los `.bmp` de `printserver\` no van en GitHub (pesan mucho). Si esa PC imprime esos formatos, copie los BMP desde la PC original a `printserver\`.

## Arranque rápido (esta PC)

1. Doble clic en `start.bat`
2. Se abre el navegador en `http://127.0.0.1:8080/`
3. Otras PCs: `http://IP:8080/` (si el firewall bloquea, `tools\abrir_red_8080.bat` como Administrador)
4. Para detener: Ctrl+C en la consola, o `stop.bat`

MySQL sigue siendo el remoto de `conections.php` (no hace falta MySQL local).

## Qué incluye

- Generación ZPL formatos 1, 5, 9, 10, 13, 14, 21, GTIN
- Cola local / `print_service_21`
- Migración híbrida por impresora (`print_migrate.cfg`)
- Editor JSON de layouts (`label_layouts/`)

## Migración híbrida (impresora por impresora)

La PC nueva usa `print_service_21` y MySQL `isabel_zpl_queue`. La PC vieja puede dejar el Label_Printserver / `isabel_label_print` hasta mover cada impresora.

Control: archivo `print_migrate.cfg` (una impresora por linea).

- **Listada** → el job va a `isabel_zpl_queue` y lo imprime el servicio nuevo. No se inserta en `isabel_label_print`.
- **No listada** → no escribe la cola ZPL; se inserta `isabel_label_print` para el legacy.

Inicio: `GK420t_chica` (formatos **1, 9, 21 y GTIN**). Cuando mueva `GK420t_grande` u otra, agregue el nombre en el cfg, haga pull, y vuelva a ejecutar `print_service_21\install_tarea.bat` como Administrador en la PC nueva.

En la PC nueva: `start.bat` abierto (API `http://127.0.0.1:8080/api_print_21.php`) y la impresora chica instalada con el mismo nombre Windows (`GK420t_chica`).

### Actualizar la otra PC (despues de push a GitHub)

```bat
cd C:\Servicios\barcode
git pull --ff-only origin master
```

O GitHub Desktop: **Fetch origin** → **Pull origin**. O solo `start.bat` (hace pull automatico).

Luego, si cambio el servicio de impresion: clic derecho `print_service_21\install_tarea.bat` → Ejecutar como administrador.

## Notas

- IP impresora formato #13: `print_ip_13.cfg`
- Si falta `tools\php\php.exe`, ejecute `tools\install_php.bat`.
