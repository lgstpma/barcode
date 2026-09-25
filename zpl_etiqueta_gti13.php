<?php
/**
 * ZPL GTI 13 — etiqueta caja unidades 2" × 3" @ 203 dpi (Supermercado Rey).
 * Botón "Imprimir GTI 13" → export_gti13.php → cola gti13 → GK420t_2x3.
 * Independiente del GTIN SoftShop chica (export_code13.php).
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

/**
 * @param string $codigo2 GTIN-13
 * @param string $descrip
 * @param string $descrip2
 * @param int    $unidades Unidades en la caja (texto "N unidades")
 * @param int    $cant     Copias a imprimir (^PQ)
 * @param string $elab_day Y-m-d → Lote: DD/MM
 * @param int    $expir    Días → Exp: DD/MM
 * @param string $descrip3 Nombre preferido
 */
function gti13_layout_num($layout, $field, $key, $default)
{
	if (!is_array($layout) || empty($layout['fields'][$field]) || !is_array($layout['fields'][$field])) {
		return $default;
	}
	if (!isset($layout['fields'][$field][$key]) || $layout['fields'][$field][$key] === '') {
		return $default;
	}
	return is_numeric($layout['fields'][$field][$key]) ? 0 + $layout['fields'][$field][$key] : $default;
}

