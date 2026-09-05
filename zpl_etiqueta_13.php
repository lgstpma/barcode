<?php
/**
 * ZPL etiqueta #13 — formato rotado 0.90" × 2.44" @ 203 dpi.
 * Media: ^PW183 (0.90" cabezal) × ^LL495 (2.44" avance). Texto ^AZR.
 * El visor rota 270° CW para verla como se lee (2.44" ancha × 0.90" alta).
 * No hace falta modelo de impresora: PW/LL + 8 dpmm (203 dpi) bastan.
 * Sin código de barras (como formato 21): solo descripción, Exp, Lote, precio.
 * Plantilla tipo barcode.zpl / TODAS.zpl / checker2.bat (TCP :9100).
 * items.etiqueta = 13 → exportarimg.php.
 * "Imprimir GTIN" es otro flujo (zpl_etiqueta_gtin.php).
 */
if (!function_exists('zpl_escape_field')) {
	function zpl_escape_field($s)
	{
		if ($s === null || $s === '') {
			return '';
		}
		$s = str_replace(array("\r", "\n"), ' ', (string)$s);
		return str_replace(array('^', '~', '\\'), '', $s);
	}
}

function etiqueta13_lote_code($ts)
{
	$mes = (int)date('n', $ts);
	$dia = date('d', $ts);
	switch ($mes) {
		case 1:  $mescod = 'E0'; break;
		case 2:  $mescod = 'F0'; break;
		case 3:  $mescod = 'M0'; break;
		case 4:  $mescod = 'AL'; break;
		case 5:  $mescod = 'MA'; break;
		case 6:  $mescod = 'JO'; break;
		case 7:  $mescod = 'JU'; break;
		case 8:  $mescod = 'AO'; break;
		case 9:  $mescod = 'SE'; break;
		case 10: $mescod = 'OC'; break;
		case 11: $mescod = 'NV'; break;
		case 12: $mescod = 'DI'; break;
		default: $mescod = 'XX';
	}
	return $mescod . $dia;
}

function etiqueta13_parse_fecha($fecha)
{
	$fecha = trim((string)$fecha);
	if ($fecha === '') {
		return false;
	}
	$ts = strtotime(str_replace('/', '-', $fecha));
	if ($ts === false) {
		$dt = DateTime::createFromFormat('d-m-Y', $fecha);
		if (!$dt) {
			$dt = DateTime::createFromFormat('Y-m-d', $fecha);
		}
		if (!$dt) {
			return false;
		}
		$ts = $dt->getTimestamp();
	}
	return $ts;
}

/** IP impresora Zebra (checker2.bat). Override: archivo print_ip_13.cfg con una sola línea IP. */
function etiqueta13_printer_ip()
{
	$cfg = __DIR__ . DIRECTORY_SEPARATOR . 'print_ip_13.cfg';
	if (is_file($cfg)) {
		$ip = trim((string)@file_get_contents($cfg));
		if ($ip !== '' && preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $ip)) {
			return $ip;
		}
	}
	return '192.168.10.66';
}

function etiqueta13_printer_port()
{
	return 9100;
}

/**
 * Envía ZPL por TCP (mismo método que checker2.bat).
 * @return array{ok:bool,error?:string,ip?:string,written?:int}
 */
function etiqueta13_send_ip($zpl, $ip = null, $port = null)
{
	if ($ip === null || $ip === '') {
		$ip = etiqueta13_printer_ip();
	}
	if ($port === null || (int)$port < 1) {
		$port = etiqueta13_printer_port();
	}
	$port = (int)$port;
	$errno = 0;
	$errstr = '';
	$fp = @fsockopen($ip, $port, $errno, $errstr, 5);
	if (!$fp) {
		return array('ok' => false, 'error' => 'TCP ' . $ip . ':' . $port . ' — ' . $errstr . ' (#' . $errno . ')', 'ip' => $ip);
	}
	stream_set_timeout($fp, 5);
	$n = fwrite($fp, $zpl);
	fflush($fp);
	fclose($fp);
	if ($n === false) {
		return array('ok' => false, 'error' => 'No se pudo escribir al socket', 'ip' => $ip);
	}
	return array('ok' => true, 'ip' => $ip, 'written' => (int)$n);
}

