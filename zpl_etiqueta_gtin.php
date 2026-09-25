<?php
/**
 * ZPL GTIN chica (2.43" × 0.90" @ 203 dpi) — SoftShop horizontal.
 * Botón "Imprimir GTIN" → export_code13.php → cola gtin → GK420t_chica.
 * NO es items.etiqueta = 13 ni la caja GTI 13 (Rey 3×2).
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

function gtin_layout_num($layout, $field, $key, $default)
{
	if (!is_array($layout) || empty($layout['fields'][$field]) || !is_array($layout['fields'][$field])) {
		return $default;
	}
	if (!isset($layout['fields'][$field][$key]) || $layout['fields'][$field][$key] === '') {
		return $default;
	}
	return is_numeric($layout['fields'][$field][$key]) ? 0 + $layout['fields'][$field][$key] : $default;
}

function build_zpl_etiqueta_gtin($codigo2, $descrip, $descrip2, $cant, $layoutOverride = null)
{
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$cantStr = sprintf('%03d', $cant);
	$codigo2_digits = preg_replace('/\D/', '', (string)$codigo2);
	$descripZ = zpl_escape_field($descrip);
	$descrip2Z = zpl_escape_field($descrip2);

	$pw = 494;
	$ll = 183;
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

	$barcode_h = (int)gtin_layout_num($layoutOverride, 'barcode', 'h', 40);
	$barX = (int)gtin_layout_num($layoutOverride, 'barcode', 'x', strlen($codigo2_digits) === 13 ? 121 : 102);
	$barY = (int)gtin_layout_num($layoutOverride, 'barcode', 'y', 3);
	$txtX = (int)gtin_layout_num($layoutOverride, 'barcode_text', 'x', strlen($codigo2_digits) === 13 ? 172 : 106);
	$txtY = (int)gtin_layout_num($layoutOverride, 'barcode_text', 'y', 36);
	$txtFont = (int)gtin_layout_num($layoutOverride, 'barcode_text', 'font', 16);
	$d1x = (int)gtin_layout_num($layoutOverride, 'descripcion', 'x', 28);
	$d1y = (int)gtin_layout_num($layoutOverride, 'descripcion', 'y', 62);
	$d1f = (int)gtin_layout_num($layoutOverride, 'descripcion', 'font', 12);
	$d2x = (int)gtin_layout_num($layoutOverride, 'descripcion2', 'x', 28);
	$d2y = (int)gtin_layout_num($layoutOverride, 'descripcion2', 'y', 76);
	$d2f = (int)gtin_layout_num($layoutOverride, 'descripcion2', 'font', 12);

	if (strlen($codigo2_digits) === 13) {
		$barcodeBlock = '^FO' . $barX . ',' . $barY . '^BEN,' . $barcode_h . ',N,N,N^FD' . $codigo2_digits . '^FS
^FO' . $txtX . ',' . $txtY . '^GB150,18,18,W,0^FS
^FO' . $txtX . ',' . $txtY . '^A0N,' . $txtFont . ',' . $txtFont . '^FD' . $codigo2_digits . '^FS';
	} else {
		$codigo2_disp = zpl_escape_field($codigo2);
		$barcodeBlock = '^FO' . $barX . ',' . $barY . '^BY1,2,' . $barcode_h . '
^BCN,' . $barcode_h . ',N,N,N
^FD>;' . $codigo2_digits . '^FS
^FO' . $txtX . ',' . $txtY . '^GB150,18,18,W,0^FS
^FO' . $txtX . ',' . $txtY . '^A0N,' . $txtFont . ',' . $txtFont . '^FD' . $codigo2_disp . '^FS';
	}

	return '^XA
^PW' . $pw . '
^LL' . $ll . '
^LH0,0
' . $barcodeBlock . '
^FO' . $d1x . ',' . $d1y . '^A0N,' . $d1f . ',' . $d1f . '^FD' . $descripZ . '^FS
^FO' . $d2x . ',' . $d2y . '^A0N,' . $d2f . ',' . $d2f . '^FD' . $descrip2Z . '^FS
^PQ' . $cantStr . '
^XZ';
}
