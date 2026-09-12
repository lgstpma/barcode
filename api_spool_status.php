<?php
/**
 * API estado de cola / "spooler" isabel_zpl_queue.
 * GET api_spool_status.php?key=barcode21&id=128
 * GET api_spool_status.php?key=barcode21&recent=1&printer=GK420t_chica
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$key = isset($_GET['key']) ? (string)$_GET['key'] : '';
if ($key !== 'barcode21') {
	http_response_code(403);
	echo json_encode(array('ok' => false, 'error' => 'key'));
	exit;
}

$link = conec_mysql();
ensure_zpl_queue($link);

function spool_estado_label($est)
{
	$est = (int)$est;
	$map = array(
		0 => 'pendiente',
		1 => 'impreso',
		2 => 'error',
		3 => 'en_spooler',
	);
	return isset($map[$est]) ? $map[$est] : ('estado_' . $est);
}

function spool_row_public($row)
{
	return array(
		'id' => (int)$row['id'],
		'etiqueta' => $row['etiqueta'],
		'itemid' => $row['itemid'],
		'printer' => isset($row['printer']) ? $row['printer'] : '',
		'estado' => (int)$row['estado'],
		'estado_txt' => spool_estado_label($row['estado']),
		'locked_by' => isset($row['locked_by']) ? $row['locked_by'] : '',
		'created_at' => $row['created_at'],
		'printed_at' => isset($row['printed_at']) ? $row['printed_at'] : null,
	);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
	$st = mysqli_prepare($link, 'SELECT id, etiqueta, itemid, printer, estado, locked_by, created_at, printed_at FROM isabel_zpl_queue WHERE id=? LIMIT 1');
	mysqli_stmt_bind_param($st, 'i', $id);
	mysqli_stmt_execute($st);
	mysqli_stmt_store_result($st);
	mysqli_stmt_bind_result($st, $r_id, $r_etiq, $r_item, $r_prn, $r_est, $r_by, $r_cre, $r_prt);
	$row = null;
	if (mysqli_stmt_fetch($st)) {
		$row = array(
			'id' => $r_id,
			'etiqueta' => $r_etiq,
			'itemid' => $r_item,
			'printer' => $r_prn,
			'estado' => $r_est,
			'locked_by' => $r_by,
			'created_at' => $r_cre,
			'printed_at' => $r_prt,
		);
	}
	mysqli_stmt_close($st);
	if (!$row) {
		echo json_encode(array('ok' => false, 'error' => 'not_found', 'id' => $id));
		exit;
	}
	echo json_encode(array('ok' => true, 'job' => spool_row_public($row)));
	exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
if ($limit < 1) {
	$limit = 12;
}
if ($limit > 40) {
	$limit = 40;
}
$printer = isset($_GET['printer']) ? trim((string)$_GET['printer']) : '';
$sql = 'SELECT id, etiqueta, itemid, printer, estado, locked_by, created_at, printed_at FROM isabel_zpl_queue';
if ($printer !== '') {
	$sql .= " WHERE printer='" . mysqli_real_escape_string($link, $printer) . "'";
}
$sql .= ' ORDER BY id DESC LIMIT ' . $limit;
$jobs = array();
$res = mysqli_query($link, $sql);
if ($res) {
	while ($row = mysqli_fetch_assoc($res)) {
		$jobs[] = spool_row_public($row);
	}
	mysqli_free_result($res);
}

echo json_encode(array('ok' => true, 'count' => count($jobs), 'jobs' => $jobs));
