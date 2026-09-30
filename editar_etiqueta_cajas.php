<?php
/**
 * Editor del formato compartido de etiqueta de CAJA (Imprimir Caja → GK420t_2x3).
 * Un solo layout para TODOS los productos. No toca layouts por producto (1/5/9/10/14).
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'conections.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php';

$link = conec_mysql();
$layout = label_layout_ensure_shared('cajas');
$sampleCode = '';
if ($link) {
	$res = mysqli_query($link, "SELECT codigo FROM items WHERE codigo2 IS NOT NULL AND codigo2 != '' ORDER BY codigo LIMIT 1");
	$row = $res ? mysqli_fetch_assoc($res) : null;
	if ($res) {
		mysqli_free_result($res);
	}
	if ($row && isset($row['codigo'])) {
		$sampleCode = label_layout_pad_codigo($row['codigo']);
	}
}

function h($s)
{
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$fields = isset($layout['fields']) && is_array($layout['fields']) ? $layout['fields'] : array();
$lab = isset($layout['label']) && is_array($layout['label']) ? $layout['label'] : array();

function fval($fields, $name, $key, $default = '')
{
	if (!isset($fields[$name]) || !is_array($fields[$name]) || !isset($fields[$name][$key])) {
		return $default;
	}
	return $fields[$name][$key];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<title>Editor etiqueta de caja</title>
	<link rel="stylesheet" href="css/ui_modern.css?v=m3" />
	<style>
		.ec-wrap { max-width: 1100px; margin: 0 auto; padding: 16px 18px 40px; }
		.ec-header { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
		.ec-header h1 { margin: 0 0 6px; font-size: 1.45rem; }
		.ec-sub { margin: 0; color: #4b5563; max-width: 42rem; line-height: 1.45; }
		.ec-badge { display: inline-block; background: #0f2744; color: #fff; font-size: 0.75rem; padding: 3px 8px; border-radius: 999px; margin-bottom: 8px; }
		.ec-warn { background: #fff8e6; border: 1px solid #f0d78c; border-radius: 10px; padding: 10px 12px; margin-bottom: 16px; color: #5c4813; }
		.ec-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 16px; }
		@media (max-width: 900px) { .ec-grid { grid-template-columns: 1fr; } }
		.ec-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
		.ec-card h2 { margin: 0 0 12px; font-size: 1.05rem; }
		.ec-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin-bottom: 12px; }
		.ec-row label { display: flex; flex-direction: column; gap: 4px; font-size: 0.82rem; color: #374151; }
		.ec-row input { padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; }
		.ec-section { margin-top: 8px; padding-top: 10px; border-top: 1px solid #f3f4f6; }
		.ec-section h3 { margin: 0 0 8px; font-size: 0.92rem; color: #111827; }
		.ec-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
		.ec-btn { appearance: none; border: 1px solid #d1d5db; background: #fff; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.92rem; }
		.ec-btn-primary { background: #0f2744; color: #fff; border-color: #0f2744; }
		.ec-btn-ghost { text-decoration: none; color: #0f2744; display: inline-flex; align-items: center; }
		.ec-status { min-height: 1.3em; color: #374151; font-size: 0.9rem; margin: 0 0 10px; }
		.ec-preview { width: 100%; max-width: 520px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; display: none; }
		.ec-zpl { max-height: 180px; overflow: auto; font-size: 11px; background: #0b1220; color: #d1e7ff; padding: 10px; border-radius: 8px; display: none; }
		.ec-hint { font-size: 0.8rem; color: #6b7280; margin: 0; }
	</style>
</head>
<body>
	<div class="ec-wrap">
		<header class="ec-header">
			<div>
				<span class="ec-badge">compartido · todos los productos</span>
				<h1>Editor — etiqueta de caja</h1>
				<p class="ec-sub">Ajusta posición y tamaño del nombre, código de barras y datos. Se guarda en un solo JSON y aplica a <strong>Imprimir Caja</strong> de cualquier producto. No cambia las etiquetas normales (1, 5, 9, 10, 14…).</p>
			</div>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<a class="ec-btn ec-btn-ghost" href="ajustes_formatos.php?tipo=cajas">Formatos</a>
				<a class="ec-btn ec-btn-ghost" href="index.php">← Inicio</a>
			</div>
		</header>

		<div class="ec-warn">
			Archivo: <code>label_layouts/shared/tipo_cajas.json</code>
			· Impresora: <code>GK420t_2x3</code>
			· Unidades: dots @ 203 dpi
			<?php if ($sampleCode !== '') { ?>
			· Muestra: <a href="search_results.php?code=<?php echo h(urlencode($sampleCode)); ?>&amp;bttn_actualizar=FM"><?php echo h($sampleCode); ?></a>
			<?php } ?>
		</div>

		<div class="ec-grid">
			<div class="ec-card">
				<h2>Campos (dots)</h2>

				<div class="ec-section">
					<h3>Tamaño de media</h3>
					<div class="ec-row">
						<label>Ancho dots <input type="number" id="w_dots" value="<?php echo h(isset($lab['width_dots']) ? $lab['width_dots'] : 406); ?>" /></label>
						<label>Alto dots <input type="number" id="h_dots" value="<?php echo h(isset($lab['height_dots']) ? $lab['height_dots'] : 609); ?>" /></label>
						<label>Ancho in <input type="number" step="0.01" id="w_in" value="<?php echo h(isset($lab['width_in']) ? $lab['width_in'] : 2); ?>" /></label>
						<label>Alto in <input type="number" step="0.01" id="h_in" value="<?php echo h(isset($lab['height_in']) ? $lab['height_in'] : 3); ?>" /></label>
						<label>Correr izq. (shift_left) <input type="number" id="shift_left" value="<?php echo h(fval($fields, 'origen', 'shift_left', 24)); ?>" title="Dots hacia la izquierda (^LS)" /></label>
					</div>
				</div>

				<div class="ec-section">
					<h3>Nombre del producto</h3>
					<div class="ec-row">
						<label>X <input type="number" id="nombre_x" value="<?php echo h(fval($fields, 'nombre', 'x', 0)); ?>" /></label>
						<label>Y <input type="number" id="nombre_y" value="<?php echo h(fval($fields, 'nombre', 'y', 6)); ?>" /></label>
						<label>Alto letra (font) <input type="number" id="nombre_font" value="<?php echo h(fval($fields, 'nombre', 'font', 68)); ?>" /></label>
						<label>Ancho letra (w) <input type="number" id="nombre_w" value="<?php echo h(fval($fields, 'nombre', 'w', 52)); ?>" /></label>
						<label>Espacio entre renglones <input type="number" id="nombre_line_gap" value="<?php echo h(fval($fields, 'nombre', 'line_gap', 10)); ?>" /></label>
						<label>Ancho p/ salto (wrap_w) <input type="number" id="nombre_wrap_w" value="<?php echo h(fval($fields, 'nombre', 'wrap_w', 28)); ?>" title="Solo decide en qué palabra saltar; no achica la letra" /></label>
					</div>
					<p class="ec-hint">font/w = tamaño visual (como Nachos). wrap_w más chico ⇒ salta antes a 2.ª línea, sin reducir la letra.</p>
				</div>

				<div class="ec-section">
					<h3>Línea separadora</h3>
					<div class="ec-row">
						<label>Y <input type="number" id="sep_y" value="<?php echo h(fval($fields, 'separador', 'y', 210)); ?>" /></label>
					</div>
				</div>

				<div class="ec-section">
					<h3>Código de barras</h3>
					<div class="ec-row">
						<label>X <input type="number" id="bar_x" value="<?php echo h(fval($fields, 'barcode', 'x', 0)); ?>" /></label>
						<label>Y <input type="number" id="bar_y" value="<?php echo h(fval($fields, 'barcode', 'y', 225)); ?>" /></label>
						<label>Alto (h) <input type="number" id="bar_h" value="<?php echo h(fval($fields, 'barcode', 'h', 70)); ?>" /></label>
					</div>
				</div>

				<div class="ec-section">
					<h3>Datos</h3>
					<div class="ec-row">
						<label>Unidades X <input type="number" id="uni_x" value="<?php echo h(fval($fields, 'unidades', 'x', 68)); ?>" /></label>
						<label>Unidades Y <input type="number" id="uni_y" value="<?php echo h(fval($fields, 'unidades', 'y', 235)); ?>" /></label>
						<label>Unidades font <input type="number" id="uni_font" value="<?php echo h(fval($fields, 'unidades', 'font', 34)); ?>" /></label>
						<label>Lote Y <input type="number" id="lote_y" value="<?php echo h(fval($fields, 'lote', 'y', 290)); ?>" /></label>
						<label>Lote font <input type="number" id="lote_font" value="<?php echo h(fval($fields, 'lote', 'font', 34)); ?>" /></label>
						<label>Elab. Y <input type="number" id="elab_y" value="<?php echo h(fval($fields, 'elaboracion', 'y', 345)); ?>" /></label>
						<label>Elab. font <input type="number" id="elab_font" value="<?php echo h(fval($fields, 'elaboracion', 'font', 32)); ?>" /></label>
						<label>Exp Y <input type="number" id="exp_y" value="<?php echo h(fval($fields, 'fecha', 'y', 400)); ?>" /></label>
						<label>Exp font <input type="number" id="exp_font" value="<?php echo h(fval($fields, 'fecha', 'font', 34)); ?>" /></label>
						<label>GTIN Y <input type="number" id="gtin_y" value="<?php echo h(fval($fields, 'gtin', 'y', 470)); ?>" /></label>
					</div>
				</div>
			</div>

			<div class="ec-card">
				<h2>Vista previa y guardar</h2>
				<div class="ec-actions">
					<button type="button" class="ec-btn" id="btnPreview">Ver etiqueta</button>
					<button type="button" class="ec-btn ec-btn-primary" id="btnSave">Guardar para todos</button>
				</div>
				<p class="ec-status" id="status">Ajuste valores y pulse Guardar. Aplica a todas las cajas.</p>
				<img id="previewImg" class="ec-preview" alt="Vista previa caja" />
				<pre id="zplBox" class="ec-zpl"></pre>
			</div>
		</div>
	</div>

	<script>
	var EC = {
		sample: <?php echo json_encode($sampleCode); ?>,
		layout: <?php echo json_encode($layout); ?>
	};
	if (!EC.layout || typeof EC.layout !== 'object') EC.layout = {};
	EC.layout.etiqueta = 'cajas';
	EC.layout.shared = true;
	EC.layout.unit = 'dots';
	if (!EC.layout.fields) EC.layout.fields = {};
	if (!EC.layout.label) EC.layout.label = {};

	function num(id) {
		var el = document.getElementById(id);
		if (!el || el.value === '') return null;
		var n = parseFloat(el.value);
		return isNaN(n) ? null : n;
	}
	function setStatus(msg) {
		var el = document.getElementById('status');
		if (el) el.textContent = msg || '';
	}
	function collect() {
		var L = EC.layout;
		L.etiqueta = 'cajas';
		L.shared = true;
		L.unit = 'dots';
		L.version = (L.version || 12);
		L.note = 'Formato compartido para TODOS los productos (Imprimir Caja). No afecta etiquetas por producto.';
		L.label = L.label || {};
		var wd = num('w_dots'), hd = num('h_dots'), wi = num('w_in'), hi = num('h_in');
		if (wd !== null) L.label.width_dots = Math.round(wd);
		if (hd !== null) L.label.height_dots = Math.round(hd);
		if (wi !== null) L.label.width_in = wi;
		if (hi !== null) L.label.height_in = hi;

		function field(name, map) {
			if (!L.fields[name]) L.fields[name] = {};
			Object.keys(map).forEach(function (k) {
				var v = num(map[k]);
				if (v !== null) L.fields[name][k] = Math.round(v);
			});
		}
		field('origen', { shift_left: 'shift_left' });
		field('nombre', { x: 'nombre_x', y: 'nombre_y', font: 'nombre_font', w: 'nombre_w', line_gap: 'nombre_line_gap', wrap_w: 'nombre_wrap_w' });
		field('separador', { y: 'sep_y' });
		field('barcode', { x: 'bar_x', y: 'bar_y', h: 'bar_h' });
		field('unidades', { x: 'uni_x', y: 'uni_y', font: 'uni_font' });
		field('lote', { x: 'uni_x', y: 'lote_y', font: 'lote_font' });
		field('elaboracion', { x: 'uni_x', y: 'elab_y', font: 'elab_font' });
		field('fecha', { x: 'uni_x', y: 'exp_y', font: 'exp_font' });
		field('gtin', { x: 'uni_x', y: 'gtin_y' });
		return L;
	}

	function preview() {
		collect();
		if (!EC.sample) {
			setStatus('No hay producto de muestra con GTIN para previsualizar.');
			return;
		}
		setStatus('Generando vista previa…');
		var base = 'codigo=' + encodeURIComponent(EC.sample)
			+ '&tipo=cajas&unidades=100'
			+ '&caducidad=2&elab_day=' + encodeURIComponent(new Date().toISOString().slice(0, 10))
			+ '&include_zpl=1&_=' + Date.now();
		var img = document.getElementById('previewImg');
		var zplBox = document.getElementById('zplBox');
		img.style.display = 'none';
		var fd = new FormData();
		fd.append('layout_json', JSON.stringify(EC.layout));
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'preview_zpl.php?fmt=json&' + base, true);
		xhr.timeout = 60000;
		xhr.onload = function () {
			var r = null;
			try { r = JSON.parse(xhr.responseText); } catch (e) {}
			if (!r || !r.ok) {
				setStatus((r && r.error) ? r.error : 'Error de vista previa');
				return;
			}
			if (r.zpl) {
				zplBox.style.display = 'block';
				zplBox.textContent = r.zpl;
			}
			var xhr2 = new XMLHttpRequest();
			xhr2.open('POST', 'preview_zpl.php?fmt=png&' + base, true);
			xhr2.responseType = 'blob';
			xhr2.onload = function () {
				if (xhr2.status >= 200 && xhr2.status < 300) {
					img.onload = function () {
						img.style.display = 'block';
						setStatus('Vista previa OK (aún no guardado).');
					};
					img.src = URL.createObjectURL(xhr2.response);
				} else {
					setStatus('ZPL OK; Labelary no devolvió imagen. Puede guardar igual.');
				}
			};
			xhr2.onerror = function () { setStatus('ZPL OK; sin imagen de red.'); };
			var fd2 = new FormData();
			fd2.append('layout_json', JSON.stringify(EC.layout));
			xhr2.send(fd2);
		};
		xhr.onerror = function () { setStatus('Error de red en vista previa'); };
		xhr.send(fd);
	}

	function save() {
		collect();
		setStatus('Guardando formato compartido de caja…');
		var xhr = new XMLHttpRequest();
		xhr.open('POST', 'api_label_layout.php?action=save_shared', true);
		xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
		xhr.onload = function () {
			var r = null;
			try { r = JSON.parse(xhr.responseText); } catch (e) {}
			if (!r || !r.ok) {
				setStatus((r && r.error) ? r.error : 'No se pudo guardar');
				return;
			}
			setStatus('Guardado. Todas las impresiones «Imprimir Caja» usarán este layout. Las etiquetas normales no cambian.');
		};
		xhr.onerror = function () { setStatus('Error de red al guardar'); };
		xhr.send(JSON.stringify(EC.layout));
	}

	document.getElementById('btnPreview').onclick = preview;
	document.getElementById('btnSave').onclick = save;
	</script>
</body>
</html>
