<?php
/**
 * ZPL etiqueta 1 — SofyShop 1" × 0.5" (layout del VB6 Label_Printserver).
 *
 * Visual Basic Printer usa twips (ScaleMode 1): 1440 twips = 1".
 * ZPL de Zebra GK420t usa dots a 203 dpi: 203 dots = 1".
 *
 *   dots = round(twips * 203 / 1440)
 *   font_dots = round(puntos * 203 / 72)
 *
 * No se copian CurrentX/CurrentY del VB como ^FO: 1700 twips no es 1700 dots
 * (serían ~8" y la etiqueta solo tiene 203 dots de ancho).
 *
 * En X el formulario VB llega a ~1700 twips (~1.18") más el texto del código,
 * así que el diseño se escala al ancho físico de 1" (203 dots).
 * En Y sí cabe en 0.5" (720 twips = 102 dots) con la conversión 1:1.
 *
 * Valores iniciales = defaults del formulario VB (pestaña lbl1).
 * Ajustes: cambiar esas constantes o, más adelante, leer por etiqueta si se guardan.
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

/** Equivalente a corte_renglon_lbl1 del VB (Mid 1-based, corta en el último espacio). */
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

function etiqueta1_vb_x($twips)
{
	// Ancho físico 1" = 203 dots. El X del VB se diseñó ~1.5" (2160 twips).
	return (int)round($twips * 203.0 / 2160.0);
}

function etiqueta1_vb_y($twips)
{
	return (int)round($twips * 203.0 / 1440.0);
}

function etiqueta1_vb_font($pt)
{
	$h = (int)round($pt * 203.0 / 72.0);
	return $h < 10 ? 10 : $h;
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
	// Valores del formulario VB (printserver.frm, pestaña lbl1)
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

	// Solo si se pasa override (preview JSON). Impresion produccion no lee JSON.
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

	$pw = 203;
	$ll = 102;

	$codigo = str_pad(preg_replace('/\D/', '', (string)$codigo), 6, '0', STR_PAD_LEFT);
	$codigo = substr($codigo, -6);
	$code_txt = '[' . $codigo . ']';

	$x_bc = etiqueta1_vb_x($vb_bc_x);
	$y_bc = etiqueta1_vb_y($vb_bc_y);
	$x_code = etiqueta1_vb_x($vb_code_x);
	$y_code = etiqueta1_vb_y($vb_code_y);
	$h_code = etiqueta1_vb_font($vb_code_pt);
	$x_desc = etiqueta1_vb_x($vb_desc_x);
	$y_desc = etiqueta1_vb_y($vb_desc_y);
	$h_desc = etiqueta1_vb_font($vb_desc_pt);
	$dy_desc = etiqueta1_vb_y($vb_desc_renglon);
	$x_exp = etiqueta1_vb_x($vb_exp_x);
	$y_exp = etiqueta1_vb_y($vb_exp_y);
	$h_exp = etiqueta1_vb_font($vb_exp_pt);
	$x_price = etiqueta1_vb_x($vb_price_x);
	$y_price = etiqueta1_vb_y($vb_price_y);
	$h_price = etiqueta1_vb_font($vb_price_pt);

	$descrip_esc = zpl_escape_field(trim(preg_replace('/\s+/', ' ', (string)$descrip)));
	$w_desc = (int)round($h_desc * 0.55);
	if ($w_desc < 8) {
		$w_desc = 8;
	}
	$fb_w = $pw - $x_desc - 4;
	if ($fb_w < 80) {
		$fb_w = 80;
	}
	$fb_gap = $dy_desc - $h_desc;
	if ($fb_gap < 0) {
		$fb_gap = 0;
	}

	$bc_h = etiqueta1_vb_y($vb_bc_h);
	if ($bc_h > ($y_desc - $y_bc - 2)) {
		$bc_h = $y_desc - $y_bc - 2;
	}
	if ($bc_h < 18) {
		$bc_h = 18;
	}
	$bc_w = etiqueta1_vb_x($vb_bc_w);
	$max_bc_w = $x_price - $x_bc - 4;
	if ($max_bc_w < 40) {
		$max_bc_w = 80;
	}
	if ($bc_w > $max_bc_w) {
		$bc_w = $max_bc_w;
	}

	if ($x_code + 55 > $pw) {
		$x_code = $pw - 58;
	}
	if ($x_price + 70 > $pw) {
		$x_price = $pw - 72;
	}

	$precio = (float)$precio;
	$expir = (int)$expir;
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}

	$zpl = "^XA\n^FWN\n^PON\n^CI28\n^PW" . $pw . "\n^LL" . $ll . "\n^LH0,0\n";
	$zpl .= "^FO" . $x_bc . "," . $y_bc . "^BY1,2," . $bc_h . "\n";
	$zpl .= "^BCN," . $bc_h . ",N,N,N\n^FD" . $codigo . "^FS\n";
	$zpl .= "^FO" . $x_code . "," . $y_code . "^A0N," . $h_code . "," . $h_code . "^FD" . zpl_escape_field($code_txt) . "^FS\n";

	if ($precio != 0.0) {
		$price_txt = 'B/.' . number_format($precio, 2, '.', ',');
		$zpl .= "^FO" . $x_price . "," . $y_price . "^A0N," . $h_price . "," . (int)round($h_price * 0.85) . "^FD" . zpl_escape_field($price_txt) . "^FS\n";
	}

	$zpl .= "^FO" . $x_desc . "," . $y_desc . "^A0N," . $h_desc . "," . $w_desc . "^FB" . $fb_w . ",2," . $fb_gap . ",L,0^FD" . $descrip_esc . "^FS\n";

	if ($con_fecha) {
		$base = etiqueta1_parse_fecha($fecha_manufact);
		if ($base === false) {
			$base = time();
		}
		$lote_txt = 'Lote:' . etiqueta1_lote_code($base);
		$x_lote = $pw - 70;
		$zpl .= "^FO" . $x_lote . "," . $y_exp . "^A0N," . $h_exp . "," . $h_exp . "^FD" . zpl_escape_field($lote_txt) . "^FS\n";

		if ($expir != 0) {
			$exp_txt = 'EXP:' . date('d/m/Y', strtotime('+' . $expir . ' days', $base));
			$zpl .= "^FO" . $x_exp . "," . $y_exp . "^A0N," . $h_exp . "," . $h_exp . "^FD" . zpl_escape_field($exp_txt) . "^FS\n";
		}
	}

	$zpl .= "^PQ" . $cant . "\n^XZ\n";
	return $zpl;
}

function build_zpl_etiqueta_9($codigo, $descrip, $precio, $cant, $layoutOverride = null)
{
	return build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, 0, '', false, $layoutOverride);
}