function build_zpl_etiqueta_gti13($codigo2, $descrip, $descrip2, $unidades, $cant = 1, $elab_day = '', $expir = 0, $descrip3 = '', $layoutOverride = null)
{
	$unidades = (int)$unidades;
	if ($unidades < 1) {
		$unidades = 1;
	}
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$cantStr = sprintf('%03d', $cant);

	$codigo2_digits = preg_replace('/\D/', '', (string)$codigo2);
	$nombre = trim((string)$descrip3);
	if ($nombre === '') {
		$nombre = trim(trim((string)$descrip) . ' ' . trim((string)$descrip2));
	}
	if (function_exists('mb_strtoupper')) {
		$nombre = mb_strtoupper($nombre, 'UTF-8');
	} else {
		$nombre = strtoupper($nombre);
	}
	$nombreZ = zpl_escape_field($nombre);

	$elab_day = trim((string)$elab_day);
	if ($elab_day === '') {
		$elab_day = date('Y-m-d');
	}
	$base = strtotime($elab_day);
	if ($base === false) {
		$base = time();
	}
	$expir = (int)$expir;
	if ($expir < 0) {
		$expir = 0;
	}
	$loteTxt = date('d/m', $base);
	$expTxt = date('d/m', strtotime('+' . $expir . ' days', $base));

	$gtinDisp = $codigo2_digits !== '' ? $codigo2_digits : zpl_escape_field($codigo2);
	$unidadesLine = $unidades . ' unidades';
	$loteLine = 'Lote: ' . $loteTxt;
	$expLine = 'Exp: ' . $expTxt;
	$brand = 'La Cocina de Sofy';

	$qrPayload = $brand . "\n"
		. $nombre . "\n"
		. 'GTIN: ' . $gtinDisp . "\n"
		. $unidadesLine . "\n"
		. $loteLine . "\n"
		. $expLine;
	$qrPayload = str_replace(array('^', '~'), '', $qrPayload);

	$pw = 406;
	$ll = 609;
	if (is_array($layoutOverride) && !empty($layoutOverride['label']) && is_array($layoutOverride['label'])) {
		$lab = $layoutOverride['label'];
		if (!empty($lab['width_dots'])) {
			$pw = (int)$lab['width_dots'];
		} elseif (!empty($lab['width_in'])) {
			$pw = (int)round((float)$lab['width_in'] * 203);
		}
		if (!empty($lab['height_dots'])) {
			$ll = (int)$lab['height_dots'];
		} elseif (!empty($lab['height_in'])) {
			$ll = (int)round((float)$lab['height_in'] * 203);
		}
	}

	$nameX = (int)gti13_layout_num($layoutOverride, 'nombre', 'x', 16);
	$nameY = (int)gti13_layout_num($layoutOverride, 'nombre', 'y', 16);
	$nameH = (int)gti13_layout_num($layoutOverride, 'nombre', 'font', strlen($nombreZ) > 22 ? 26 : 32);
	$nameW = $nameH > 22 ? max(20, $nameH - 2) : $nameH;
	$barX = (int)gti13_layout_num($layoutOverride, 'barcode', 'x', strlen($codigo2_digits) === 13 ? 40 : 30);
	$barY = (int)gti13_layout_num($layoutOverride, 'barcode', 'y', 70);
	$barH = (int)gti13_layout_num($layoutOverride, 'barcode', 'h', 100);
	$txtX = (int)gti13_layout_num($layoutOverride, 'barcode_text', 'x', strlen($codigo2_digits) === 13 ? 90 : 60);
	$txtY = (int)gti13_layout_num($layoutOverride, 'barcode_text', 'y', 180);
	$txtFont = (int)gti13_layout_num($layoutOverride, 'barcode_text', 'font', 26);
	$uniX = (int)gti13_layout_num($layoutOverride, 'unidades', 'x', 16);
	$uniY = (int)gti13_layout_num($layoutOverride, 'unidades', 'y', 220);
	$uniFont = (int)gti13_layout_num($layoutOverride, 'unidades', 'font', 30);
	$loteX = (int)gti13_layout_num($layoutOverride, 'lote', 'x', 16);
	$loteY = (int)gti13_layout_num($layoutOverride, 'lote', 'y', 265);
	$loteFont = (int)gti13_layout_num($layoutOverride, 'lote', 'font', 26);
	$expX = (int)gti13_layout_num($layoutOverride, 'fecha', 'x', 16);
	$expY = (int)gti13_layout_num($layoutOverride, 'fecha', 'y', 305);
	$expFont = (int)gti13_layout_num($layoutOverride, 'fecha', 'font', 26);
	$brandX = (int)gti13_layout_num($layoutOverride, 'brand', 'x', 16);
	$brandY = (int)gti13_layout_num($layoutOverride, 'brand', 'y', 355);
	$brandFont = (int)gti13_layout_num($layoutOverride, 'brand', 'font', 22);
	$qrX = (int)gti13_layout_num($layoutOverride, 'qr', 'x', 250);
	$qrY = (int)gti13_layout_num($layoutOverride, 'qr', 'y', 400);

	if (strlen($codigo2_digits) === 13) {
		$barcodeBlock = '^FO' . $barX . ',' . $barY . '^BY2^BEN,' . $barH . ',N,N,N^FD' . $codigo2_digits . '^FS
^FO' . $txtX . ',' . $txtY . '^A0N,' . $txtFont . ',' . $txtFont . '^FD' . $codigo2_digits . '^FS';
	} else {
		$barcodeBlock = '^FO' . $barX . ',' . $barY . '^BY2,2,' . $barH . '
^BCN,' . $barH . ',N,N,N
^FD>;' . $gtinDisp . '^FS
^FO' . $txtX . ',' . $txtY . '^A0N,' . $txtFont . ',' . $txtFont . '^FD' . $gtinDisp . '^FS';
	}

	return '^XA
^PW' . $pw . '
^LL' . $ll . '
^LH0,0
^CI28
^FO' . $nameX . ',' . $nameY . '^A0N,' . $nameH . ',' . $nameW . '^FD' . $nombreZ . '^FS
' . $barcodeBlock . '
^FO' . $uniX . ',' . $uniY . '^A0N,' . $uniFont . ',' . $uniFont . '^FD' . zpl_escape_field($unidadesLine) . '^FS
^FO' . $loteX . ',' . $loteY . '^A0N,' . $loteFont . ',' . $loteFont . '^FD' . zpl_escape_field($loteLine) . '^FS
^FO' . $expX . ',' . $expY . '^A0N,' . $expFont . ',' . $expFont . '^FD' . zpl_escape_field($expLine) . '^FS
^FO' . $brandX . ',' . $brandY . '^A0N,' . $brandFont . ',' . $brandFont . '^FD' . zpl_escape_field($brand) . '^FS
^FO' . $qrX . ',' . $qrY . '^BQN,2,4^FDQA,' . $qrPayload . '^FS
^PQ' . $cantStr . '
^XZ';
}
