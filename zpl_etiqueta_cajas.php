<?php
/**
 * ZPL etiqueta grande de caja (nachos, galletas, etc.).
 * Botón "Imprimir Caja" → export_code_cajas.php → cola cajas → GK420t_2x3.
 * Layout compartido: label_layouts/shared/tipo_cajas.json
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

function lote_code($ts = null)
{
	if ($ts === null || $ts === '') {
		$ts = time();
	}
	if (!is_numeric($ts)) {
		$parsed = strtotime((string)$ts);
		$ts = ($parsed === false) ? time() : $parsed;
	}
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
	return $mescod . str_pad($dia, 2, '0', STR_PAD_LEFT);
}

function cajas_logo_gfa($path, $maxW = 80, $maxH = 80, $foX = 8, $foY = 8)
{
	if (!is_file($path) || !function_exists('imagecreatefrompng')) {
		return '';
	}
	$im = @imagecreatefrompng($path);
	if (!$im) {
		return '';
	}
	imagealphablending($im, true);
	$w = imagesx($im);
	$h = imagesy($im);
	if ($w < 1 || $h < 1) {
		imagedestroy($im);
		return '';
	}
	$maxW = max(8, (int)$maxW);
	$maxH = max(8, (int)$maxH);
	$scale = min($maxW / $w, $maxH / $h, 1.0);
	$nw = max(1, (int)round($w * $scale));
	$nh = max(1, (int)round($h * $scale));
	$dst = imagecreatetruecolor($nw, $nh);
	$white = imagecolorallocate($dst, 255, 255, 255);
	imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
	imagealphablending($dst, true);
	imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
	imagedestroy($im);
	$bpr = (int)ceil($nw / 8.0);
	$data = '';
	for ($y = 0; $y < $nh; $y++) {
		for ($bx = 0; $bx < $bpr; $bx++) {
			$byte = 0;
			for ($bit = 0; $bit < 8; $bit++) {
				$x = $bx * 8 + $bit;
				$on = 0;
				if ($x < $nw) {
					$rgb = imagecolorat($dst, $x, $y);
					$a = ($rgb >> 24) & 0x7F;
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					$luma = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
					if ($a < 80 && $luma < 170) {
						$on = 1;
					}
				}
				$byte = ($byte << 1) | $on;
			}
			$data .= chr($byte);
		}
	}
	imagedestroy($dst);
	$total = strlen($data);
	if ($total < 1) {
		return '';
	}
	return '^FO' . (int)$foX . ',' . (int)$foY . '^GFA,' . $total . ',' . $total . ',' . $bpr . ',' . strtoupper(bin2hex($data)) . '^FS';
}

function cajas_layout_num($layout, $field, $key, $default)
{
	if (!is_array($layout) || empty($layout['fields'][$field]) || !is_array($layout['fields'][$field])) {
		return $default;
	}
	if (!isset($layout['fields'][$field][$key]) || $layout['fields'][$field][$key] === '') {
		return $default;
	}
	return is_numeric($layout['fields'][$field][$key])
		? 0 + $layout['fields'][$field][$key]
		: $default;
}

/**
 * @param string $gtin
 * @param string $descrip
 * @param string $descrip2
 * @param int    $unidades
 * @param int    $copies
 * @param string $elab_day Y-m-d
 * @param int    $expirDays
 * @param array|null $layoutOverride
 */
