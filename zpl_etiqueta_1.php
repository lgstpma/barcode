<?php
/**
 * ZPL etiqueta 1 / 9 — misma plantilla que funcionó en la chica (la que compartiste).
 *
 * Antes se recreaba SoftShop VB con ^PW203^LL102 + fuentes ^A0 escaladas;
 * si la media de la impresora no coincidía, salía apiñada/aplastada.
 *
 * Tu ZPL bueno NO fuerza ^PW/^LL: usa el tamaño calibrado de la impresora
 * y fuentes ^CFA / ^AD:
 *
 *   ^XA ^AD,54 ^CFA,12 ^LT10 ^CWZ,E:LEXENDDECA.TTF
 *   ^FO180,45 ^BY1 ^BCN,40 ^FD{codigo}
 *   ^FO20,20 Precio:{precio}
 *   ^FO20,0  {nombre}
 *   ^FO20,40 #Lote:{lote}
 *   ^FO20,58 Exp.:{exp}
 *   (^FO20,75 Reg.:… solo si hay reg_sanitario — vía build_zpl_etiqueta_15)
 *
 * Tipo 1 = con lote/exp (y Reg. si se pasa).
 * Tipo 9 = sin fechas (solo nombre + precio + barras).
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

function etiqueta1_lote_code($ts)
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

function etiqueta1_parse_fecha($fecha)
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
 * Plantilla chica que funciona (sin ^PW/^LL).
 *
 * @param string $descrip2 opcional 2ª línea
 * @param string $reg_sanitario opcional
 * @param bool $con_fecha incluir lote + exp (+ reg si hay)
 * @param array|null $layoutOverride ignorado (compat API); la geometría es la plantilla fija
 */
function build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, $expir, $fecha_manufact, $con_fecha = true, $layoutOverride = null, $descrip2 = '', $reg_sanitario = '')
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
	if ($precio != 0.0) {
		$zpl .= "^FO20,20^FDPrecio:" . zpl_escape_field($priceTxt) . "^FS\n";
	}
	$zpl .= "^CFA,14\n";
	$zpl .= "^FO20,0^FD" . $descrip . "^FS\n";
	if ($descrip2 !== '') {
		$zpl .= "^FO20,12^FD" . $descrip2 . "^FS\n";
	}

	if ($con_fecha) {
		$base = etiqueta1_parse_fecha($fecha_manufact);
		if ($base === false) {
			$base = time();
		}
		$lote = etiqueta1_lote_code($base);
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

		$reg = zpl_escape_field(trim((string)$reg_sanitario));
		if ($reg !== '') {
			$regLine = (stripos($reg, 'Reg.') === 0) ? $reg : ('Reg.:' . $reg);
			$zpl .= "^CFA,14\n";
			$zpl .= "^FO20,75^FD" . $regLine . "^FS\n";
		}
	}

	$zpl .= "^PQ" . $cantStr . "\n";
	$zpl .= "^XZ\n";
	return $zpl;
}

function build_zpl_etiqueta_9($codigo, $descrip, $precio, $cant, $layoutOverride = null, $descrip2 = '')
{
	return build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, 0, '', false, $layoutOverride, $descrip2, '');
}
