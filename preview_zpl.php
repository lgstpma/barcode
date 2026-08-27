<?php
/**
 * Vista previa ZPL (JSON liviano o PNG).
 * Uso: preview_zpl.php?codigo=025295&fmt=json|png&caducidad=2&elab_day=2026-08-20
 */
@ini_set('memory_limit', '256M');
@set_time_limit(90);
ob_start();
header('X-Content-Type-Options: nosniff');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'conections.php');

$fmt = isset($_REQUEST['fmt']) ? strtolower(trim((string)$_REQUEST['fmt'])) : 'json';
if ($fmt !== 'png' && $fmt !== 'json') {
	$fmt = 'json';
}

$codigo = isset($_REQUEST['codigo']) ? preg_replace('/\D/', '', (string)$_REQUEST['codigo']) : '';
if ($codigo === '') {
	$codigo = isset($_REQUEST['code']) ? preg_replace('/\D/', '', (string)$_REQUEST['code']) : '';
}
$codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
$caducidad = isset($_REQUEST['caducidad']) ? (int)$_REQUEST['caducidad'] : 0;
if ($caducidad < 0) {
	$caducidad = 0;
}
$elab_day = isset($_REQUEST['elab_day']) ? trim((string)$_REQUEST['elab_day']) : date('Y-m-d');
$cant = 1;

function preview_zpl_fail($fmt, $msg, $extra = array())
{
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	if ($fmt === 'png') {
		header('Content-Type: text/plain; charset=utf-8');
		header('HTTP/1.1 400 Bad Request');
		echo $msg;
		exit;
	}
	header('Content-Type: application/json; charset=utf-8');
	$out = array_merge(array('ok' => false, 'error' => $msg), $extra);
	echo json_encode($out);
	exit;
}

function preview_zpl_force_pq1($zpl)
{
	$zpl = (string)$zpl;
	if (preg_match('/\^PQ\d+/', $zpl)) {
		$zpl = preg_replace('/\^PQ\d+/', '^PQ1', $zpl);
	}
	return $zpl;
}

function preview_zpl_inches($dots)
{
	$dots = max(1, (int)$dots);
	return round($dots / 203.0, 3);
}

function preview_zpl_labelary_png($zpl, $pw, $ll)
{
	$wIn = preview_zpl_inches($pw);
	$hIn = preview_zpl_inches($ll);
	if ($wIn < 0.2) {
		$wIn = 0.2;
	}
	if ($hIn < 0.2) {
		$hIn = 0.2;
	}
	if ($wIn > 15) {
		$wIn = 15;
	}
	if ($hIn > 15) {
		$hIn = 15;
	}
	$url = 'http://api.labelary.com/v1/printers/8dpmm/labels/' . $wIn . 'x' . $hIn . '/0/';
	$ctx = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: image/png\r\n",
			'content' => $zpl,
			'timeout' => 45,
			'ignore_errors' => true,
		),
	));
	$bin = @file_get_contents($url, false, $ctx);
	if ($bin === false || strlen($bin) < 24) {
		return array('ok' => false, 'error' => 'Labelary no respondio (red o ZPL muy grande)');
	}
	if (substr($bin, 0, 8) !== "\x89PNG\r\n\x1a\n") {
		$msg = trim(substr($bin, 0, 200));
		return array('ok' => false, 'error' => 'Labelary: ' . ($msg !== '' ? $msg : 'respuesta no PNG'));
	}
	return array('ok' => true, 'png' => $bin);
}

$link = conec_mysql();
if (!$link) {
	preview_zpl_fail($fmt, 'Sin conexion MySQL');
}

$codeEsc = mysqli_real_escape_string($link, $codigo);
$res = mysqli_query($link, "SELECT * FROM items WHERE codigo='$codeEsc' LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if ($res) {
	mysqli_free_result($res);
}
if (!$row) {
	preview_zpl_fail($fmt, 'Item no encontrado: ' . $codigo);
}

$etiqueta = isset($row['etiqueta']) ? trim((string)$row['etiqueta']) : '';
$zpl = '';
$pw = 0;
$ll = 0;
$printer = '';
$err = '';
$previewLocalPng = null;

