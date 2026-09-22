<?php
/**
 * Estado / arranque / paro del worker ZPL (print_service_21).
 * Local: usa los mismos .bat que start.bat.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'print_service_21';
$startBat = $dir . DIRECTORY_SEPARATOR . 'arrancar_worker.bat';
$stopBat = $dir . DIRECTORY_SEPARATOR . 'stop_worker.bat';

function worker_running()
{
	$out = array();
	@exec('tasklist /V /FO CSV 2>NUL', $out);
	$blob = strtolower(implode("\n", $out));
	if (strpos($blob, 'print_etiqueta21') !== false) {
		return true;
	}
	$out2 = array();
	@exec('wmic process get CommandLine 2>NUL', $out2);
	$blob2 = strtolower(implode("\n", $out2));
	return (strpos($blob2, 'print_etiqueta21') !== false);
}

$action = isset($_REQUEST['action']) ? strtolower(trim((string)$_REQUEST['action'])) : 'status';
$ok = true;
$msg = '';

if ($action === 'start') {
	if (!is_file($startBat)) {
		$ok = false;
		$msg = 'Falta print_service_21/arrancar_worker.bat';
	} else {
		pclose(popen('start "BARCODE-worker" /MIN cmd /c "' . $startBat . '"', 'r'));
		usleep(900000);
		$msg = 'Worker iniciado';
	}
} elseif ($action === 'stop') {
	if (!is_file($stopBat)) {
		$ok = false;
		$msg = 'Falta print_service_21/stop_worker.bat';
	} else {
		@exec('cmd /c "' . $stopBat . '"');
		$msg = 'Worker detenido';
	}
} elseif ($action !== 'status') {
	$ok = false;
	$msg = 'Accion no valida';
}

$running = worker_running();
echo json_encode(array(
	'ok' => $ok,
	'running' => $running,
	'action' => $action,
	'message' => $msg !== '' ? $msg : ($running ? 'Worker activo' : 'Worker detenido'),
));
