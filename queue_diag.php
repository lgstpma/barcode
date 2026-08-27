<?php
/**
 * Solo diagnóstico local: libera claims y muestra últimos jobs.
 * Borrar cuando no haga falta.
 */
include_once __DIR__ . '/print_queue_21.php';
header('Content-Type: text/plain; charset=utf-8');
$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== 'barcode21') {
	http_response_code(403);
	echo "key\n";
	exit;
}
$link = conec_mysql();
ensure_zpl_queue($link);
if (isset($_GET['unlock']) && $_GET['unlock'] === '1') {
	mysqli_query($link, "UPDATE isabel_zpl_queue SET estado=0, locked_by=NULL, locked_at=NULL WHERE estado=3");
	echo "unlocked=" . mysqli_affected_rows($link) . "\n";
}
if (isset($_GET['fail_old']) && $_GET['fail_old'] === '1') {
	mysqli_query($link, "UPDATE isabel_zpl_queue SET estado=2, printed_at=NOW() WHERE estado=0 AND id < 37");
	echo "failed_old=" . mysqli_affected_rows($link) . "\n";
}
$res = mysqli_query($link, "SELECT id, etiqueta, itemid, printer, estado, locked_by, LEFT(zpl,80) AS zplhead, created_at FROM isabel_zpl_queue ORDER BY id DESC LIMIT 20");
while ($row = mysqli_fetch_assoc($res)) {
	echo sprintf(
		"#%s etiq=%s item=%s prn=%s est=%s by=%s at=%s | %s\n",
		$row['id'],
		$row['etiqueta'],
		$row['itemid'],
		$row['printer'],
		$row['estado'],
		$row['locked_by'],
		$row['created_at'],
		str_replace(array("\r", "\n"), ' ', $row['zplhead'])
	);
}
