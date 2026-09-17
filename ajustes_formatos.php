<?php
/**
 * Ajustes de formatos de etiqueta: listar, editar en vivo (shared) y guardar
 * para todos los items con esa numeración (JSON compartido).
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'conections.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'zpl_chica_plantilla.php';

$link = conec_mysql();

$offsetMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['af_save_chica_offset'])) {
	$ok = zpl_chica_save_offsets(
		isset($_POST['lt']) ? $_POST['lt'] : 10,
		isset($_POST['ls']) ? $_POST['ls'] : 0,
		isset($_POST['lh_x']) ? $_POST['lh_x'] : 0,
		isset($_POST['lh_y']) ? $_POST['lh_y'] : 0
	);
	$offsetMsg = $ok
		? 'Offset guardado en print_chica_offset.cfg (solo jobs nuevos; VB6 no cambia).'
		: 'No se pudo escribir print_chica_offset.cfg (permisos).';
}

$chicaOff = zpl_chica_load_offsets();

$catalog = array(
	array(
		'id' => '1',
		'nombre' => 'SoftShop chica #1',
		'printer' => 'GK420t_chica',
		'size' => 'media de la impresora',
		'shared' => false,
		'note' => 'Usa tu ZPL bueno (sin ^PW/^LL). Misma plantilla que #15. Edición de coords JSON ya no aplica.',
	),
	array(
		'id' => '9',
		'nombre' => 'SoftShop chica #9 (sin fecha)',
		'printer' => 'GK420t_chica',
		'size' => 'media de la impresora',
		'shared' => false,
		'note' => 'Misma plantilla chica sin lote/exp.',
	),
	array(
		'id' => '5',
		'nombre' => 'SoftShop mediana #5',
		'printer' => 'GK420t_grande',
		'size' => '~3" × 2"',
		'shared' => true,
		'note' => 'Con ingredientes. Layout compartido.',
	),
	array(
		'id' => '15',
		'nombre' => 'Chica SoftShop #15 (lote+Reg)',
		'printer' => 'GK420t_chica',
		'size' => 'media chica',
		'shared' => false,
		'note' => 'ZPL fijo en zpl_etiqueta_15.php (aún no editable aquí).',
	),
	array(
		'id' => '10',
		'nombre' => 'Diseño imagen #10',
		'printer' => 'según lbls',
		'size' => 'variable',
		'shared' => false,
		'note' => 'Por producto (JSON en label_layouts/items/). Editar desde el ítem.',
	),
	array(
		'id' => '14',
		'nombre' => 'Diseño imagen #14',
		'printer' => 'según lbls',
		'size' => 'variable',
		'shared' => false,
		'note' => 'Por producto. Editar desde el ítem.',
	),
	array(
		'id' => '13',
		'nombre' => 'Rotada IP #13',
		'printer' => 'IP (print_ip_13.cfg)',
		'size' => 'rotado',
		'shared' => false,
		'note' => 'Envío directo por IP; layout en zpl_etiqueta_13.php.',
	),
	array(
		'id' => '21',
		'nombre' => 'Formato #21',
		'printer' => 'GK420t_chica',
		'size' => 'fijo',
		'shared' => false,
		'note' => 'Plantilla fija en exportarimg.php.',
	),
	array(
		'id' => 'gtin',
		'nombre' => 'GTI 13 caja Rey',
		'printer' => 'GK420t_3x2',
		'size' => '3x2',
		'shared' => false,
		'note' => 'Caja unidades 3×2 + QR La Cocina de Sofy (export_code13.php).',
	),
);

function af_count_items($link, $etiq)
{
	if (!$link || $etiq === 'gtin') {
		return null;
	}
	$e = mysqli_real_escape_string($link, (string)$etiq);
	$res = mysqli_query($link, "SELECT COUNT(*) AS c FROM items WHERE etiqueta='$e'");
	if (!$res) {
		return null;
	}
	$row = mysqli_fetch_assoc($res);
	mysqli_free_result($res);
	return $row ? (int)$row['c'] : 0;
}

function af_sample_codigo($link, $etiq)
{
	if (!$link || $etiq === 'gtin') {
		return '';
	}
	$e = mysqli_real_escape_string($link, (string)$etiq);
	$res = mysqli_query($link, "SELECT codigo FROM items WHERE etiqueta='$e' AND navidad='FM' ORDER BY codigo LIMIT 1");
	if (!$res || !($row = mysqli_fetch_assoc($res))) {
		if ($res) {
			mysqli_free_result($res);
		}
		$res = mysqli_query($link, "SELECT codigo FROM items WHERE etiqueta='$e' ORDER BY codigo LIMIT 1");
		$row = $res ? mysqli_fetch_assoc($res) : null;
	}
	if ($res) {
		mysqli_free_result($res);
	}
	return $row && isset($row['codigo']) ? label_layout_pad_codigo($row['codigo']) : '';
}

$tipoSel = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : '';
$selected = null;
foreach ($catalog as $f) {
	if ((string)$f['id'] === (string)$tipoSel) {
		$selected = $f;
		break;
	}
}

$sampleCode = $selected ? af_sample_codigo($link, $selected['id']) : '';
$sharedLayout = null;
if ($selected && !empty($selected['shared'])) {
	$sharedLayout = label_layout_ensure_shared((int)$selected['id']);
}

function h($s)
{
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<title>Ajustes de formatos</title>
	<link rel="stylesheet" href="css/ui_modern.css?v=m3" />
	<link rel="stylesheet" href="css/ajustes_formatos.css?v=2" />
	<script>
	(function () {
		var w = Math.min(screen.width || 9999, window.innerWidth || 9999);
		if (w <= 820) document.documentElement.className += ' is-phone';
	})();
	</script>
</head>
<body class="af-body">
	<div class="af-wrap">
		<header class="af-header">
			<div>
				<p class="af-eyebrow">Sistema de impresión</p>
				<h1>Formatos de etiqueta</h1>
				<p class="af-sub">Edite el layout compartido y guárdelo para todos los ítems con ese número de etiqueta.</p>
			</div>
			<a class="af-btn af-btn-ghost" href="index.php">← Inicio</a>
		</header>

		<section class="af-list">
			<?php foreach ($catalog as $f) {
				$cnt = af_count_items($link, $f['id']);
				$active = $selected && (string)$selected['id'] === (string)$f['id'];
				?>
				<a class="af-card<?php echo $active ? ' is-active' : ''; ?>" href="ajustes_formatos.php?tipo=<?php echo h(urlencode($f['id'])); ?>">
					<div class="af-card-top">
						<span class="af-badge">#<?php echo h($f['id']); ?></span>
						<?php if (!empty($f['shared'])) { ?><span class="af-tag">compartido</span><?php } ?>
					</div>
					<strong><?php echo h($f['nombre']); ?></strong>
					<span class="af-meta"><?php echo h($f['printer']); ?> · <?php echo h($f['size']); ?></span>
					<span class="af-meta"><?php echo $cnt === null ? '—' : ((int)$cnt . ' ítems'); ?></span>
				</a>
			<?php } ?>
		</section>

		<?php if ($selected) { ?>
		<section class="af-editor">
			<div class="af-editor-head">
				<h2>#<?php echo h($selected['id']); ?> — <?php echo h($selected['nombre']); ?></h2>
				<p><?php echo h($selected['note']); ?></p>
				<?php if ($sampleCode !== '') { ?>
				<p class="af-sample">Muestra: <a href="search_results.php?code=<?php echo h(urlencode($sampleCode)); ?>&amp;bttn_actualizar=FM"><?php echo h($sampleCode); ?></a></p>
				<?php } else { ?>
				<p class="af-sample">No hay ítems de ejemplo en MySQL para este tipo.</p>
				<?php } ?>
			</div>

			<?php if (empty($selected['shared'])) { ?>
			<div class="af-notice">
				<?php if (in_array($selected['id'], array('1', '9', '15'), true)) { ?>
					<strong>Compensar margen de la chica solo en este sistema</strong>
					(comandos <code>^LT</code>/<code>^LS</code>/<code>^LH</code> en el ZPL del job).
					<strong>No se graba nada en la impresora</strong> → VB6 SoftShop sigue igual.
					<?php if ($offsetMsg !== '') { ?>
						<p style="margin:10px 0 0;color:#0f2744;"><strong><?php echo h($offsetMsg); ?></strong></p>
					<?php } ?>
					<form method="post" action="ajustes_formatos.php?tipo=<?php echo h(urlencode($selected['id'])); ?>" class="af-label-size" style="margin-top:12px;">
						<input type="hidden" name="af_save_chica_offset" value="1" />
						<div class="af-coords">
							<label>Vertical ^LT (dots)
								<input type="number" name="lt" value="<?php echo (int)$chicaOff['lt']; ?>" step="1" min="-120" max="120" />
							</label>
							<label>Horizontal ^LS (dots)
								<input type="number" name="ls" value="<?php echo (int)$chicaOff['ls']; ?>" step="1" min="-120" max="120" />
							</label>
							<label>Origen X ^LH
								<input type="number" name="lh_x" value="<?php echo (int)$chicaOff['lh_x']; ?>" step="1" min="0" max="400" />
							</label>
							<label>Origen Y ^LH
								<input type="number" name="lh_y" value="<?php echo (int)$chicaOff['lh_y']; ?>" step="1" min="0" max="400" />
							</label>
						</div>
						<p class="af-hint" style="margin-top:8px;">
							~8 dots ≈ 1 mm. Si hay margen de más arriba, baja <code>lt</code> (prueba <code>-10</code> o <code>-20</code>).
							Si sobra a la izquierda, baja <code>ls</code> (negativo). Luego imprima una de prueba.
						</p>
						<button type="submit" class="af-btn af-btn-primary" style="margin-top:10px;">Guardar offset chica</button>
					</form>
				<?php } elseif (in_array($selected['id'], array('10', '14'), true) && $sampleCode !== '') { ?>
					Este formato no usa layout compartido editable aquí.
					Abra un producto (ej. <?php echo h($sampleCode); ?>) y use <em>Opciones avanzadas / Vista previa</em>.
				<?php } else { ?>
					Este formato no usa layout compartido editable aquí.
					La geometría está en el generador ZPL PHP correspondiente.
				<?php } ?>
			</div>
			<?php } else { ?>
			<div class="af-grid">
				<div class="af-preview-pane">
					<div class="af-toolbar">
						<button type="button" class="af-btn" id="afPreviewBtn">Ver etiqueta</button>
						<button type="button" class="af-btn af-btn-primary" id="afSaveBtn">Guardar para todos los ítems #<?php echo h($selected['id']); ?></button>
					</div>
					<p id="afStatus" class="af-status">Pulse «Ver etiqueta» para previsualizar.</p>
					<img id="afPreviewImg" alt="Vista previa" class="af-preview-img" style="display:none;" />
					<pre id="afZpl" class="af-zpl" style="display:none;"></pre>
				</div>
				<div class="af-fields-pane">
					<div class="af-label-size" id="afLabelSize">
						<h3>Tamaño de etiqueta</h3>
						<div class="af-coords">
							<label>Ancho (pulgadas)
								<input type="number" id="af_label_w_in" step="0.01" min="0.2" max="8" />
							</label>
							<label>Alto (pulgadas)
								<input type="number" id="af_label_h_in" step="0.01" min="0.2" max="8" />
							</label>
							<label>Ancho (dots @203dpi)
								<input type="number" id="af_label_w_dots" step="1" min="40" max="1600" />
							</label>
							<label>Alto (dots @203dpi)
								<input type="number" id="af_label_h_dots" step="1" min="40" max="1600" />
							</label>
						</div>
						<p class="af-hint" id="afLabelHint">Esto define ^PW / ^LL del ZPL (tamaño de media). Debe coincidir con el rollo en la impresora.</p>
					</div>
					<label>Campo
						<select id="afFieldSel"></select>
					</label>
					<div class="af-coords" id="afCoords">
						<label>X <input type="number" id="af_x" step="1" /></label>
						<label>Y <input type="number" id="af_y" step="1" /></label>
						<label>Font (pt) <input type="number" id="af_font" step="0.5" /></label>
						<label>Ancho barras (w) <input type="number" id="af_w" step="1" /></label>
						<label>Alto barras (h) <input type="number" id="af_h" step="1" /></label>
						<label>Alcance <input type="number" id="af_alcance" step="1" /></label>
						<label>Renglón <input type="number" id="af_renglon" step="1" /></label>
						<label>Ancho letra (ratio) <input type="number" id="af_ratio" step="0.05" min="0.4" max="1.4" title="1 = normal; 0.7 = condensado" /></label>
						<label>Título <input type="text" id="af_titulo" /></label>
						<div class="af-nudge">
							Paso <input type="number" id="af_step" value="20" style="width:4em;" />
							<button type="button" onclick="afNudge(-1,0)">←</button>
							<button type="button" onclick="afNudge(1,0)">→</button>
							<button type="button" onclick="afNudge(0,-1)">↑</button>
							<button type="button" onclick="afNudge(0,1)">↓</button>
							<button type="button" class="af-btn" onclick="afApplyInputs(); afPreview();">Aplicar + ver</button>
						</div>
					</div>
					<p class="af-hint">Campos en unidades SoftShop (twips). El tamaño de etiqueta es en pulgadas/dots. Guardar escribe <code>label_layouts/shared/tipo_<?php echo h($selected['id']); ?>.json</code> para todos los ítems #<?php echo h($selected['id']); ?>.</p>
				</div>
			</div>
			<script>
			var AF = {
				tipo: <?php echo json_encode((string)$selected['id']); ?>,
				sample: <?php echo json_encode($sampleCode); ?>,
				layout: <?php echo json_encode($sharedLayout ? $sharedLayout : new stdClass()); ?>
			};
			if (!AF.layout.fields) AF.layout.fields = {};
			if (!AF.layout.label) AF.layout.label = {};
			AF.layout.etiqueta = AF.tipo;
			AF.layout.shared = true;

			function afStatus(msg) {
				var el = document.getElementById('afStatus');
				if (el) el.textContent = msg || '';
			}
			function afRound(n, d) {
				var p = Math.pow(10, d || 2);
				return Math.round(n * p) / p;
			}
			function afSyncLabelFromIn() {
				var win = parseFloat(document.getElementById('af_label_w_in').value);
				var hin = parseFloat(document.getElementById('af_label_h_in').value);
				if (!isNaN(win) && win > 0) {
					document.getElementById('af_label_w_dots').value = Math.round(win * 203);
				}
				if (!isNaN(hin) && hin > 0) {
					document.getElementById('af_label_h_dots').value = Math.round(hin * 203);
				}
			}
			function afSyncLabelFromDots() {
				var wd = parseFloat(document.getElementById('af_label_w_dots').value);
				var hd = parseFloat(document.getElementById('af_label_h_dots').value);
				if (!isNaN(wd) && wd > 0) {
					document.getElementById('af_label_w_in').value = afRound(wd / 203, 3);
				}
				if (!isNaN(hd) && hd > 0) {
					document.getElementById('af_label_h_in').value = afRound(hd / 203, 3);
				}
			}
			function afLoadLabelSize() {
				var lab = AF.layout.label || {};
				var win = null, hin = null, wd = null, hd = null;
				if (lab.width_in != null) win = parseFloat(lab.width_in);
				if (lab.height_in != null) hin = parseFloat(lab.height_in);
				if (lab.width_dots != null) wd = parseInt(lab.width_dots, 10);
				if (lab.height_dots != null) hd = parseInt(lab.height_dots, 10);
				// Compat: tipo 5 guardaba width/height en twips
				if ((win == null || isNaN(win)) && lab.width != null && AF.tipo === '5') {
					win = parseFloat(lab.width) / 1440;
				}
				if ((hin == null || isNaN(hin)) && lab.height != null && AF.tipo === '5') {
					hin = parseFloat(lab.height) / 1440;
				}
				if ((win == null || isNaN(win)) && lab.width != null && AF.tipo !== '5') {
					wd = parseInt(lab.width, 10);
					win = wd / 203;
				}
				if ((hin == null || isNaN(hin)) && lab.height != null && AF.tipo !== '5') {
					hd = parseInt(lab.height, 10);
					hin = hd / 203;
				}
				// Defaults por tipo
				if (win == null || isNaN(win) || win <= 0) win = (AF.tipo === '5') ? 2.358 : 1.0;
				if (hin == null || isNaN(hin) || hin <= 0) hin = (AF.tipo === '5') ? 1.571 : 0.5;
				document.getElementById('af_label_w_in').value = afRound(win, 3);
				document.getElementById('af_label_h_in').value = afRound(hin, 3);
				afSyncLabelFromIn();
			}
			function afApplyLabelSize() {
				if (!AF.layout.label) AF.layout.label = {};
				var win = parseFloat(document.getElementById('af_label_w_in').value);
				var hin = parseFloat(document.getElementById('af_label_h_in').value);
				var wd = parseInt(document.getElementById('af_label_w_dots').value, 10);
				var hd = parseInt(document.getElementById('af_label_h_dots').value, 10);
				if (!isNaN(win) && win > 0) AF.layout.label.width_in = afRound(win, 3);
				if (!isNaN(hin) && hin > 0) AF.layout.label.height_in = afRound(hin, 3);
				if (!isNaN(wd) && wd > 0) AF.layout.label.width_dots = wd;
				if (!isNaN(hd) && hd > 0) AF.layout.label.height_dots = hd;
				// Mantener twips para tipo 5 (compat impresion)
				if (AF.tipo === '5') {
					if (!isNaN(win) && win > 0) AF.layout.label.width = Math.round(win * 1440);
					if (!isNaN(hin) && hin > 0) AF.layout.label.height = Math.round(hin * 1440);
				}
			}
			function afFieldKeys() {
				var keys = [];
				for (var k in AF.layout.fields) {
					if (AF.layout.fields.hasOwnProperty(k)) keys.push(k);
				}
				keys.sort();
				return keys;
			}
			function afFillSelect() {
				var sel = document.getElementById('afFieldSel');
				sel.innerHTML = '';
				var keys = afFieldKeys();
				for (var i = 0; i < keys.length; i++) {
					var o = document.createElement('option');
					o.value = keys[i];
					o.textContent = keys[i];
					sel.appendChild(o);
				}
				if (keys.length) afLoadField(keys[0]);
				sel.onchange = function () { afLoadField(sel.value); };
			}
			function afLoadField(name) {
				var f = AF.layout.fields[name] || {};
				document.getElementById('af_x').value = f.x != null ? f.x : '';
				document.getElementById('af_y').value = f.y != null ? f.y : '';
				document.getElementById('af_font').value = f.font != null ? f.font : '';
				document.getElementById('af_w').value = f.w != null ? f.w : '';
				document.getElementById('af_h').value = f.h != null ? f.h : '';
				document.getElementById('af_alcance').value = f.alcance != null ? f.alcance : '';
				document.getElementById('af_renglon').value = f.renglon != null ? f.renglon : '';
				document.getElementById('af_ratio').value = f.font_w_ratio != null ? f.font_w_ratio : (name === 'descripcion' ? 1 : '');
				document.getElementById('af_titulo').value = f.titulo != null ? f.titulo : '';
			}
			function afNum(id) {
				var v = document.getElementById(id).value;
				if (v === '' || v == null) return null;
				var n = parseFloat(v);
				return isNaN(n) ? null : n;
			}
			function afApplyInputs() {
				afApplyLabelSize();
				var name = document.getElementById('afFieldSel').value;
				if (!name) return;
				if (!AF.layout.fields[name]) AF.layout.fields[name] = {};
				var f = AF.layout.fields[name];
				var x = afNum('af_x'); if (x !== null) f.x = Math.round(x);
				var y = afNum('af_y'); if (y !== null) f.y = Math.round(y);
				var font = afNum('af_font'); if (font !== null) f.font = font;
				var w = afNum('af_w'); if (w !== null) f.w = Math.round(w);
				var h = afNum('af_h'); if (h !== null) f.h = Math.round(h);
				var al = afNum('af_alcance'); if (al !== null) f.alcance = Math.round(al);
				var re = afNum('af_renglon'); if (re !== null) f.renglon = Math.round(re);
				var ratio = afNum('af_ratio'); if (ratio !== null) f.font_w_ratio = ratio;
				f.titulo = document.getElementById('af_titulo').value;
			}
			function afNudge(dx, dy) {
				var step = afNum('af_step');
				if (step === null || step < 1) step = 20;
				var xEl = document.getElementById('af_x');
				var yEl = document.getElementById('af_y');
				var x = parseFloat(xEl.value) || 0;
				var y = parseFloat(yEl.value) || 0;
				xEl.value = Math.round(x + dx * step);
				yEl.value = Math.round(y + dy * step);
				afApplyInputs();
			}
			function afPreview() {
				afApplyInputs();
				if (!AF.sample) {
					afStatus('No hay ítem de muestra para previsualizar.');
					return;
				}
				afStatus('Generando vista en vivo…');
				var base = 'codigo=' + encodeURIComponent(AF.sample)
					+ '&caducidad=2&elab_day=' + encodeURIComponent(new Date().toISOString().slice(0, 10))
					+ '&include_zpl=1&_=' + Date.now();
				var img = document.getElementById('afPreviewImg');
				var zplBox = document.getElementById('afZpl');
				img.style.display = 'none';
				var fd = new FormData();
				fd.append('layout_json', JSON.stringify(AF.layout));
				var xhr = new XMLHttpRequest();
				xhr.open('POST', 'preview_zpl.php?fmt=json&' + base, true);
				xhr.timeout = 60000;
				xhr.onload = function () {
					var r = null;
					try { r = JSON.parse(xhr.responseText); } catch (e) {}
					if (!r || !r.ok) {
						afStatus((r && r.error) ? r.error : 'Error de vista previa');
						return;
					}
					afStatus('Tipo ' + r.etiqueta + ' — ' + r.pw + '×' + r.ll + ' dots. Cargando imagen…');
					if (r.zpl) {
						zplBox.style.display = 'block';
						zplBox.textContent = r.zpl;
					}
					img.onload = function () {
						img.style.display = 'block';
						afStatus('Vista tipo ' + r.etiqueta + ' (' + r.width_in + '" × ' + r.height_in + '") — aún no guardado');
					};
					img.onerror = function () {
						afStatus('ZPL OK, pero Labelary no devolvió imagen (red). Puede guardar e imprimir de prueba.');
					};
					var xhr2 = new XMLHttpRequest();
					xhr2.open('POST', 'preview_zpl.php?fmt=png&' + base, true);
					xhr2.responseType = 'blob';
					xhr2.onload = function () {
						if (xhr2.status >= 200 && xhr2.status < 300) {
							img.src = URL.createObjectURL(xhr2.response);
						} else {
							img.onerror();
						}
					};
					xhr2.onerror = function () { img.onerror(); };
					var fd2 = new FormData();
					fd2.append('layout_json', JSON.stringify(AF.layout));
					xhr2.send(fd2);
				};
				xhr.onerror = function () { afStatus('Error de red en vista previa'); };
				xhr.send(fd);
			}
			function afSave(silent, cb) {
				afApplyInputs();
				AF.layout.etiqueta = AF.tipo;
				AF.layout.shared = true;
				afStatus(silent ? 'Actualizando layout…' : 'Guardando para todos los ítems #' + AF.tipo + '…');
				var xhr = new XMLHttpRequest();
				xhr.open('POST', 'api_label_layout.php?action=save_shared', true);
				xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
				xhr.onload = function () {
					var r = null;
					try { r = JSON.parse(xhr.responseText); } catch (e) {}
					if (!r || !r.ok) {
						afStatus((r && r.error) ? r.error : 'No se pudo guardar');
						if (cb) cb(false);
						return;
					}
					if (!silent) {
						afStatus('Guardado. Todos los ítems con etiqueta #' + AF.tipo + ' usarán este layout al imprimir.');
					}
					if (cb) cb(true);
				};
				xhr.onerror = function () {
					afStatus('Error de red al guardar');
					if (cb) cb(false);
				};
				xhr.send(JSON.stringify(AF.layout));
			}
			document.getElementById('afPreviewBtn').onclick = function () { afPreview(); };
			document.getElementById('afSaveBtn').onclick = function () { afSave(false); };
			document.getElementById('af_label_w_in').addEventListener('change', afSyncLabelFromIn);
			document.getElementById('af_label_h_in').addEventListener('change', afSyncLabelFromIn);
			document.getElementById('af_label_w_dots').addEventListener('change', afSyncLabelFromDots);
			document.getElementById('af_label_h_dots').addEventListener('change', afSyncLabelFromDots);
			afLoadLabelSize();
			afFillSelect();
			</script>
			<?php } ?>
		</section>
		<?php } else { ?>
		<p class="af-pick">Elija un formato arriba para ver detalle y editar (si es compartido).</p>
		<?php } ?>
	</div>
</body>
</html>