if ($etiqueta === '1' || $etiqueta === 1 || $etiqueta === '9' || $etiqueta === 9) {
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_1.php');
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
	$descrip = isset($row['descrip3']) ? $row['descrip3'] : '';
	$precio = isset($row['precio2']) ? $row['precio2'] : 0;
	$useJson = !isset($_REQUEST['use_json']) || (string)$_REQUEST['use_json'] !== '0';
	$layoutOverride = null;
	if ($useJson) {
		$tipoShared = ($etiqueta === '9' || $etiqueta === 9) ? 9 : 1;
		$layoutOverride = label_layout_ensure_shared($tipoShared);
	}
	if ($etiqueta === '9' || $etiqueta === 9) {
		$zpl = build_zpl_etiqueta_9($codigo, $descrip, $precio, $cant, $layoutOverride);
	} else {
		$zpl = build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, $caducidad, $elab_day, true, $layoutOverride);
	}
	$pw = 203;
	$ll = 102;
	$printer = 'GK420t_chica';
} elseif ($etiqueta === '5' || $etiqueta === 5) {
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_5.php');
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
	$descrip = isset($row['descrip3']) ? $row['descrip3'] : '';
	$precio = isset($row['precio2']) ? $row['precio2'] : 0;
	$ingre = isset($row['ingredientes']) ? $row['ingredientes'] : '';
	$useJson = !isset($_REQUEST['use_json']) || (string)$_REQUEST['use_json'] !== '0';
	$layoutOverride = $useJson ? label_layout_ensure_shared(5) : null;
	$zpl = build_zpl_etiqueta_5($codigo, $descrip, $precio, $cant, $caducidad, $elab_day, $ingre, $layoutOverride);
	$pw = 609;
	$ll = 406;
	$printer = 'GK420t_grande';
} elseif ($etiqueta === '10' || $etiqueta === 10 || $etiqueta === '14' || $etiqueta === 14) {
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_10.php');
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
	$lbls = etiqueta10_lookup_lbls($link, $codigo);
	$lines = etiqueta10_lookup_lines($link, $codigo);
	// Preview / pruebas: preferir JSON local si existe (no afecta cola de produccion)
	$useJson = !isset($_REQUEST['use_json']) || (string)$_REQUEST['use_json'] !== '0';
	if ($useJson) {
		$layoutJson = label_layout_load_item($link, $codigo, true);
		if ($layoutJson) {
			$merged = label_layout_apply_to_lbls_lines($layoutJson, $lbls, $lines);
			$lbls = $merged['lbls'];
			$lines = $merged['lines'];
		}
	}
	$ctx = array(
		'itemid' => (string)$codigo,
		'precio' => isset($row['precio2']) ? $row['precio2'] : 0,
		'descripcion' => isset($row['descrip3']) ? $row['descrip3'] : '',
		'ingredientes' => isset($row['ingredientes']) ? $row['ingredientes'] : '',
		'especif' => isset($row['especif']) ? $row['especif'] : '',
		'obs' => isset($row['obs']) ? $row['obs'] : '',
		'reg_sanitario' => isset($row['reg_sanitario']) ? $row['reg_sanitario'] : '',
		'expir' => $caducidad,
		'fecha_manufact' => $elab_day,
		'lines' => $lines,
	);
	$GLOBALS['etiqueta10_want_preview_bits'] = ($fmt === 'png');
	if ($etiqueta === '14' || $etiqueta === 14) {
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_14.php');
		$codigo2 = isset($row['codigo2']) ? $row['codigo2'] : $codigo;
		$built = build_zpl_etiqueta_14($codigo, $codigo2, $cant, $lbls, $ctx, $link);
	} else {
		$built = build_zpl_etiqueta_10($codigo, $cant, $lbls, '', $ctx, $link);
	}
	$GLOBALS['etiqueta10_want_preview_bits'] = false;
	if (empty($built['ok'])) {
		$err = isset($built['error']) ? $built['error'] : 'No se genero ZPL 10/14';
	} else {
		$zpl = $built['zpl'];
		$pw = isset($built['pw']) ? (int)$built['pw'] : 0;
		$ll = isset($built['ll']) ? (int)$built['ll'] : 0;
		$printer = isset($built['printer']) ? $built['printer'] : '';
		$previewLocalPng = null;
		if ($fmt === 'png' && !empty($built['label_bits'])) {
			$previewLocalPng = etiqueta10_label_bits_to_png(
				$built['label_bits'],
				isset($built['bar_box']) ? $built['bar_box'] : null,
				isset($built['overlays']) ? $built['overlays'] : null
			);
		}
	}
} elseif ($etiqueta === '13' || $etiqueta === 13) {
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_13.php');
	$descrip = isset($row['descrip']) ? $row['descrip'] : '';
	$descrip2 = isset($row['descrip2']) ? $row['descrip2'] : '';
	if (trim((string)$descrip) === '' && isset($row['descrip3'])) {
		$descrip = $row['descrip3'];
	}
	$precio = isset($row['precio2']) ? $row['precio2'] : 0;
	$zpl = build_zpl_etiqueta_13($descrip, $descrip2, $precio, $cant, $caducidad, $elab_day);
	$pw = 183;
	$ll = 495;
	$printer = 'IP:' . etiqueta13_printer_ip();
} elseif ($etiqueta === '21' || $etiqueta === 21) {
	$err = 'Tipo 21 no tiene generador ZPL en preview (usa cola dedicada).';
} else {
	$err = 'Tipo de etiqueta no soportado en vista previa: ' . $etiqueta;
}