/**
 * Media original (barcode.zpl / TODAS.zpl): 203 dpi, ^PW183 = 0.90" (cabezal),
 * ^LL495 = 2.44" (avance). Texto ^AZR (90° CW) para leerla como 2.44" × 0.90".
 * No hace falta modelo de impresora: PW/LL + 8 dpmm bastan.
 *
 * @return array{d1:string,d2:string,exp:string,lote:string,precio:string,precio_num:float}
 */
function etiqueta13_text_fields($descrip, $descrip2, $precio, $expir, $fecha_manufact)
{
	$d1 = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip)));
	$d2 = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip2)));
	// La plantilla original tiene 2 líneas (FO140 / FO115). Con ^AZR el texto
	// corre en +Y; LL=495 cabe ~24 caracteres a ancho 24 (muestras: "Nueces Con Manjar 12u.").
	if ($d2 === '' && strlen($d1) > 24) {
		$cut = 24;
		$spc = strrpos(substr($d1, 0, 25), ' ');
		if ($spc !== false && $spc >= 10) {
			$cut = $spc;
		}
		$d2 = trim(substr($d1, $cut));
		$d1 = trim(substr($d1, 0, $cut));
	}
	if (strlen($d1) > 36) {
		$d1 = substr($d1, 0, 36);
	}
	if (strlen($d2) > 36) {
		$d2 = substr($d2, 0, 36);
	}

	$precioNum = (float)$precio;
	$priceTxt = ($precioNum != 0.0) ? ('B/.' . number_format($precioNum, 2, '.', '')) : '';

	$expir = (int)$expir;
	$base = etiqueta13_parse_fecha($fecha_manufact);
	if ($base === false) {
		$base = time();
	}
	$expLine = '';
	$loteLine = '';
	if ($expir !== 0) {
		$expTs = strtotime('+' . $expir . ' days', $base);
		$expLine = 'Exp.: ' . date('d/m/y', $expTs);
		$loteLine = '#Lote: ' . etiqueta13_lote_code($base);
	}

	return array(
		'd1' => $d1,
		'd2' => $d2,
		'exp' => $expLine,
		'lote' => $loteLine,
		'precio' => $priceTxt,
		'precio_num' => $precioNum,
	);
}

function etiqueta13_ttf_path()
{
	$candidates = array(
		__DIR__ . DIRECTORY_SEPARATOR . 'printserver' . DIRECTORY_SEPARATOR . 'LEXENDDECA.ttf',
		__DIR__ . DIRECTORY_SEPARATOR . 'printserver' . DIRECTORY_SEPARATOR . 'LEXENDDECA.TTF',
		__DIR__ . DIRECTORY_SEPARATOR . 'LEXENDDECA.ttf',
	);
	foreach ($candidates as $p) {
		if (is_file($p)) {
			return $p;
		}
	}
	return '';
}

/**
 * Prefijo ~DU para el visor (Labelary no tiene E:LEXENDDECA.TTF).
 * No se envía a la impresora: ahí la fuente ya está en E:.
 */
function etiqueta13_zpl_with_embedded_ttf($zpl)
{
	$ttf = etiqueta13_ttf_path();
	if ($ttf === '') {
		return '';
	}
	$bin = @file_get_contents($ttf);
	if ($bin === false || $bin === '') {
		return '';
	}
	return '~DUE:LEXENDDECA.TTF,' . strlen($bin) . ',' . strtoupper(bin2hex($bin)) . "\n" . $zpl;
}

/**
 * PNG del visor en orientación de lectura (2.44" × 0.90"), no la del avance de impresora.
 * Fallback si Labelary falla (p. ej. TTF E:LEXENDDECA.TTF).
 */
