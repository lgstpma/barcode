<?php
/**
 * Plantilla ZPL chica — SOLO el formato que compartiste (sin VB6 / sin ^PW/^LL).
 *
 * Usada por etiqueta 1, 9 y 15.
 *
 * Márgenes / offset: print_chica_offset.cfg → ^LT / ^LS / ^LH solo en ESTE job.
 * No se guarda configuración en la impresora (sin ^JUS), así VB6 sigue igual.
 */
if (!function_exists('zpl_escape_field')) {
	function zpl_escape_field($s)
	{
		if ($s === null || $s === '') {
			return '';
		}
		$s = str_replace(array("\r", "\n"), ' ', (string)$s);
		return str_replace(array('^', '~'), '', $s);
	}
}

function zpl_chica_offset_cfg_path()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'print_chica_offset.cfg';
}

/**
 * Lee offsets de job (no permanentes en impresora).
 * @return array{lt:int,ls:int,lh_x:int,lh_y:int}
 */
function zpl_chica_load_offsets()
{
	$out = array('lt' => 10, 'ls' => 0, 'lh_x' => 0, 'lh_y' => 0);
	$path = zpl_chica_offset_cfg_path();
	if (!is_readable($path)) {
		return $out;
	}
	$lines = @file($path, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines)) {
		return $out;
	}
	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		if (strpos($line, '=') === false) {
			continue;
		}
		list($k, $v) = array_map('trim', explode('=', $line, 2));
		$k = strtolower($k);
		if ($k === 'lt' || $k === 'ls' || $k === 'lh_x' || $k === 'lh_y') {
			$out[$k] = (int)$v;
		}
	}
	foreach (array('lt', 'ls') as $k) {
		if ($out[$k] < -120) {
			$out[$k] = -120;
		}
		if ($out[$k] > 120) {
			$out[$k] = 120;
		}
	}
	foreach (array('lh_x', 'lh_y') as $k) {
		if ($out[$k] < 0) {
			$out[$k] = 0;
		}
		if ($out[$k] > 400) {
			$out[$k] = 400;
		}
	}
	return $out;
}

function zpl_chica_save_offsets($lt, $ls, $lh_x = 0, $lh_y = 0)
{
	$lt = (int)$lt;
	$ls = (int)$ls;
	$lh_x = (int)$lh_x;
	$lh_y = (int)$lh_y;
	$txt = "# Compensación SOLO jobs nuevos (1/9/15). No afecta VB6. Sin ^JUS.\n";
	$txt .= "# dots @203dpi. lt=vertical (^LT), ls=horizontal (^LS), lh=origen (^LH)\n";
	$txt .= "lt=$lt\nls=$ls\nlh_x=$lh_x\nlh_y=$lh_y\n";
	return @file_put_contents(zpl_chica_offset_cfg_path(), $txt) !== false;
}

function zpl_chica_lote_code($ts)
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

function zpl_chica_parse_fecha($fecha)
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

/**
 * @param bool $con_fecha  true = lote/exp/reg (tipos 1 y 15); false = solo nombre+precio+barras (tipo 9)
 * @param bool $con_reg    incluir línea Reg. si hay valor
 */
function build_zpl_chica_plantilla($codigo, $descrip, $descrip2, $precio, $cant, $expir, $fecha_manufact, $reg_sanitario = '', $con_fecha = true, $con_reg = true)
{
	$codigo = str_pad(preg_replace('/\D/', '', (string)$codigo), 6, '0', STR_PAD_LEFT);
	$codigo = substr($codigo, -6);

	$descrip = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip)));
	$descrip2 = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip2)));

	$precio = (float)$precio;
	$priceTxt = number_format($precio, 2, '.', '');

	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}

	$off = zpl_chica_load_offsets();
	$lt = (int)$off['lt'];
	$ls = (int)$off['ls'];
	$lhx = (int)$off['lh_x'];
	$lhy = (int)$off['lh_y'];

	// Offsets solo en este formato (^XA…^XZ). No ^JUS → no cambia config permanente.
	$zpl = "^XA\n";
	$zpl .= "^FX chica-plantilla job-offset lt=$lt ls=$ls (VB6 intacto)\n";
	$zpl .= "^LH" . $lhx . "," . $lhy . "\n";
	$zpl .= "^LS" . $ls . "\n";
	$zpl .= "^AD,54\n";
	$zpl .= "^CFA,12\n";
	$zpl .= "^LT" . $lt . "\n";
	$zpl .= "^CWZ,E:LEXENDDECA.TTF\n";
	$zpl .= "^FO180,45\n";
	$zpl .= "^BY1\n";
	$zpl .= "^BCN,40,N,N,N\n";
	$zpl .= "^FD" . $codigo . "^FS\n";
	$zpl .= "^CFA,10\n";
	$zpl .= "^CFA,14\n";
	$zpl .= "^FO20,20^FDPrecio:" . zpl_escape_field($priceTxt) . "^FS\n";
	$zpl .= "^CFA,14\n";
	$zpl .= "^FO20,0^FD" . $descrip . "^FS\n";
	if ($descrip2 !== '') {
		$zpl .= "^FO20,12^FD" . $descrip2 . "^FS\n";
	} else {
		$zpl .= "^FO20,20^FD^FS\n";
	}

	if ($con_fecha) {
		$base = zpl_chica_parse_fecha($fecha_manufact);
		if ($base === false) {
			$base = time();
		}
		$lote = zpl_chica_lote_code($base);
		$expir = (int)$expir;
		$expTxt = '';
		if ($expir > 0) {
			$expTxt = date('d/m/y', strtotime('+' . $expir . ' days', $base));
		}

		$zpl .= "^CFA,14\n";
		$zpl .= "^FO20,40^FD#Lote:" . zpl_escape_field($lote) . "^FS\n";
		$zpl .= "^CFA,14\n";
		if ($expTxt !== '') {
			$zpl .= "^FO20,58^FDExp.:" . zpl_escape_field($expTxt) . "^FS\n";
		}

		if ($con_reg) {
			$reg = zpl_escape_field(trim((string)$reg_sanitario));
			if ($reg !== '') {
				$regLine = (stripos($reg, 'Reg.') === 0) ? $reg : ('Reg.:' . $reg);
				$zpl .= "^CFA,14\n";
				$zpl .= "^FO20,75^FD" . $regLine . "^FS\n";
			}
		}
	}

	$zpl .= "^PQ" . $cant . "\n";
	$zpl .= "^XZ\n";
	return $zpl;
}
