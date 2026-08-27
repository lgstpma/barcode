<?php
/**
 * ZPL etiqueta 5 — mediana SoftShop + ingredientes
 * VB6: Imprimir_lbl5_mediana_ingredientes_sofyshop
 * Defaults formulario lbl2; impresora VB: GK420t_grande
 *
 * dots = twips × 203 / 1440
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

function etiqueta5_twips_to_dots($twips)
{
	return (int)round(((float)$twips) * 203.0 / 1440.0);
}

function etiqueta5_font_dots($pt)
{
	$h = (int)round(((float)$pt) * 203.0 / 72.0);
	return $h < 12 ? 12 : $h;
}

function etiqueta5_mb_len($s)
{
	return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function etiqueta5_mb_sub($s, $start, $len = null)
{
	if (function_exists('mb_substr')) {
		return $len === null ? mb_substr($s, $start, null, 'UTF-8') : mb_substr($s, $start, $len, 'UTF-8');
	}
	return $len === null ? substr($s, $start) : substr($s, $start, $len);
}

/** Equivalente simplificado a corte_renglon del VB. */
function etiqueta5_corte_renglon($cadena, $maxrenglon)
{
	$n = etiqueta5_mb_len($cadena);
	if ($maxrenglon < 1) {
		return 0;
	}
	if ($maxrenglon > $n) {
		$maxrenglon = $n;
	}
	$char_cont = 0;
	$tempstring = etiqueta5_mb_sub($cadena, $maxrenglon - 1, 1);
	while ($tempstring !== ' ' && $char_cont < $maxrenglon) {
		$char_cont++;
		$pos = $maxrenglon - $char_cont;
		if ($pos < 1) {
			break;
		}
		$tempstring = etiqueta5_mb_sub($cadena, $pos - 1, 1);
	}
	return $char_cont;
}

function etiqueta5_wrap_lines($text, $alcance, $maxLines = 3)
{
	$text = trim(preg_replace('/\s+/', ' ', (string)$text));
	$lines = array();
	$alcance = (int)$alcance;
	if ($alcance < 8) {
		$alcance = 33;
	}
	while ($text !== '' && count($lines) < $maxLines) {
		$len = etiqueta5_mb_len($text);
		if ($len <= $alcance) {
			$lines[] = $text;
			break;
		}
		$cut = etiqueta5_corte_renglon($text, $alcance);
		$take = $alcance - $cut;
		if ($take < 1) {
			$take = $alcance;
		}
		$lines[] = trim(etiqueta5_mb_sub($text, 0, $take));
		$text = trim(etiqueta5_mb_sub($text, $take));
	}
	return $lines;
}

function etiqueta5_parse_fecha($fecha)
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
 * @param string $ingredientes VB pasa "ingredientes" al parámetro especification
 */
