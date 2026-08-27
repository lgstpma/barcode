<?php
/**
 * Cola ZPL compartida (isabel_zpl_queue).
 * Alineado a VB6 Label_Printserver_ver2:
 *   10 = BMP + itemid | 13 = GTIN codigo2 | 14 = BMP + itemid2
 *   1 / 9 / 21 = chica SofyShop
 * Servicio unificado print_service_21 atiende todos.
 *
 * estados: 0=pendiente 1=impreso 2=error 3=tomado (claim)
 */
include_once("conections.php");

$QUEUE_KEY = "barcode21";

function ensure_zpl_queue($link)
{
	$sql = "CREATE TABLE IF NOT EXISTS `isabel_zpl_queue` (
		`id` INT NOT NULL AUTO_INCREMENT,
		`etiqueta` VARCHAR(10) NOT NULL,
		`itemid` VARCHAR(32) DEFAULT NULL,
		`printer` VARCHAR(128) DEFAULT NULL,
		`zpl` MEDIUMTEXT NOT NULL,
		`estado` TINYINT NOT NULL DEFAULT 0,
		`locked_by` VARCHAR(64) DEFAULT NULL,
		`locked_at` DATETIME DEFAULT NULL,
		`created_at` DATETIME NOT NULL,
		`printed_at` DATETIME DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `idx_estado_etiq` (`estado`,`etiqueta`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
	$ok = (bool)mysqli_query($link, $sql);
	$cols = mysqli_query($link, "SHOW COLUMNS FROM isabel_zpl_queue LIKE 'printer'");
	if ($cols && mysqli_num_rows($cols) === 0) {
		mysqli_query($link, "ALTER TABLE isabel_zpl_queue ADD COLUMN printer VARCHAR(128) DEFAULT NULL AFTER itemid");
	}
	if ($cols) {
		mysqli_free_result($cols);
	}
	$cols2 = mysqli_query($link, "SHOW COLUMNS FROM isabel_zpl_queue LIKE 'locked_by'");
	if ($cols2 && mysqli_num_rows($cols2) === 0) {
		mysqli_query($link, "ALTER TABLE isabel_zpl_queue ADD COLUMN locked_by VARCHAR(64) DEFAULT NULL AFTER estado");
		mysqli_query($link, "ALTER TABLE isabel_zpl_queue ADD COLUMN locked_at DATETIME DEFAULT NULL AFTER locked_by");
	}
	if ($cols2) {
		mysqli_free_result($cols2);
	}
	return $ok;
}

function lookup_item_printer($link, $itemid)
{
	$printer = '';
	$code = mysqli_real_escape_string($link, (string)$itemid);
	$res = mysqli_query($link, "SELECT printer FROM lbls WHERE id = '" . $code . "' LIMIT 1");
	if ($res) {
		$row = mysqli_fetch_assoc($res);
		if ($row && isset($row['printer'])) {
			$printer = trim((string)$row['printer']);
		}
		mysqli_free_result($res);
	}
	return $printer;
}

function enqueue_zpl($link, $etiqueta, $itemid, $zpl, $printer = '')
{
	ensure_zpl_queue($link);
	$etiqueta = (string)$etiqueta;
	// SoftShop / chica: siempre GK420t_chica; #5 mediana → GK420t_grande
	// #13 va por IP (printer = IP:x.x.x.x); gtin = chica SoftShop
	if ($etiqueta === '1' || $etiqueta === '9' || $etiqueta === '21' || $etiqueta === 'gtin') {
		$printer = 'GK420t_chica';
	} elseif ($etiqueta === '5') {
		$printer = 'GK420t_grande';
	} elseif ($etiqueta === '13') {
		if ($printer === '' || $printer === null) {
			$printer = 'IP';
		}
	} elseif ($printer === '' || $printer === null) {
		$printer = lookup_item_printer($link, $itemid);
	}
	$st = mysqli_prepare($link, "INSERT INTO isabel_zpl_queue (etiqueta, itemid, printer, zpl, estado, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
	if (!$st) {
		return false;
	}
	mysqli_stmt_bind_param($st, "ssss", $etiqueta, $itemid, $printer, $zpl);
	$ok = mysqli_stmt_execute($st);
	$id = $ok ? mysqli_insert_id($link) : 0;
	mysqli_stmt_close($st);
	return $ok ? $id : false;
}

function enqueue_zpl_21($link, $itemid, $zpl, $printer = '')
{
	return enqueue_zpl($link, '21', $itemid, $zpl, $printer);
}

function enqueue_zpl_1($link, $itemid, $zpl, $printer = '')
{
	return enqueue_zpl($link, '1', $itemid, $zpl, 'GK420t_chica');
}

function enqueue_zpl_13($link, $itemid, $zpl, $printer = '')
{
	return enqueue_zpl($link, '13', $itemid, $zpl, $printer);
}

function enqueue_zpl_gtin($link, $itemid, $zpl)
{
	return enqueue_zpl($link, 'gtin', $itemid, $zpl, 'GK420t_chica');
}

/** Etiquetas ZPL atendidas por el servicio unificado */
function zpl_queue_service_etiquetas_21()
{
	return array('21', 'gtin', '10', '14', '1', '9', '5');
}

function zpl_queue_sql_in_etiquetas($list)
{
	$parts = array();
	foreach ($list as $e) {
		$parts[] = "'" . str_replace("'", '', (string)$e) . "'";
	}
	return implode(',', $parts);
}

/** Libera claims viejos (servicio caído) para que otro worker los tome. */
function zpl_queue_release_stale($link, $minutes = 5)
{
	$m = (int)$minutes;
	if ($m < 1) {
		$m = 5;
	}
	mysqli_query($link, "UPDATE isabel_zpl_queue SET estado=0, locked_by=NULL, locked_at=NULL WHERE estado=3 AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL $m MINUTE))");
}

/**
 * Toma jobs pendientes de forma atómica (estado 0 → 3).
 * Varios workers / usuarios no reciben el mismo id.
 *
 * @param string $printersCsv lista opcional de impresoras que este worker acepta
 */
function zpl_queue_claim($link, $etiqIn, $worker, $limit = 20, $printerFilter = '', $printersCsv = '')
{
	ensure_zpl_queue($link);
	zpl_queue_release_stale($link, 5);
	$worker = substr(preg_replace('/[^\w.\-:@]/', '', (string)$worker), 0, 64);
	if ($worker === '') {
		$worker = 'worker';
	}
	$limit = (int)$limit;
	if ($limit < 1) {
		$limit = 1;
	}
	if ($limit > 50) {
		$limit = 50;
	}
	$wEsc = mysqli_real_escape_string($link, $worker);

	$where = "estado=0 AND etiqueta IN ($etiqIn)";
	if ($printerFilter !== '') {
		$where .= " AND printer='" . mysqli_real_escape_string($link, $printerFilter) . "'";
	} elseif ($printersCsv !== '') {
		$names = array();
		foreach (explode(',', $printersCsv) as $p) {
			$p = trim($p);
			if ($p !== '') {
				$names[] = "'" . mysqli_real_escape_string($link, $p) . "'";
			}
		}
		if (count($names) > 0) {
			$where .= " AND (printer IN (" . implode(',', $names) . ") OR printer IS NULL OR printer='')";
		}
	}

	$sql = "UPDATE isabel_zpl_queue SET estado=3, locked_by='$wEsc', locked_at=NOW() WHERE $where ORDER BY id ASC LIMIT $limit";
	mysqli_query($link, $sql);

	$jobs = array();
	$res = mysqli_query($link, "SELECT id, itemid, etiqueta, printer, zpl, created_at FROM isabel_zpl_queue WHERE estado=3 AND locked_by='$wEsc' ORDER BY id ASC");
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$jobs[] = $row;
		}
		mysqli_free_result($res);
	}
	return $jobs;
}

