<?php
/**
 * API: guardar / leer layout plantilla chica (#1/#9/#15).
 * POST JSON: { "action":"save", "layout":{...} }
 * GET ?action=get
 */
@ini_set('memory_limit', '64M');
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

include_once __DIR__ . DIRECTORY_SEPARATOR . 'zpl_chica_plantilla.php';

function api_chica_out($arr, $code = 200)
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

$action = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action']) : '';
$raw = file_get_contents('php://input');
$body = $raw ? json_decode($raw, true) : null;
if (is_array($body) && isset($body['action']) && $action === '') {
	$action = trim((string)$body['action']);
}
if ($action === '') {
	$action = 'get';
}

if ($action === 'get') {
	api_chica_out(array(
		'ok' => true,
		'layout' => zpl_chica_load_layout(),
		'offsets' => zpl_chica_load_offsets(),
	));
}

if ($action === 'save') {
	$layout = null;
	if (is_array($body) && isset($body['layout']) && is_array($body['layout'])) {
		$layout = $body['layout'];
	} elseif (isset($_POST['layout'])) {
		$layout = json_decode((string)$_POST['layout'], true);
	}
	if (!is_array($layout)) {
		api_chica_out(array('ok' => false, 'error' => 'layout invalido'), 400);
	}
	$ok = zpl_chica_save_layout($layout);
	if (!$ok) {
		api_chica_out(array('ok' => false, 'error' => 'No se pudo escribir tipo_chica.json (permisos)'), 500);
	}
	api_chica_out(array(
		'ok' => true,
		'layout' => zpl_chica_load_layout(),
		'path' => 'label_layouts/shared/tipo_chica.json',
	));
}

api_chica_out(array('ok' => false, 'error' => 'action desconocida'), 400);
