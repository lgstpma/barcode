<?php
/**
 * API JSON - cola de impresión etiqueta 1
 *
 * Listar pendientes:
 *   GET  api_print_1.php?key=barcode1
 *
 * Marcar impreso:
 *   POST api_print_1.php?key=barcode1
 *   Body JSON: {"id":123,"estado":"impreso"}
 *
 *   o GET api_print_1.php?key=barcode1&id=123&estado=impreso
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php';

$API_KEY = 'barcode1';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

function api_json($ok, $extra = array(), $http = 200)
{
	http_response_code($http);
	$payload = array_merge(array('ok' => $ok, 'service' => 'etiqueta1'), $extra);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function api_key_ok($expected)
{
	$key = '';
	if (isset($_GET['key'])) {
		$key = (string)$_GET['key'];
	} elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
		$key = (string)$_SERVER['HTTP_X_API_KEY'];
	}
	return hash_equals($expected, $key);
}

function api_read_json_body()
{
	$raw = file_get_contents('php://input');
	if ($raw === false || trim($raw) === '') {
		return array();
	}
	$decoded = json_decode($raw, true);
	return is_array($decoded) ? $decoded : array();
}

if (!api_key_ok($API_KEY)) {
	api_json(false, array('error' => 'key invalida'), 403);
}

$link = conec_mysql();
ensure_zpl_queue($link);

$method = $_SERVER['REQUEST_METHOD'];
$body = api_read_json_body();

$id = 0;
if (isset($body['id'])) {
	$id = (int)$body['id'];
} elseif (isset($_GET['id'])) {
	$id = (int)$_GET['id'];
}

$estadoReq = '';
if (isset($body['estado'])) {
	$estadoReq = strtolower(trim((string)$body['estado']));
} elseif (isset($_GET['estado'])) {
	$estadoReq = strtolower(trim((string)$_GET['estado']));
}

if ($method === 'POST' || $estadoReq === 'impreso' || $estadoReq === 'error') {
	if ($id <= 0) {
		api_json(false, array('error' => 'falta id'), 400);
	}

	$nuevo = ($estadoReq === 'error') ? 2 : 1;
	$st = mysqli_prepare($link, "UPDATE isabel_zpl_queue SET estado=?, printed_at=NOW() WHERE id=? AND etiqueta='1' AND estado=0");
	mysqli_stmt_bind_param($st, "ii", $nuevo, $id);
	mysqli_stmt_execute($st);
	$n = mysqli_stmt_affected_rows($st);
	mysqli_stmt_close($st);

	if ($n < 1) {
		api_json(false, array('error' => 'no se actualizo', 'id' => $id), 404);
	}

	api_json(true, array(
		'id' => $id,
		'estado' => ($nuevo === 1) ? 'impreso' : 'error',
		'updated' => $n
	));
}

$jobs = array();
$printerFilter = '';
if (isset($_GET['printer'])) {
	$printerFilter = trim((string)$_GET['printer']);
}
$sqlJobs = "SELECT id, itemid, etiqueta, printer, zpl, created_at FROM isabel_zpl_queue WHERE etiqueta='1' AND estado=0";
if ($printerFilter !== '') {
	$sqlJobs .= " AND printer='" . mysqli_real_escape_string($link, $printerFilter) . "'";
}
$sqlJobs .= " ORDER BY id ASC LIMIT 20";
$res = mysqli_query($link, $sqlJobs);
if ($res) {
	while ($row = mysqli_fetch_assoc($res)) {
		$jobs[] = array(
			'id' => (int)$row['id'],
			'itemid' => $row['itemid'],
			'etiqueta' => $row['etiqueta'],
			'printer' => isset($row['printer']) ? $row['printer'] : '',
			'estado' => 'pendiente',
			'created_at' => $row['created_at'],
			'zpl' => $row['zpl'],
		);
	}
	mysqli_free_result($res);
}

api_json(true, array('count' => count($jobs), 'jobs' => $jobs));
