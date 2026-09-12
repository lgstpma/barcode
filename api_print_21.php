<?php
/**
 * API JSON — cola ZPL unificada (formatos 1,5,9,10,14,21 + gtin).
 * Formato #13 se imprime por IP (exportarimg), no por este servicio.
 *
 * Claim (toma jobs; no se repiten entre workers):
 *   GET api_print_21.php?key=barcode21&claim=1&worker=PC1
 *   Opcional: &printers=GK420t_chica,VirtualZPLPrinter_Sistemas
 *
 * Marcar:
 *   POST {"id":123,"estado":"impreso"|"error"|"liberar"}
 *
 * Varios usuarios encolan (INSERT); varios PCs imprimen con claim atómico.
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php';

$API_KEY = 'barcode21';

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
	$payload = array_merge(array('ok' => $ok, 'service' => 'zpl_queue'), $extra);
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
$etiqIn = zpl_queue_sql_in_etiquetas(zpl_queue_service_etiquetas_21());

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

$worker = '';
if (isset($body['worker'])) {
	$worker = (string)$body['worker'];
} elseif (isset($_GET['worker'])) {
	$worker = (string)$_GET['worker'];
}
if ($worker === '') {
	$worker = isset($_SERVER['REMOTE_ADDR']) ? ('ip-' . $_SERVER['REMOTE_ADDR']) : 'api';
}

if ($method === 'POST' || $estadoReq === 'impreso' || $estadoReq === 'error' || $estadoReq === 'liberar' || $estadoReq === 'pendiente') {
	if ($id <= 0) {
		api_json(false, array('error' => 'falta id'), 400);
	}

	if ($estadoReq === 'liberar' || $estadoReq === 'pendiente') {
		$n = zpl_queue_release_job($link, $id, $worker);
		api_json(true, array('id' => $id, 'estado' => 'pendiente', 'updated' => $n));
	}

	$nuevo = ($estadoReq === 'error') ? 2 : 1;
	// No filtrar por etiqueta: el job ya está en cola; si no, se queda en "enviando" eterno.
	$st = mysqli_prepare($link, "UPDATE isabel_zpl_queue SET estado=?, printed_at=NOW(), locked_by=NULL, locked_at=NULL WHERE id=? AND estado IN (0,3)");
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

// GET: por defecto claim atómico (varios workers no se pisan)
$doClaim = true;
if (isset($_GET['claim']) && ($_GET['claim'] === '0' || strtolower((string)$_GET['claim']) === 'false')) {
	$doClaim = false;
}

$printerFilter = '';
if (isset($_GET['printer'])) {
	$printerFilter = trim((string)$_GET['printer']);
}
$printersCsv = isset($_GET['printers']) ? trim((string)$_GET['printers']) : '';

if ($doClaim) {
	$jobsRaw = zpl_queue_claim($link, $etiqIn, $worker, 20, $printerFilter, $printersCsv);
} else {
	zpl_queue_release_stale($link, 5);
	$jobsRaw = array();
	$sqlJobs = "SELECT id, itemid, etiqueta, printer, zpl, created_at FROM isabel_zpl_queue WHERE etiqueta IN ($etiqIn) AND estado=0";
	if ($printerFilter !== '') {
		$sqlJobs .= " AND printer='" . mysqli_real_escape_string($link, $printerFilter) . "'";
	}
	$sqlJobs .= " ORDER BY id ASC LIMIT 20";
	$res = mysqli_query($link, $sqlJobs);
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$jobsRaw[] = $row;
		}
		mysqli_free_result($res);
	}
}

$jobs = array();
foreach ($jobsRaw as $row) {
	$jobs[] = array(
		'id' => (int)$row['id'],
		'itemid' => $row['itemid'],
		'etiqueta' => $row['etiqueta'],
		'printer' => isset($row['printer']) ? $row['printer'] : '',
		'estado' => $doClaim ? 'tomado' : 'pendiente',
		'created_at' => $row['created_at'],
		'zpl' => $row['zpl'],
	);
}

api_json(true, array(
	'count' => count($jobs),
	'worker' => $worker,
	'claim' => $doClaim,
	'formatos' => zpl_queue_service_etiquetas_21(),
	'jobs' => $jobs
));
