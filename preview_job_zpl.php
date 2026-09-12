<?php
/**
 * Vista previa PNG del ZPL de un job (cola) o ZPL en POST.
 * GET  preview_job_zpl.php?key=barcode21&id=128
 * POST preview_job_zpl.php?key=barcode21  body: zpl=...
 */
@ini_set('memory_limit', '256M');
@set_time_limit(60);

$key = isset($_REQUEST['key']) ? (string)$_REQUEST['key'] : '';
if ($key !== 'barcode21') {
	http_response_code(403);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'key';
	exit;
}

$zpl = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php';
	$link = conec_mysql();
	ensure_zpl_queue($link);
	$idEsc = (int)$id;
	$res = mysqli_query($link, "SELECT zpl FROM isabel_zpl_queue WHERE id=$idEsc LIMIT 1");
	$row = $res ? mysqli_fetch_assoc($res) : null;
	if ($row) {
		$zpl = (string)$row['zpl'];
	}
} elseif (isset($_POST['zpl'])) {
	$zpl = (string)$_POST['zpl'];
}

$zpl = trim($zpl);
if ($zpl === '' || strpos($zpl, '^XA') === false) {
	http_response_code(400);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'sin zpl';
	exit;
}

if (preg_match('/\^PQ\d+/', $zpl)) {
	$zpl = preg_replace('/\^PQ\d+/', '^PQ1', $zpl);
}

$pw = 203;
$ll = 102;
if (preg_match('/\^PW(\d+)/', $zpl, $m)) {
	$pw = max(1, (int)$m[1]);
}
if (preg_match('/\^LL(\d+)/', $zpl, $m)) {
	$ll = max(1, (int)$m[1]);
}
$wIn = round($pw / 203.0, 3);
$hIn = round($ll / 203.0, 3);
if ($wIn < 0.2) {
	$wIn = 0.2;
}
if ($hIn < 0.2) {
	$hIn = 0.2;
}

$zplRender = preg_replace('/\^CWZ,[^\r\n^]*/', '', $zpl);
$zplRender = preg_replace('/\^AZ([NRIB])/', '^A0$1', $zplRender);

$url = 'http://api.labelary.com/v1/printers/8dpmm/labels/' . $wIn . 'x' . $hIn . '/0/';
$png = null;
if (function_exists('curl_init')) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $zplRender);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: image/png', 'Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
	curl_setopt($ch, CURLOPT_TIMEOUT, 25);
	$png = curl_exec($ch);
	$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($code < 200 || $code >= 300 || $png === false || strlen($png) < 50) {
		$png = null;
	}
} else {
	$ctx = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => "Accept: image/png\r\nContent-Type: application/x-www-form-urlencoded\r\n",
			'content' => $zplRender,
			'timeout' => 25,
		),
	));
	$png = @file_get_contents($url, false, $ctx);
	if ($png === false || strlen($png) < 50) {
		$png = null;
	}
}

if ($png === null) {
	http_response_code(502);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'labelary fallo';
	exit;
}

header('Content-Type: image/png');
header('Cache-Control: no-store');
echo $png;