function zpl_queue_release_job($link, $id, $worker = '')
{
	$id = (int)$id;
	if ($id <= 0) {
		return 0;
	}
	$sql = "UPDATE isabel_zpl_queue SET estado=0, locked_by=NULL, locked_at=NULL WHERE id=$id AND estado=3";
	if ($worker !== '') {
		$w = substr(preg_replace('/[^\w.\-:@]/', '', (string)$worker), 0, 64);
		$sql .= " AND locked_by='" . mysqli_real_escape_string($link, $w) . "'";
	}
	mysqli_query($link, $sql);
	return mysqli_affected_rows($link);
}

$runningAsApi = (basename($_SERVER['SCRIPT_FILENAME']) === 'print_queue_21.php');
if (!$runningAsApi) {
	return;
}

$link = conec_mysql();
$key = isset($_GET['key']) ? (string)$_GET['key'] : '';
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

header('Content-Type: application/json; charset=utf-8');
if ($key !== $QUEUE_KEY) {
	http_response_code(403);
	echo json_encode(array('ok' => false, 'error' => 'key'));
	exit;
}

ensure_zpl_queue($link);

$etiqIn = zpl_queue_sql_in_etiquetas(zpl_queue_service_etiquetas_21());

if ($action === 'pending') {
	$worker = isset($_GET['worker']) ? (string)$_GET['worker'] : 'legacy';
	$jobs = zpl_queue_claim($link, $etiqIn, $worker, 20, '', '');
	echo json_encode(array('ok' => true, 'jobs' => $jobs));
	exit;
}

if ($action === 'done') {
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if ($id <= 0) {
		echo json_encode(array('ok' => false, 'error' => 'id'));
		exit;
	}
	$st = mysqli_prepare($link, "UPDATE isabel_zpl_queue SET estado=1, printed_at=NOW(), locked_by=NULL, locked_at=NULL WHERE id=? AND etiqueta IN ($etiqIn) AND estado IN (0,3)");
	mysqli_stmt_bind_param($st, "i", $id);
	mysqli_stmt_execute($st);
	$n = mysqli_stmt_affected_rows($st);
	mysqli_stmt_close($st);
	echo json_encode(array('ok' => true, 'updated' => $n));
	exit;
}

echo json_encode(array('ok' => false, 'error' => 'action'));
