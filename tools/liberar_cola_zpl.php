<?php
/**
 * Libera jobs atascados en estado 3 (enviando) y muestra cola reciente.
 * Uso: http://127.0.0.1:8080/tools/liberar_cola_zpl.php?key=barcode21
 *      &do=1  para liberar (estado 3 → 0)
 */
$root = dirname(__DIR__);
include_once $root . DIRECTORY_SEPARATOR . 'print_queue_21.php';

$key = isset($_GET['key']) ? (string)$_GET['key'] : '';
if ($key !== 'barcode21') {
	header('Content-Type: text/plain; charset=utf-8');
	http_response_code(403);
	echo "key\n";
	exit;
}

header('Content-Type: text/plain; charset=utf-8');
$link = conec_mysql();
ensure_zpl_queue($link);

$do = isset($_GET['do']) && (string)$_GET['do'] === '1';
if ($do) {
	mysqli_query($link, "UPDATE isabel_zpl_queue SET estado=0, locked_by=NULL, locked_at=NULL WHERE estado=3");
	$n = mysqli_affected_rows($link);
	echo "Liberados: $n job(s) de estado 3 → pendiente.\n\n";
} else {
	echo "Modo consulta. Agregue &do=1 para liberar todos los estado=3.\n\n";
}

$res = mysqli_query($link, "SELECT id, etiqueta, itemid, printer, estado, locked_by, locked_at, created_at, printed_at FROM isabel_zpl_queue ORDER BY id DESC LIMIT 15");
echo "Ultimos 15 jobs:\n";
echo str_pad('id', 6) . str_pad('est', 5) . str_pad('etiq', 6) . str_pad('item', 12) . str_pad('printer', 18) . "locked_by\n";
if ($res) {
	while ($r = mysqli_fetch_assoc($res)) {
		echo str_pad($r['id'], 6)
			. str_pad($r['estado'], 5)
			. str_pad($r['etiqueta'], 6)
			. str_pad(substr($r['itemid'], 0, 10), 12)
			. str_pad(substr($r['printer'], 0, 16), 18)
			. $r['locked_by'] . "\n";
	}
}
echo "\nEstados: 0=pendiente 1=impreso 2=error 3=enviando(claim)\n";
