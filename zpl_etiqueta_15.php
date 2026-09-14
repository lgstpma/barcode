<?php
/**
 * ZPL etiqueta #15 — chica SoftShop con lote + Reg. (GK420t_chica).
 * Plantilla aportada (funciona en impresora chica):
 *
 *   ^XA ^AD,54 ^CFA,12 ^LT10 ^CWZ,E:LEXENDDECA.TTF
 *   ^FO180,45 ^BY1 ^BCN,40,N,N,N ^FD{codigo}^FS
 *   ^FO20,20^FDPrecio:{precio}^FS
 *   ^FO20,0^FD{nombre}^FS
 *   ^FO20,40^FD#Lote:{lote}^FS
 *   ^FO20,58^FDExp.:{exp}^FS
 *   ^FO20,75^FDReg.:{reg}^FS
 *   ^PQ{cant} ^XZ
 *
 * Sin ^PW/^LL: usa el tamaño de media de la impresora chica.
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

function etiqueta15_lote_code($ts)
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

function etiqueta15_parse_fecha($fecha)
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
 * @param string $codigo itemid (barras)
 * @param string $descrip nombre línea 1
 * @param string $descrip2 nombre línea 2 (opcional; si vacío no se imprime)
 * @param float|string $precio
 * @param int $cant
 * @param int $expir días caducidad
 * @param string $fecha_manufact
 * @param string $reg_sanitario ej. N004186
 */
function build_zpl_etiqueta_15($codigo, $descrip, $descrip2, $precio, $cant, $expir, $fecha_manufact, $reg_sanitario = '')
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
	$cantStr = sprintf('%03d', $cant);

	$base = etiqueta15_parse_fecha($fecha_manufact);
	if ($base === false) {
		$base = time();
	}
	$lote = etiqueta15_lote_code($base);

	$expir = (int)$expir;
	$expTxt = '';
	if ($expir > 0) {
		$expTxt = date('d/m/y', strtotime('+' . $expir . ' days', $base));
	}

	$reg = zpl_escape_field(trim((string)$reg_sanitario));
	if ($reg !== '' && stripos($reg, 'Reg.') !== 0) {
		$regLine = 'Reg.:' . $reg;
	} elseif ($reg !== '') {
		$regLine = $reg;
	} else {
		$regLine = '';
	}

	$zpl = "^XA\n";
	$zpl .= "^AD,54\n";
	$zpl .= "^CFA,12\n";
	$zpl .= "^LT10\n";
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
	// 2ª línea de nombre (en la muestra venía vacía en Y=20; si hay texto va un poco más abajo del título)
	if ($descrip2 !== '') {
		$zpl .= "^FO20,12^FD" . $descrip2 . "^FS\n";
	}
	$zpl .= "^CFA,14\n";
	$zpl .= "^FO20,40^FD#Lote:" . zpl_escape_field($lote) . "^FS\n";
	$zpl .= "^CFA,14\n";
	if ($expTxt !== '') {
		$zpl .= "^FO20,58^FDExp.:" . zpl_escape_field($expTxt) . "^FS\n";
	}
	$zpl .= "^CFA,14\n";
	if ($regLine !== '') {
		$zpl .= "^FO20,75^FD" . $regLine . "^FS\n";
	}
	$zpl .= "^PQ" . $cantStr . "\n";
	$zpl .= "^XZ\n";
	return $zpl;
}