if ($err !== '') {
	preview_zpl_fail($fmt, $err, array('etiqueta' => $etiqueta, 'codigo' => $codigo));
}

$zpl = preview_zpl_force_pq1($zpl);
if ($zpl === '' || strpos($zpl, '^XA') === false) {
	preview_zpl_fail($fmt, 'ZPL vacio', array('etiqueta' => $etiqueta, 'codigo' => $codigo));
}

if ($pw < 1 || $ll < 1) {
	if (preg_match('/\^PW(\d+)/', $zpl, $m)) {
		$pw = (int)$m[1];
	}
	if (preg_match('/\^LL(\d+)/', $zpl, $m)) {
		$ll = (int)$m[1];
	}
}
if ($pw < 1) {
	$pw = 203;
}
if ($ll < 1) {
	$ll = 102;
}

$previewLocalPng = isset($previewLocalPng) ? $previewLocalPng : null;

if ($fmt === 'png') {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	if ($previewLocalPng) {
		header('Content-Type: image/png');
		header('Cache-Control: no-store');
		echo $previewLocalPng;
		exit;
	}
	$rend = preview_zpl_labelary_png($zpl, $pw, $ll);
	if (empty($rend['ok'])) {
		preview_zpl_fail('png', isset($rend['error']) ? $rend['error'] : 'Error render');
	}
	header('Content-Type: image/png');
	header('Cache-Control: no-store');
	echo $rend['png'];
	exit;
}

while (ob_get_level() > 0) {
	ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
$includeZpl = isset($_REQUEST['include_zpl']) && (string)$_REQUEST['include_zpl'] === '1';
$zplLen = strlen($zpl);
$payload = array(
	'ok' => true,
	'codigo' => $codigo,
	'etiqueta' => $etiqueta,
	'printer' => $printer,
	'pw' => $pw,
	'll' => $ll,
	'width_in' => preview_zpl_inches($pw),
	'height_in' => preview_zpl_inches($ll),
	'zpl_len' => $zplLen,
);
// No devolver ^GFA completo por defecto (congela el navegador).
if ($includeZpl && $zplLen <= 80000) {
	$payload['zpl'] = $zpl;
} elseif ($includeZpl) {
	$payload['zpl'] = substr($zpl, 0, 4000) . "\n... [ZPL truncado, " . $zplLen . " bytes] ...\n";
	$payload['zpl_truncated'] = true;
}
echo json_encode($payload);