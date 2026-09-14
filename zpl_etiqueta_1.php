<?php
/**
 * ZPL etiqueta 1 / 9 — SofyShop chica (mismo rollo / orientación que se ve en VB6).
 *
 * La etiqueta física se LEE en vertical (como la #13): texto a lo largo,
 * código de barras al costado. En impresora:
 *   ^PW183 (0.90" cabezal) × ^LL495 (2.44" avance) + campos ^A0R / ^BCR.
 *
 * El formulario VB (lbl1) diseña en twips paisaje ~2160×720; aquí se mapea
 * a ese lienzo rotado para que coincida con lo que imprime Label_Printserver.
 *
 * Antes se usaba ^PW203×^LL102 sin rotar → salía achatado / mal orientado.
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

function etiqueta1_mb_len($s)
{
	return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function etiqueta1_mb_sub($s, $start, $len = null)
{
	if (function_exists('mb_substr')) {
		return $len === null ? mb_substr($s, $start, null, 'UTF-8') : mb_substr($s, $start, $len, 'UTF-8');
	}
	return $len === null ? substr($s, $start) : substr($s, $start, $len);
}

function etiqueta1_corte_renglon($cadena, $maxrenglon)
{
	$n = etiqueta1_mb_len($cadena);
	if ($maxrenglon < 1) {
		return 0;
	}
	if ($maxrenglon > $n) {
		$maxrenglon = $n;
	}
	$char_cont = 0;
	$tempstring = etiqueta1_mb_sub($cadena, $maxrenglon - 1, 1);
	while ($tempstring !== ' ' && $char_cont < $maxrenglon) {
		$char_cont++;
		$pos = $maxrenglon - $char_cont;
		if ($pos < 1) {
			break;
		}
		$tempstring = etiqueta1_mb_sub($cadena, $pos - 1, 1);
	}
	return $char_cont;
}

function etiqueta1_wrap_descrip($descripcion, $alcance)
{
	$descripcion = trim(preg_replace('/\s+/', ' ', (string)$descripcion));
	$len = etiqueta1_mb_len($descripcion);
	if ($len <= $alcance) {
		return array($descripcion, '');
	}
	$chunk = etiqueta1_mb_sub($descripcion, 0, $alcance);
	$space = function_exists('mb_strrpos') ? mb_strrpos($chunk, ' ', 0, 'UTF-8') : strrpos($chunk, ' ');
	if ($space === false || $space < 1) {
		$space = $alcance;
	}
	$line1 = trim(etiqueta1_mb_sub($descripcion, 0, $space));
	$line2 = trim(etiqueta1_mb_sub($descripcion, $space));
	$len2 = etiqueta1_mb_len($line2);
	if ($len2 > $alcance) {
		$chunk2 = etiqueta1_mb_sub($line2, 0, $alcance);
		$sp2 = function_exists('mb_strrpos') ? mb_strrpos($chunk2, ' ', 0, 'UTF-8') : strrpos($chunk2, ' ');
		if ($sp2 === false || $sp2 < 1) {
			$sp2 = $alcance;
		}
		$line2 = trim(etiqueta1_mb_sub($line2, 0, $sp2));
	}
	return array($line1, $line2);
}

/** VB X (twips paisaje) → Y de impresora (avance, dots). */
function etiqueta1_map_y($twipsX)
{
	return (int)round(((float)$twipsX) * 495.0 / 2160.0);
}

/** VB Y (twips paisaje) → X de impresora (cabezal, dots). */
function etiqueta1_map_x($twipsY)
{
	return (int)round(((float)$twipsY) * 183.0 / 720.0);
}