function build_zpl_etiqueta_5($codigo, $descrip, $precio, $cant, $expir, $fecha_manufact, $ingredientes = '', $layoutOverride = null)
{
	// Defaults VB pestaña lbl2
	$vb_w = 3395;
	$vb_h = 2263;
	$vb_bc_x = 1;
	$vb_bc_y = 70;
	$vb_bc_h = 350; // alto útil (VB pinta 1400 pero se recorta sobre el layout)
	$vb_price_x = 2500;
	$vb_price_y = 60;
	$vb_price_pt = 12;
	$vb_code_x = 10;
	$vb_code_y = 1800;
	$vb_code_pt = 7;
	$vb_desc_x = 10;
	$vb_desc_y = 400;
	$vb_desc_pt = 10;
	$vb_desc_alcance = 33;
	$vb_desc_renglon = 200;
	$vb_ingre_x = 10;
	$vb_ingre_y = 820;
	$vb_ingre_pt = 10;
	$vb_instruc_x = 10;
	$vb_instruc_y = 820;
	$vb_instruc_renglon = 230;
	$vb_instruc_alcance = 33;
	$vb_exp_x = 2100;
	$vb_exp_y = 1800;
	$vb_exp_pt = 8;

	if (is_array($layoutOverride)) {
		if (!empty($layoutOverride['label']) && is_array($layoutOverride['label'])) {
			if (isset($layoutOverride['label']['width'])) $vb_w = (int)$layoutOverride['label']['width'];
			if (isset($layoutOverride['label']['height'])) $vb_h = (int)$layoutOverride['label']['height'];
		}
		if (!empty($layoutOverride['fields']) && is_array($layoutOverride['fields'])) {
			$f = $layoutOverride['fields'];
			if (!empty($f['barcode'])) {
				if (isset($f['barcode']['x'])) $vb_bc_x = (int)$f['barcode']['x'];
				if (isset($f['barcode']['y'])) $vb_bc_y = (int)$f['barcode']['y'];
				if (isset($f['barcode']['h'])) $vb_bc_h = (int)$f['barcode']['h'];
			}
			if (!empty($f['precio'])) {
				if (isset($f['precio']['x'])) $vb_price_x = (int)$f['precio']['x'];
				if (isset($f['precio']['y'])) $vb_price_y = (int)$f['precio']['y'];
				if (isset($f['precio']['font'])) $vb_price_pt = (float)$f['precio']['font'];
			}
			if (!empty($f['itemid'])) {
				if (isset($f['itemid']['x'])) $vb_code_x = (int)$f['itemid']['x'];
				if (isset($f['itemid']['y'])) $vb_code_y = (int)$f['itemid']['y'];
				if (isset($f['itemid']['font'])) $vb_code_pt = (float)$f['itemid']['font'];
			}
			if (!empty($f['descripcion'])) {
				if (isset($f['descripcion']['x'])) $vb_desc_x = (int)$f['descripcion']['x'];
				if (isset($f['descripcion']['y'])) $vb_desc_y = (int)$f['descripcion']['y'];
				if (isset($f['descripcion']['font'])) $vb_desc_pt = (float)$f['descripcion']['font'];
				if (isset($f['descripcion']['alcance'])) $vb_desc_alcance = (int)$f['descripcion']['alcance'];
				if (isset($f['descripcion']['renglon'])) $vb_desc_renglon = (int)$f['descripcion']['renglon'];
			}
			if (!empty($f['ingredientes'])) {
				if (isset($f['ingredientes']['x'])) $vb_ingre_x = (int)$f['ingredientes']['x'];
				if (isset($f['ingredientes']['y'])) $vb_ingre_y = (int)$f['ingredientes']['y'];
				if (isset($f['ingredientes']['font'])) $vb_ingre_pt = (float)$f['ingredientes']['font'];
			}
			if (!empty($f['fecha'])) {
				if (isset($f['fecha']['x'])) $vb_exp_x = (int)$f['fecha']['x'];
				if (isset($f['fecha']['y'])) $vb_exp_y = (int)$f['fecha']['y'];
				if (isset($f['fecha']['font'])) $vb_exp_pt = (float)$f['fecha']['font'];
			}
		}
	}

	$pw = max(1, etiqueta5_twips_to_dots($vb_w));
	$ll = max(1, etiqueta5_twips_to_dots($vb_h));

	$codigo = str_pad(preg_replace('/\D/', '', (string)$codigo), 6, '0', STR_PAD_LEFT);
	$codigo = substr($codigo, -6);
	$code_txt = '[' . $codigo . ']';

	$x_bc = etiqueta5_twips_to_dots($vb_bc_x);
	$y_bc = etiqueta5_twips_to_dots($vb_bc_y);
	$bc_h = etiqueta5_twips_to_dots($vb_bc_h);
	$x_price = etiqueta5_twips_to_dots($vb_price_x);
	$y_price = etiqueta5_twips_to_dots($vb_price_y);
	$h_price = etiqueta5_font_dots($vb_price_pt);
	$x_code = etiqueta5_twips_to_dots($vb_code_x);
	$y_code = etiqueta5_twips_to_dots($vb_code_y);
	$h_code = etiqueta5_font_dots($vb_code_pt);
	$x_desc = etiqueta5_twips_to_dots($vb_desc_x);
	$y_desc = etiqueta5_twips_to_dots($vb_desc_y);
	$h_desc = etiqueta5_font_dots($vb_desc_pt);
	$dy_desc = etiqueta5_twips_to_dots($vb_desc_renglon);
	$x_ing = etiqueta5_twips_to_dots($vb_ingre_x);
	$y_ing = etiqueta5_twips_to_dots($vb_ingre_y);
	$h_ing = etiqueta5_font_dots($vb_ingre_pt);
	$x_ins = etiqueta5_twips_to_dots($vb_instruc_x);
	$y_ins = etiqueta5_twips_to_dots($vb_instruc_y);
	$dy_ins = etiqueta5_twips_to_dots($vb_instruc_renglon);
	$x_exp = etiqueta5_twips_to_dots($vb_exp_x);
	$y_exp = etiqueta5_twips_to_dots($vb_exp_y);
	$h_exp = etiqueta5_font_dots($vb_exp_pt);

	if ($bc_h > ($y_desc - $y_bc - 2)) {
		$bc_h = max(28, $y_desc - $y_bc - 2);
	}
	if ($x_price + 90 > $pw) {
		$x_price = $pw - 100;
	}
	if ($y_code + $h_code > $ll) {
		$y_code = $ll - $h_code - 2;
	}
	if ($y_exp + $h_exp > $ll) {
		$y_exp = $ll - $h_exp - 2;
	}

	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$precio = (float)$precio;
	$expir = (int)$expir;

	$zpl = "^XA\n^FWN\n^PON\n^CI28\n^PW" . $pw . "\n^LL" . $ll . "\n^LH0,0\n";

	$zpl .= "^FO" . $x_bc . "," . $y_bc . "^BY2,2," . $bc_h . "\n";
	$zpl .= "^BCN," . $bc_h . ",N,N,N\n^FD" . $codigo . "^FS\n";

	if ($precio != 0.0) {
		$price_txt = 'B/.' . number_format($precio, 2, '.', ',');
		$zpl .= "^FO" . $x_price . "," . $y_price . "^A0N," . $h_price . "," . (int)round($h_price * 0.85) . "^FD" . zpl_escape_field($price_txt) . "^FS\n";
	}

	$descLines = etiqueta5_wrap_lines($descrip, $vb_desc_alcance, 2);
	$yy = $y_desc;
	foreach ($descLines as $line) {
		$zpl .= "^FO" . $x_desc . "," . $yy . "^A0N," . $h_desc . "," . (int)round($h_desc * 0.55) . "^FD" . zpl_escape_field($line) . "^FS\n";
		$yy += $dy_desc;
	}

	$zpl .= "^FO" . $x_ing . "," . $y_ing . "^A0N," . $h_ing . "," . (int)round($h_ing * 0.55) . "^FD" . zpl_escape_field('Ingredientes:') . "^FS\n";

	$ingLines = etiqueta5_wrap_lines($ingredientes, $vb_instruc_alcance, 3);
	for ($i = 0; $i < count($ingLines); $i++) {
		$yLine = $y_ins + (($i + 1) * $dy_ins);
		if ($yLine + $h_ing > $ll - 4) {
			break;
		}
		$zpl .= "^FO" . $x_ins . "," . $yLine . "^A0N," . $h_ing . "," . (int)round($h_ing * 0.5) . "^FD" . zpl_escape_field($ingLines[$i]) . "^FS\n";
	}

	$zpl .= "^FO" . $x_code . "," . $y_code . "^A0N," . $h_code . "," . $h_code . "^FD" . zpl_escape_field($code_txt) . "^FS\n";

	if ($expir != 0) {
		$base = etiqueta5_parse_fecha($fecha_manufact);
		if ($base === false) {
			$base = time();
		}
		$exp_txt = 'EXP: ' . date('d/m/Y', strtotime('+' . $expir . ' days', $base));
		$zpl .= "^FO" . $x_exp . "," . $y_exp . "^A0N," . $h_exp . "," . $h_exp . "^FD" . zpl_escape_field($exp_txt) . "^FS\n";
	}

	$zpl .= "^PQ" . $cant . "\n^XZ\n";
	return $zpl;
}
