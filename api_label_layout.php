<?php
/**
 * API layouts JSON locales + upload imagen printserver.
 *
 * GET  ?action=get&codigo=024822
 * GET  ?action=get_shared&tipo=1
 * POST action=save  (JSON body o form layout=...)
 * POST action=save_shared
 * POST action=upload_image (multipart: codigo, imagen)
 */
@ini_set('memory_limit', '128M');
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

include_once(__DIR__ . DIRECTORY_SEPARATOR . 'conections.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');

function api_layout_out($arr, $code = 200)
{
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	if ($code !== 200) {
		header('HTTP/1.1 ' . (int)$code);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($arr);
	exit;
}

$action = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action']) : 'get';
$link = conec_mysql();

if ($action === 'get') {
	$codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';
	if ($codigo === '') {
		api_layout_out(array('ok' => false, 'error' => 'codigo invalido'), 400);
	}
	// Si el item es tipo 1/5/9 → devolver layout compartido
	$etiquetaItem = '';
	if ($link) {
		$code = label_layout_pad_codigo($codigo);
		$codeEsc = mysqli_real_escape_string($link, $code);
		$res = mysqli_query($link, "SELECT etiqueta FROM items WHERE codigo='$codeEsc' LIMIT 1");
		if ($res && ($row = mysqli_fetch_assoc($res))) {
			$etiquetaItem = isset($row['etiqueta']) ? trim((string)$row['etiqueta']) : '';
			mysqli_free_result($res);
		}
	}
	if (in_array($etiquetaItem, array('1', '5', '9'), true)) {
		$layout = label_layout_ensure_shared((int)$etiquetaItem);
		$layout['codigo'] = label_layout_pad_codigo($codigo);
		$layout['etiqueta'] = $etiquetaItem;
		$layout['exists'] = true;
		api_layout_out(array(
			'ok' => true,
			'layout' => $layout,
			'mode' => 'json_shared',
			'note' => 'Layout compartido tipo ' . $etiquetaItem . ' (label_layouts/shared/). MySQL no se modifica.',
		));
	}
	$layout = label_layout_load_item($link, $codigo, true);
	if (!$layout) {
		api_layout_out(array('ok' => false, 'error' => 'No se pudo cargar layout'), 404);
	}
	if ($etiquetaItem !== '') {
		$layout['etiqueta'] = $etiquetaItem;
	}
	label_layout_ensure_shared(1);
	label_layout_ensure_shared(5);
	label_layout_ensure_shared(9);
	api_layout_out(array(
		'ok' => true,
		'layout' => $layout,
		'mode' => 'json_local',
		'note' => 'Edicion en JSON local; MySQL de produccion no se modifica.',
	));
}

if ($action === 'get_shared') {
	$tipo = isset($_REQUEST['tipo']) ? (int)$_REQUEST['tipo'] : 0;
	if (!in_array($tipo, array(1, 5, 9), true)) {
		api_layout_out(array('ok' => false, 'error' => 'tipo shared debe ser 1, 5 o 9'), 400);
	}
	$layout = label_layout_ensure_shared($tipo);
	api_layout_out(array('ok' => true, 'layout' => $layout));
}

if ($action === 'save') {
	$raw = file_get_contents('php://input');
	$data = null;
	if ($raw !== false && $raw !== '' && isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
		$data = json_decode($raw, true);
	}
	if (!is_array($data) && isset($_POST['layout'])) {
		$data = json_decode((string)$_POST['layout'], true);
	}
	if (!is_array($data)) {
		api_layout_out(array('ok' => false, 'error' => 'JSON layout invalido'), 400);
	}
	$codigo = isset($data['codigo']) ? $data['codigo'] : (isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '');
	$etiq = isset($data['etiqueta']) ? (string)$data['etiqueta'] : '';
	// Shared 1/5/9
	if (!empty($data['shared']) || in_array($etiq, array('1', '5', '9'), true)) {
		$tipo = (int)$etiq;
		if (!in_array($tipo, array(1, 5, 9), true)) {
			api_layout_out(array('ok' => false, 'error' => 'tipo shared invalido'), 400);
		}
		$data['etiqueta'] = (string)$tipo;
		$data['shared'] = true;
		unset($data['path'], $data['exists'], $data['seeded'], $data['from_mysql'], $data['codigo']);
		$ok = label_layout_write_json_file(label_layout_shared_path($tipo), $data);
		if (!$ok) {
			api_layout_out(array('ok' => false, 'error' => 'No se pudo escribir shared JSON'), 500);
		}
		api_layout_out(array(
			'ok' => true,
			'path' => label_layout_shared_path($tipo),
			'tipo' => $tipo,
			'mode' => 'shared',
		));
	}
	if ($codigo === '') {
		api_layout_out(array('ok' => false, 'error' => 'Falta codigo'), 400);
	}
	$ok = label_layout_save_item($codigo, $data);
	if (!$ok) {
		api_layout_out(array('ok' => false, 'error' => 'No se pudo escribir JSON'), 500);
	}
	api_layout_out(array(
		'ok' => true,
		'path' => label_layout_item_path($codigo),
		'codigo' => label_layout_pad_codigo($codigo),
	));
}

if ($action === 'save_shared') {
	$raw = file_get_contents('php://input');
	$data = json_decode($raw !== false ? $raw : '', true);
	if (!is_array($data) && isset($_POST['layout'])) {
		$data = json_decode((string)$_POST['layout'], true);
	}
	$tipo = isset($data['etiqueta']) ? (int)$data['etiqueta'] : (isset($_REQUEST['tipo']) ? (int)$_REQUEST['tipo'] : 0);
	if (!in_array($tipo, array(1, 5, 9), true)) {
		api_layout_out(array('ok' => false, 'error' => 'tipo shared debe ser 1, 5 o 9'), 400);
	}
	$data['etiqueta'] = (string)$tipo;
	$data['shared'] = true;
	$ok = label_layout_write_json_file(label_layout_shared_path($tipo), $data);
	if (!$ok) {
		api_layout_out(array('ok' => false, 'error' => 'No se pudo escribir shared JSON'), 500);
	}
	api_layout_out(array('ok' => true, 'path' => label_layout_shared_path($tipo), 'tipo' => $tipo));
}

if ($action === 'upload_image') {
	$codigo = isset($_POST['codigo']) ? $_POST['codigo'] : (isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '');
	if ($codigo === '') {
		api_layout_out(array('ok' => false, 'error' => 'Falta codigo'), 400);
	}
	if (!isset($_FILES['imagen']) || !is_array($_FILES['imagen'])) {
		api_layout_out(array('ok' => false, 'error' => 'Falta archivo imagen'), 400);
	}
	$err = isset($_FILES['imagen']['error']) ? (int)$_FILES['imagen']['error'] : UPLOAD_ERR_NO_FILE;
	if ($err !== UPLOAD_ERR_OK) {
		api_layout_out(array('ok' => false, 'error' => 'Error upload #' . $err), 400);
	}
	$res = label_layout_save_design_image(
		$codigo,
		$_FILES['imagen']['tmp_name'],
		isset($_FILES['imagen']['name']) ? $_FILES['imagen']['name'] : 'img.bmp'
	);
	if (empty($res['ok'])) {
		api_layout_out(array('ok' => false, 'error' => isset($res['error']) ? $res['error'] : 'fallo'), 500);
	}
	api_layout_out(array(
		'ok' => true,
		'file' => $res['file'],
		'path' => $res['path'],
		'note' => 'Guardado en printserver (diseno etiqueta 10/14). No toca img_prd MySQL.',
	));
}

api_layout_out(array('ok' => false, 'error' => 'accion desconocida: ' . $action), 400);