function build_zpl_etiqueta_cajas($gtin, $descrip, $descrip2, $unidades, $copies = 1, $elab_day = '', $expirDays = 0, $layoutOverride = null)
{
	$unidades = (int)$unidades;
	if ($unidades < 1) {
		$unidades = 1;
	}
	$copies = (int)$copies;
	if ($copies < 1) {
		$copies = 1;
	}
	$expirDays = (int)$expirDays;
	if ($expirDays < 0) {
		$expirDays = 0;
	}

	$elab_day = trim((string)$elab_day);
	if ($elab_day === '') {
		$elab_day = date('Y-m-d');
	}
	$elab_ts = strtotime($elab_day);
	if ($elab_ts === false) {
		$elab_ts = time();
	}
	$elab_txt = date('d/m/y', $elab_ts);
	$lote = lote_code($elab_ts);
	$exp_txt = date('d/m/y', strtotime('+' . $expirDays . ' days', $elab_ts));

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
	if ($pw < 80) {
		$pw = 80;
	}
	if ($ll < 80) {
		$ll = 80;
	}

	$logoX = (int)cajas_layout_num($layoutOverride, 'logo', 'x', 8);
	$logoY = (int)cajas_layout_num($layoutOverride, 'logo', 'y', 8);
	$logoW = (int)cajas_layout_num($layoutOverride, 'logo', 'w', 80);
	$logoH = (int)cajas_layout_num($layoutOverride, 'logo', 'h', 80);
	$nameX = (int)cajas_layout_num($layoutOverride, 'nombre', 'x', 96);
	$nameY = (int)cajas_layout_num($layoutOverride, 'nombre', 'y', 16);
	$nameFont = (int)cajas_layout_num($layoutOverride, 'nombre', 'font', 28);
	$barX = (int)cajas_layout_num($layoutOverride, 'barcode', 'x', 24);
	$barY = (int)cajas_layout_num($layoutOverride, 'barcode', 'y', 100);
	$barH = (int)cajas_layout_num($layoutOverride, 'barcode', 'h', 90);
	$uniX = (int)cajas_layout_num($layoutOverride, 'unidades', 'x', 16);
	$uniY = (int)cajas_layout_num($layoutOverride, 'unidades', 'y', 250);
	$uniFont = (int)cajas_layout_num($layoutOverride, 'unidades', 'font', 28);
	$loteX = (int)cajas_layout_num($layoutOverride, 'lote', 'x', 16);
	$loteY = (int)cajas_layout_num($layoutOverride, 'lote', 'y', 295);
	$loteFont = (int)cajas_layout_num($layoutOverride, 'lote', 'font', 28);
	$elabX = (int)cajas_layout_num($layoutOverride, 'elaboracion', 'x', 16);
	$elabY = (int)cajas_layout_num($layoutOverride, 'elaboracion', 'y', 340);
	$elabFont = (int)cajas_layout_num($layoutOverride, 'elaboracion', 'font', 28);
	$expX = (int)cajas_layout_num($layoutOverride, 'fecha', 'x', 16);
	$expY = (int)cajas_layout_num($layoutOverride, 'fecha', 'y', 385);
	$expFont = (int)cajas_layout_num($layoutOverride, 'fecha', 'font', 28);

	$logoPath = __DIR__ . DIRECTORY_SEPARATOR . 'IMG' . DIRECTORY_SEPARATOR . 'logo.png';
	$logoZpl = cajas_logo_gfa($logoPath, $logoW, $logoH, $logoX, $logoY);
	if ($logoZpl === '') {
		$nameX = (int)cajas_layout_num($layoutOverride, 'nombre', 'x', 16);
	}

	$name = trim((string)$descrip);
	if ($name === '') {
		$name = trim((string)$descrip2);
	}
	$nameZ = zpl_escape_field($name);
	$gtinDigits = preg_replace('/\D+/', '', (string)$gtin);
	if ($gtinDigits === '') {
		$gtinDigits = zpl_escape_field($gtin);
	}

	if (strlen($gtinDigits) === 13) {
		$barcodeBlock = '^FO' . $barX . ',' . $barY . '^BY2^BEN,' . $barH . ',Y,N,N^FD' . $gtinDigits . '^FS';
	} else {
		$barcodeBlock = '^BY2,2,' . $barH . "\n"
			. '^FO' . $barX . ',' . $barY . '^BCN,' . $barH . ',Y,N,N' . "\n"
			. '^FD' . zpl_escape_field($gtinDigits) . '^FS';
	}

	$zpl = '^XA
^CI28
^PW' . $pw . '
^LL' . $ll . '
^LH0,0
' . $logoZpl . '
^FO' . $nameX . ',' . $nameY . '^A0N,' . $nameFont . ',' . max(12, $nameFont - 2) . '^FD' . $nameZ . '^FS
' . $barcodeBlock . '
^FO' . $uniX . ',' . $uniY . '^A0N,' . $uniFont . ',' . $uniFont . '^FDUnidades: ' . $unidades . '^FS
^FO' . $loteX . ',' . $loteY . '^A0N,' . $loteFont . ',' . $loteFont . '^FDLote: ' . zpl_escape_field($lote) . '^FS
^FO' . $elabX . ',' . $elabY . '^A0N,' . $elabFont . ',' . $elabFont . '^FDElaboracion: ' . zpl_escape_field($elab_txt) . '^FS
';
	if ($expirDays > 0) {
		$zpl .= '^FO' . $expX . ',' . $expY . '^A0N,' . $expFont . ',' . $expFont . '^FDExp: ' . zpl_escape_field($exp_txt) . '^FS' . "\n";
	}
	$zpl .= '^PQ' . $copies . '
^XZ';
	return $zpl;
}
