<?php
/**
 * ZPL etiqueta #13 — formato rotado 0.90" × 2.44" @ 203 dpi.
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

	$d1 = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip)));
	$d2 = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip2)));
	if (strlen($d1) > 36) {
		$d1 = substr($d1, 0, 36);
	}
	if (strlen($d2) > 36) {
		$d2 = substr($d2, 0, 36);
	}

	$precio = (float)$precio;
	$priceTxt = 'B/.' . number_format($precio, 2, '.', '');

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

	$zpl = "^XA\n";
	$zpl .= "^CI28\n";
	$zpl .= "^PW183\n";
	$zpl .= "^LL495\n";
	$zpl .= "^LS0\n";
	$zpl .= "^CWZ,E:LEXENDDECA.TTF\n";
	$zpl .= "^FO140,20^AZR,30,24^FD" . $d1 . "^FS\n";
	$zpl .= "^FO115,20^AZR,26,22^FD" . $d2 . "^FS\n";
	if ($expLine !== '') {
		$zpl .= "^FO70,20^AZR,24,20^FD" . zpl_escape_field($expLine) . "^FS\n";
	}
	if ($loteLine !== '') {
		$zpl .= "^FO40,20^AZR,24,20^FD" . zpl_escape_field($loteLine) . "^FS\n";
	}
	if ($precio != 0.0) {
		$zpl .= "^FO20,300^AZR,35,25^FD" . zpl_escape_field($priceTxt) . "^FS\n";
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
