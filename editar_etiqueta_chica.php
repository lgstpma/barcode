<?php
/**
 * Editor de la plantilla ZPL chica (#1 con fecha, #9 sin fecha, #15).
 * Guarda label_layouts/shared/tipo_chica.json — afecta impresión real.
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'conections.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'zpl_chica_plantilla.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php';

$link = conec_mysql();
$layout = zpl_chica_load_layout();
$previewTipo = isset($_GET['tipo']) && in_array((string)$_GET['tipo'], array('1', '9'), true)
	? (string)$_GET['tipo']
	: '9';

$sampleCode = '';
if ($link) {
	$e = mysqli_real_escape_string($link, $previewTipo);
	$res = mysqli_query($link, "SELECT codigo FROM items WHERE etiqueta='$e' AND navidad='FM' ORDER BY codigo LIMIT 1");
	$row = $res ? mysqli_fetch_assoc($res) : null;
	if ($res) {
		mysqli_free_result($res);
	}
	if (!$row) {
		$res = mysqli_query($link, "SELECT codigo FROM items WHERE etiqueta='$e' ORDER BY codigo LIMIT 1");
		$row = $res ? mysqli_fetch_assoc($res) : null;
		if ($res) {
			mysqli_free_result($res);
		}
	}
	if ($row && isset($row['codigo'])) {
		$sampleCode = label_layout_pad_codigo($row['codigo']);
	}
}

function h($s)
{
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function fval($fields, $name, $key, $default = 0)
{
	if (!isset($fields[$name]) || !is_array($fields[$name]) || !isset($fields[$name][$key])) {
		return $default;
	}
	return $fields[$name][$key];
}

$fields = isset($layout['fields']) && is_array($layout['fields']) ? $layout['fields'] : array();
$chicaOff = zpl_chica_load_offsets();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<title>Editor etiqueta chica #1 / #9</title>
	<link rel="stylesheet" href="css/ui_modern.css?v=m3" />
	<style>
		.ec-wrap { max-width: 1100px; margin: 0 auto; padding: 16px 18px 40px; }
		.ec-header { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
		.ec-header h1 { margin: 0 0 6px; font-size: 1.45rem; }
		.ec-sub { margin: 0; color: #4b5563; max-width: 44rem; line-height: 1.45; }
		.ec-badge { display: inline-block; background: #0f2744; color: #fff; font-size: 0.75rem; padding: 3px 8px; border-radius: 999px; margin-bottom: 8px; }
		.ec-warn { background: #fff8e6; border: 1px solid #f0d78c; border-radius: 10px; padding: 10px 12px; margin-bottom: 16px; color: #5c4813; }
		.ec-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 16px; }
		@media (max-width: 900px) { .ec-grid { grid-template-columns: 1fr; } }
		.ec-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
		.ec-card h2 { margin: 0 0 12px; font-size: 1.05rem; }
		.ec-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin-bottom: 12px; }
		.ec-row label { display: flex; flex-direction: column; gap: 4px; font-size: 0.82rem; color: #374151; }
		.ec-row input, .ec-row select { padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; }
		.ec-section { margin-top: 8px; padding-top: 10px; border-top: 1px solid #f3f4f6; }
		.ec-section h3 { margin: 0 0 8px; font-size: 0.92rem; color: #111827; }
		.ec-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
		.ec-btn { appearance: none; border: 1px solid #d1d5db; background: #fff; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.92rem; text-decoration: none; color: inherit; display: inline-flex; align-items: center; }
		.ec-btn-primary { background: #0f2744; color: #fff; border-color: #0f2744; }
		.ec-status { min-height: 1.3em; color: #374151; font-size: 0.9rem; margin: 0 0 10px; }
		.ec-preview { width: 100%; max-width: 280px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; display: none; }
		.ec-zpl { max-height: 220px; overflow: auto; font-size: 11px; background: #0b1220; color: #d1e7ff; padding: 10px; border-radius: 8px; display: none; white-space: pre-wrap; }
		.ec-hint { font-size: 0.8rem; color: #6b7280; margin: 0; }
		.ec-off { font-size: 0.85rem; color: #4b5563; margin: 0 0 10px; }
	</style>
</head>
<body>
	<div class="ec-wrap">
		<header class="ec-header">
			<div>
				<span class="ec-badge">plantilla compartida · #1 · #9 · #15</span>
				<h1>Editor — etiqueta chica SoftShop</h1>
				<p class="ec-sub">
					Aquí se edita el <strong>ZPL real</strong> (posición del nombre, wrap, precio y barras).
					#1 y #15 agregan lote/exp/reg; #9 es la misma plantilla sin fechas.
					El offset ^LT/^LS de «Formatos» solo corre el job entero; no cambia el diseño.
				</p>
			</div>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<a class="ec-btn" href="ajustes_formatos.php?tipo=<?php echo h(urlencode($previewTipo)); ?>">Formatos</a>
				<a class="ec-btn" href="index.php">← Inicio</a>
			</div>
		</header>

		<div class="ec-warn">
			Archivo: <code>label_layouts/shared/tipo_chica.json</code>
			· Impresora: <code>GK420t_chica</code>
			· Offset actual: LT=<?php echo (int)$chicaOff['lt']; ?>, LS=<?php echo (int)$chicaOff['ls']; ?>
			<?php if ($sampleCode !== '') { ?>
			· Muestra: <a href="search_results.php?code=<?php echo h(urlencode($sampleCode)); ?>&amp;bttn_actualizar=FM"><?php echo h($sampleCode); ?></a>
			<?php } ?>
		</div>

		<div class="ec-grid">
			<div class="ec-card">
				<h2>Geometría (dots @203dpi)</h2>

				<div class="ec-section" style="border-top:0;padding-top:0;margin-top:0;">
					<h3>Vista previa como</h3>
					<div class="ec-row">
						<label>Tipo
							<select id="preview_tipo">
								<option value="9"<?php echo $previewTipo === '9' ? ' selected' : ''; ?>>#9 sin fecha</option>
								<option value="1"<?php echo $previewTipo === '1' ? ' selected' : ''; ?>>#1 con lote/exp</option>
							</select>
						</label>
					</div>
				</div>

				<div class="ec-section">
					<h3>Texto (nombre + precio)</h3>
					<div class="ec-row">
						<label>Alcance (chars/renglón) <input type="number" id="alcance" value="<?php echo h(fval($fields, 'texto', 'alcance', 18)); ?>" min="8" max="40" /></label>
						<label>Fuente ^CFA <input type="number" id="font" value="<?php echo h(fval($fields, 'texto', 'font', 14)); ?>" min="8" max="28" /></label>
						<label>X texto <input type="number" id="tx" value="<?php echo h(fval($fields, 'texto', 'x', 20)); ?>" /></label>
						<label>Y primer renglón <input type="number" id="y0" value="<?php echo h(fval($fields, 'texto', 'y0', 0)); ?>" /></label>
						<label>Paso entre renglones <input type="number" id="step" value="<?php echo h(fval($fields, 'texto', 'step', 17)); ?>" /></label>
						<label>Hueco antes de precio <input type="number" id="precio_gap" value="<?php echo h(fval($fields, 'texto', 'precio_gap', 2)); ?>" /></label>
					</div>
					<p class="ec-hint">Alcance ~18 con fuente 14 evita que el nombre se salga. Subir step si se montan las líneas.</p>
				</div>

				<div class="ec-section">
					<h3>Código de barras</h3>
					<div class="ec-row">
						<label>X barras <input type="number" id="bar_x" value="<?php echo h(fval($fields, 'barcode', 'x', 160)); ?>" /></label>
						<label>Alto barras <input type="number" id="bar_h" value="<?php echo h(fval($fields, 'barcode', 'h', 36)); ?>" /></label>
						<label>Ancho módulo ^BY <input type="number" id="bar_by" value="<?php echo h(fval($fields, 'barcode', 'by', 1)); ?>" min="1" max="3" /></label>
						<label>Y mínimo barras <input type="number" id="bar_ymin" value="<?php echo h(fval($fields, 'barcode', 'y_min', 42)); ?>" /></label>
						<label>Gap c/ fecha (#1) <input type="number" id="gap_fecha" value="<?php echo h(fval($fields, 'barcode', 'gap_con_fecha', 6)); ?>" /></label>
						<label>Gap s/ fecha (#9) <input type="number" id="gap_sin" value="<?php echo h(fval($fields, 'barcode', 'gap_sin_fecha', 16)); ?>" /></label>
					</div>
				</div>
			</div>

			<div class="ec-card">
				<h2>Vista previa y ZPL</h2>
				<div class="ec-actions">
					<button type="button" class="ec-btn" id="btnPreview">Ver etiqueta</button>
					<button type="button" class="ec-btn ec-btn-primary" id="btnSave">Guardar plantilla</button>
				</div>
				<p class="ec-status" id="status">Ajuste valores, vea la preview y guarde. Aplica a #1, #9 y #15.</p>
				<img id="previewImg" class="ec-preview" alt="Vista previa chica" />
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
	if (!EC.layout.fields) EC.layout.fields = {};

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
		L.etiqueta = 'chica';
		L.shared = true;
		L.unit = 'dots';
		L.version = 1;
		L.fields = L.fields || {};
		function field(name, map) {
			if (!L.fields[name]) L.fields[name] = {};
			Object.keys(map).forEach(function (k) {
				var v = num(map[k]);
				if (v !== null) L.fields[name][k] = Math.round(v);
			});
		}
		field('texto', { alcance: 'alcance', font: 'font', x: 'tx', y0: 'y0', step: 'step', precio_gap: 'precio_gap' });
		field('barcode', { x: 'bar_x', h: 'bar_h', by: 'bar_by', y_min: 'bar_ymin', gap_con_fecha: 'gap_fecha', gap_sin_fecha: 'gap_sin' });
		return L;
	}
	function previewTipo() {
		var el = document.getElementById('preview_tipo');
		return el ? el.value : '9';
	}
	function preview() {
		collect();
		var tipo = previewTipo();
		if (!EC.sample) {
			setStatus('No hay producto de muestra para #' + tipo + '.');
			return;
		}
		setStatus('Generando vista previa…');
		var qs = 'codigo=' + encodeURIComponent(EC.sample)
			+ '&tipo=' + encodeURIComponent(tipo)
			+ '&fmt=json&cache=0&v=' + Date.now();
		fetch('preview_zpl.php?' + qs)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				var zplEl = document.getElementById('zplBox');
				var img = document.getElementById('previewImg');
				if (!data || !data.ok) {
					setStatus((data && data.error) ? data.error : 'Error en preview');
					return;
				}
				if (zplEl) {
					zplEl.style.display = 'block';
					zplEl.textContent = data.zpl || '';
				}
				var pngUrl = 'preview_zpl.php?codigo=' + encodeURIComponent(EC.sample)
					+ '&tipo=' + encodeURIComponent(tipo)
					+ '&fmt=png&cache=0&v=' + Date.now();
				if (img) {
					img.style.display = 'block';
					img.onload = function () { setStatus('Preview OK · #' + tipo + ' · ' + EC.sample); };
					img.onerror = function () { setStatus('ZPL listo; Labelary no pudo renderizar PNG.'); };
					img.src = pngUrl;
				} else {
					setStatus('ZPL listo · #' + tipo);
				}
			})
			.catch(function (e) {
				setStatus('Error: ' + (e && e.message ? e.message : e));
			});
	}
	function save() {
		collect();
		setStatus('Guardando…');
		fetch('api_chica_layout.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ action: 'save', layout: EC.layout })
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data || !data.ok) {
					setStatus((data && data.error) ? data.error : 'No se pudo guardar');
					return;
				}
				setStatus('Guardado. Aplica a impresión #1 / #9 / #15.');
				preview();
			})
			.catch(function (e) {
				setStatus('Error al guardar: ' + (e && e.message ? e.message : e));
			});
	}
	document.getElementById('btnPreview').addEventListener('click', preview);
	document.getElementById('btnSave').addEventListener('click', save);
	document.getElementById('preview_tipo').addEventListener('change', function () {
		var t = previewTipo();
		history.replaceState(null, '', 'editar_etiqueta_chica.php?tipo=' + encodeURIComponent(t));
		// Recargar muestra del tipo elegido
		location.href = 'editar_etiqueta_chica.php?tipo=' + encodeURIComponent(t);
	});
	if (EC.sample) preview();
	</script>
</body>
</html>