function etiqueta13_preview_png($descrip, $descrip2, $precio, $expir, $fecha_manufact)
{
	if (!function_exists('imagecreatetruecolor')) {
		return null;
	}
	$f = etiqueta13_text_fields($descrip, $descrip2, $precio, $expir, $fecha_manufact);
	$W = 495;
	$H = 183;
	$im = imagecreatetruecolor($W, $H);
	if (!$im) {
		return null;
	}
	$white = imagecolorallocate($im, 255, 255, 255);
	$black = imagecolorallocate($im, 0, 0, 0);
	imagefilledrectangle($im, 0, 0, $W - 1, $H - 1, $white);

	$font = etiqueta13_ttf_path();
	$useTtf = ($font !== '' && function_exists('imagettftext'));
	$draw = function ($foX, $foY, $hDots, $text) use ($im, $black, $font, $useTtf, $H) {
		$text = (string)$text;
		if ($text === '') {
			return;
		}
		$x = (int)$foY;
		$yTop = $H - (int)$foX;
		if ($useTtf) {
			$pt = max(6.0, ((int)$hDots) * 72.0 / 203.0);
			$baseline = max((int)$hDots - 2, min($H - 2, $yTop));
			imagettftext($im, $pt, 0, $x, $baseline, $black, $font, $text);
		} elseif (function_exists('imagestring')) {
			imagestring($im, 3, $x, max(0, $yTop - (int)$hDots), $text, $black);
		}
	};
	$draw(140, 20, 30, $f['d1']);
	$draw(115, 20, 26, $f['d2']);
	$draw(70, 20, 24, $f['exp']);
	$draw(40, 20, 24, $f['lote']);
	$draw(20, 300, 35, $f['precio']);

	ob_start();
	imagepng($im);
	$bin = ob_get_clean();
	imagedestroy($im);
	return ($bin !== false && $bin !== '') ? $bin : null;
}

/**
 * @param string $descrip  línea 1
 * @param string $descrip2 línea 2
 * @param float|string $precio
 * @param int $cant
 * @param int $expir días caducidad
 * @param string $fecha_manufact
 * Sin código de barras (igual que formato 21).
 */
function build_zpl_etiqueta_13($descrip, $descrip2, $precio, $cant, $expir, $fecha_manufact)
{
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$cantStr = sprintf('%03d', $cant);
	$f = etiqueta13_text_fields($descrip, $descrip2, $precio, $expir, $fecha_manufact);

	$zpl = "^XA\n";
	$zpl .= "^CI28\n";
	$zpl .= "^PW183\n";
	$zpl .= "^LL495\n";
	$zpl .= "^LS0\n";
	$zpl .= "^CWZ,E:LEXENDDECA.TTF\n";
	$zpl .= "^FO140,20^AZR,30,24^FD" . $f['d1'] . "^FS\n";
	$zpl .= "^FO115,20^AZR,26,22^FD" . $f['d2'] . "^FS\n";
	if ($f['exp'] !== '') {
		$zpl .= "^FO70,20^AZR,24,20^FD" . zpl_escape_field($f['exp']) . "^FS\n";
	}
	if ($f['lote'] !== '') {
		$zpl .= "^FO40,20^AZR,24,20^FD" . zpl_escape_field($f['lote']) . "^FS\n";
	}
	if ($f['precio'] !== '') {
		$zpl .= "^FO20,300^AZR,35,25^FD" . zpl_escape_field($f['precio']) . "^FS\n";
	}
	$zpl .= "^PQ" . $cantStr . "\n";
	$zpl .= "^XZ\n";
	return $zpl;
}

/**
 * Qué formatos llevan código de barras en ZPL.
 * 13 y 21 = sin barras (solo texto/precio/fecha).
 */
function etiqueta_formato_tiene_barras($tipo)
{
	$t = (string)(int)$tipo;
	if ($t === '0') {
		$t = trim((string)$tipo);
	}
	$conBarras = array('1', '5', '9', '10', '14', 'gtin');
	return in_array((string)$tipo, $conBarras, true) || in_array($t, array('1', '5', '9', '10', '14'), true);
}