function etiqueta1_map_font($pt)
{
	// Un poco más grande que 203dpi*pt/72 para llenar la chica rotada.
	$h = (int)round(((float)$pt) * 203.0 / 72.0 * 1.15);
	return $h < 12 ? 12 : $h;
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

function build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, $expir, $fecha_manufact, $con_fecha = true, $layoutOverride = null)
{
	// Defaults formulario VB (pestaña lbl1), twips
	$vb_bc_x = 150;
	$vb_bc_y = 10;
	$vb_bc_w = 3500;
	$vb_bc_h = 1000;
	$vb_code_x = 1700;
	$vb_code_y = 30;
	$vb_code_pt = 5;
	$vb_desc_x = 120;
	$vb_desc_y = 325;
	$vb_desc_pt = 6;
	$vb_desc_alcance = 24;
	$vb_desc_renglon = 117;
	$vb_exp_x = 150;
	$vb_exp_y = 590;
	$vb_exp_pt = 5;
	$vb_price_x = 1495;
	$vb_price_y = 137;
	$vb_price_pt = 9;

	if (is_array($layoutOverride) && !empty($layoutOverride['fields']) && is_array($layoutOverride['fields'])) {
		$f = $layoutOverride['fields'];
		if (!empty($f['barcode'])) {
			if (isset($f['barcode']['x'])) $vb_bc_x = (int)$f['barcode']['x'];
			if (isset($f['barcode']['y'])) $vb_bc_y = (int)$f['barcode']['y'];
			if (isset($f['barcode']['w'])) $vb_bc_w = (int)$f['barcode']['w'];
			if (isset($f['barcode']['h'])) $vb_bc_h = (int)$f['barcode']['h'];
		}
		if (!empty($f['itemid'])) {
			if (isset($f['itemid']['x'])) $vb_code_x = (int)$f['itemid']['x'];
			if (isset($f['itemid']['y'])) $vb_code_y = (int)$f['itemid']['y'];
			if (isset($f['itemid']['font'])) $vb_code_pt = (float)$f['itemid']['font'];
		}
		if (!empty($f['precio'])) {
			if (isset($f['precio']['x'])) $vb_price_x = (int)$f['precio']['x'];
			if (isset($f['precio']['y'])) $vb_price_y = (int)$f['precio']['y'];
			if (isset($f['precio']['font'])) $vb_price_pt = (float)$f['precio']['font'];
		}
		if (!empty($f['descripcion'])) {
			if (isset($f['descripcion']['x'])) $vb_desc_x = (int)$f['descripcion']['x'];
			if (isset($f['descripcion']['y'])) $vb_desc_y = (int)$f['descripcion']['y'];
			if (isset($f['descripcion']['font'])) $vb_desc_pt = (float)$f['descripcion']['font'];
			if (isset($f['descripcion']['alcance'])) $vb_desc_alcance = (int)$f['descripcion']['alcance'];
			if (isset($f['descripcion']['renglon'])) $vb_desc_renglon = (int)$f['descripcion']['renglon'];
		}
		if (!empty($f['fecha'])) {
			if (isset($f['fecha']['x'])) $vb_exp_x = (int)$f['fecha']['x'];
			if (isset($f['fecha']['y'])) $vb_exp_y = (int)$f['fecha']['y'];
			if (isset($f['fecha']['font'])) $vb_exp_pt = (float)$f['fecha']['font'];
		}
	}

	// Media física chica (misma familia que formato 13 / VB6 en foto)
	$pw = 183;
	$ll = 495;

	$codigo = str_pad(preg_replace('/\D/', '', (string)$codigo), 6, '0', STR_PAD_LEFT);
	$codigo = substr($codigo, -6);
	$code_txt = '[' . $codigo . ']';

	// Mapa rotado 90° CW: VB(x,y) → ZPL(x'=y, y'=x)
	$x_bc = etiqueta1_map_x($vb_bc_y);
	$y_bc = etiqueta1_map_y($vb_bc_x);
	$bc_h = etiqueta1_map_x($vb_bc_h); // alto de barras en eje cabezal
	if ($bc_h < 40) {
		$bc_h = 55;
	}
	if ($bc_h > $pw - 8) {
		$bc_h = $pw - 8;
	}
	$bc_mod = 2;

	$x_code = etiqueta1_map_x($vb_code_y);
	$y_code = etiqueta1_map_y($vb_code_x);
	$h_code = etiqueta1_map_font($vb_code_pt);

	$x_price = etiqueta1_map_x($vb_price_y);
	$y_price = etiqueta1_map_y($vb_price_x);
	$h_price = etiqueta1_map_font($vb_price_pt);

	$x_desc = etiqueta1_map_x($vb_desc_y);
	$y_desc = etiqueta1_map_y($vb_desc_x);
	$h_desc = etiqueta1_map_font($vb_desc_pt);
	$dy_desc = etiqueta1_map_x($vb_desc_renglon);
	if ($dy_desc < $h_desc + 2) {
		$dy_desc = $h_desc + 4;
	}

	$x_exp = etiqueta1_map_x($vb_exp_y);
	$y_exp = etiqueta1_map_y($vb_exp_x);
	$h_exp = etiqueta1_map_font($vb_exp_pt);

	$descrip_esc = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip)));
	$lines = etiqueta1_wrap_descrip($descrip_esc, $vb_desc_alcance);

	$precio = (float)$precio;
	$expir = (int)$expir;
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}

	$zpl = "^XA\n^CI28\n^PON\n^FWR\n";
	$zpl .= "^PW" . $pw . "\n^LL" . $ll . "\n^LS0\n^LH0,0\n";

	// Código de barras (rotado, a lo largo de la etiqueta)
	$zpl .= "^FO" . $x_bc . "," . $y_bc . "^BY" . $bc_mod . ",2," . $bc_h . "\n";
	$zpl .= "^BCR," . $bc_h . ",N,N,N\n^FD" . $codigo . "^FS\n";

	$zpl .= "^FO" . $x_code . "," . $y_code . "^A0R," . $h_code . "," . $h_code . "^FD" . zpl_escape_field($code_txt) . "^FS\n";

	if ($precio != 0.0) {
		$price_txt = 'B/. ' . number_format($precio, 2, '.', ',');
		$zpl .= "^FO" . $x_price . "," . $y_price . "^A0R," . $h_price . "," . (int)round($h_price * 0.9) . "^FD" . zpl_escape_field($price_txt) . "^FS\n";
	}

	$zpl .= "^FO" . $x_desc . "," . $y_desc . "^A0R," . $h_desc . "," . (int)round($h_desc * 0.85) . "^FD" . zpl_escape_field($lines[0]) . "^FS\n";
	if ($lines[1] !== '') {
		$zpl .= "^FO" . ($x_desc - $dy_desc) . "," . $y_desc . "^A0R," . $h_desc . "," . (int)round($h_desc * 0.85) . "^FD" . zpl_escape_field($lines[1]) . "^FS\n";
	}

	if ($con_fecha) {
		$base = etiqueta1_parse_fecha($fecha_manufact);
		if ($base === false) {
			$base = time();
		}
		// Igual que VB6 en foto: "EXP 15/09/26" (sin Lote en SoftShop chica)
		if ($expir != 0) {
			$exp_txt = 'EXP ' . date('d/m/y', strtotime('+' . $expir . ' days', $base));
			$zpl .= "^FO" . $x_exp . "," . $y_exp . "^A0R," . $h_exp . "," . $h_exp . "^FD" . zpl_escape_field($exp_txt) . "^FS\n";
		}
	}

	$zpl .= "^PQ" . $cant . "\n^XZ\n";
	return $zpl;
}

function build_zpl_etiqueta_9($codigo, $descrip, $precio, $cant, $layoutOverride = null)
{
	return build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, 0, '', false, $layoutOverride);
}
