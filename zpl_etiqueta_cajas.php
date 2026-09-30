<?php
/**
 * ZPL etiqueta de caja (2" × 3" @ 203 dpi = 406 × 609).
 * Nombre con salto de línea si es largo; barcode vertical + datos.
 * Botón "Imprimir Caja" → export_code_cajas.php → cola cajas → GK420t_2x3.
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

/**
 * Convierte logo a ^GFA. Fondo blanco, tinta negra, sin marco por bits de padding.
 */
function cajas_logo_gfa($path, $maxW = 100, $maxH = 100, $foX = 8, $foY = 6)
{
	if (!is_file($path) || !function_exists('imagecreatefrompng')) {
		return '';
	}
	$im = @imagecreatefrompng($path);
	if (!$im && function_exists('imagecreatefromjpeg')) {
		$im = @imagecreatefromjpeg($path);
	}
	if (!$im) {
		return '';
	}
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
	// Ancho múltiplo de 8 evita línea vertical basura a la derecha del ^GFA
	$nwPad = (int)(ceil($nw / 8.0) * 8);

	$dst = imagecreatetruecolor($nwPad, $nh);
	$white = imagecolorallocate($dst, 255, 255, 255);
	imagefilledrectangle($dst, 0, 0, $nwPad, $nh, $white);
	imagealphablending($dst, true);
	imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
	imagedestroy($im);

	$bpr = (int)($nwPad / 8);
	$data = '';
	for ($y = 0; $y < $nh; $y++) {
		for ($bx = 0; $bx < $bpr; $bx++) {
			$byte = 0;
			for ($bit = 0; $bit < 8; $bit++) {
				$x = $bx * 8 + $bit;
				$on = 0;
				if ($x < $nw) {
					$rgb = imagecolorat($dst, $x, $y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					$luma = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
					// Solo tinta oscura; blanco/gris claro = vacío (sin marco)
					if ($luma < 140) {
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

function cajas_logo_path()
{
	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'IMG' . DIRECTORY_SEPARATOR;
	// logo_cajas.png = versión B/N compatible con GD (logo_lcds2 a veces no abre en PHP)
	foreach (array('logo_cajas.png', 'logo_lcds2.png', 'logo.png') as $f) {
		$p = $dir . $f;
		if (is_file($p)) {
			return $p;
		}
	}
	return $dir . 'logo.png';
}

/**
 * Parte el nombre solo en espacios (no a mitad de palabra). Máx $maxLines.
 * Devuelve array de renglones.
 */
function cajas_name_word_wrap_lines($name, $fbWidthDots, $charW, $maxLines = 2)
{
	$name = trim(preg_replace('/\s+/', ' ', (string)$name));
	if ($name === '') {
		return array();
	}
	$charW = max(8, (int)$charW);
	$maxChars = max(6, (int)floor(((int)$fbWidthDots) / $charW));
	$maxLines = max(1, (int)$maxLines);
	$lenFn = function ($s) {
		return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
	};

	$words = explode(' ', $name);
	$lines = array();
	$cur = '';
	foreach ($words as $w) {
		if ($w === '') {
			continue;
		}
		$trial = ($cur === '') ? $w : ($cur . ' ' . $w);
		if ($lenFn($trial) <= $maxChars || $cur === '') {
			$cur = $trial;
			continue;
		}
		$lines[] = $cur;
		$cur = $w;
		if (count($lines) === $maxLines - 1) {
			$idx = array_search($w, $words, true);
			$cur = implode(' ', array_slice($words, (int)$idx));
			break;
		}
	}
	if ($cur !== '') {
		$lines[] = $cur;
	}
	return array_slice($lines, 0, $maxLines);
}

/**
 * 2"×3": nombre tamaño Nachos (fijo), hasta 2 renglones; barcode + datos.
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

	// Mismo cuerpo que Nachos; zona de nombre más alta para 2 renglones
	$nameX = 0;
	$nameY = 6;
	$nameFont = 68;
	$nameCharW = 52;
	$nameLineGap = 10;
	$wrapCharW = 28; // solo para decidir saltos; la letra impresa usa nameCharW
	$sepY = 210;

	$dataX = 68;
	$uniY = 235;
	$uniFont = 34;
	$loteY = 290;
	$loteFont = 34;
	$elabY = 345;
	$elabFont = 32;
	$expY = 400;
	$expFont = 34;
	$gtinY = 470;

	$barX = 0;
	$barY = 225;
	$barH = 70;
	$shiftLeft = 24; // corre todo a la izquierda (^LS negativo)

	if (is_array($layoutOverride)) {
		if (!empty($layoutOverride['label']['width_dots'])) {
			$pw = (int)$layoutOverride['label']['width_dots'];
		}
		if (!empty($layoutOverride['label']['height_dots'])) {
			$ll = (int)$layoutOverride['label']['height_dots'];
		}
		$shiftLeft = (int)cajas_layout_num($layoutOverride, 'origen', 'shift_left', $shiftLeft);
		$nameX = (int)cajas_layout_num($layoutOverride, 'nombre', 'x', $nameX);
		$nameY = (int)cajas_layout_num($layoutOverride, 'nombre', 'y', $nameY);
		$nameFont = (int)cajas_layout_num($layoutOverride, 'nombre', 'font', $nameFont);
		$nameCharW = (int)cajas_layout_num($layoutOverride, 'nombre', 'w', $nameCharW);
		$nameLineGap = (int)cajas_layout_num($layoutOverride, 'nombre', 'line_gap', $nameLineGap);
		$wrapCharW = (int)cajas_layout_num($layoutOverride, 'nombre', 'wrap_w', $wrapCharW);
		$sepY = (int)cajas_layout_num($layoutOverride, 'separador', 'y', $sepY);
		$dataX = (int)cajas_layout_num($layoutOverride, 'unidades', 'x', $dataX);
		$uniY = (int)cajas_layout_num($layoutOverride, 'unidades', 'y', $uniY);
		$uniFont = (int)cajas_layout_num($layoutOverride, 'unidades', 'font', $uniFont);
		$loteY = (int)cajas_layout_num($layoutOverride, 'lote', 'y', $loteY);
		$loteFont = (int)cajas_layout_num($layoutOverride, 'lote', 'font', $loteFont);
		$elabY = (int)cajas_layout_num($layoutOverride, 'elaboracion', 'y', $elabY);
		$elabFont = (int)cajas_layout_num($layoutOverride, 'elaboracion', 'font', $elabFont);
		$expY = (int)cajas_layout_num($layoutOverride, 'fecha', 'y', $expY);
		$expFont = (int)cajas_layout_num($layoutOverride, 'fecha', 'font', $expFont);
		$gtinY = (int)cajas_layout_num($layoutOverride, 'gtin', 'y', $gtinY);
		$barX = (int)cajas_layout_num($layoutOverride, 'barcode', 'x', $barX);
		$barY = (int)cajas_layout_num($layoutOverride, 'barcode', 'y', $barY);
		$barH = (int)cajas_layout_num($layoutOverride, 'barcode', 'h', $barH);
	}

	$ls = -1 * abs((int)$shiftLeft);

	$name = trim((string)$descrip);
	if ($name === '') {
		$name = trim((string)$descrip2);
	}
	$nameZ = zpl_escape_field($name);
	$nameFbW = max(200, $pw - $nameX - 4);
	$nameLines = cajas_name_word_wrap_lines($nameZ, $nameFbW, max(16, $wrapCharW), 2);
	if (count($nameLines) < 1) {
		$nameLines = array($nameZ);
	}

	$gtinDigits = preg_replace('/\D+/', '', (string)$gtin);
	if ($gtinDigits === '') {
		$gtinDigits = zpl_escape_field($gtin);
	}
	$gtinDisp = $gtinDigits !== '' ? $gtinDigits : zpl_escape_field($gtin);

	if (strlen($gtinDigits) === 13) {
		$barcodeBlock = '^FO' . $barX . ',' . $barY . '^BY2^BEB,' . $barH . ',N,N,N^FD' . $gtinDigits . '^FS';
	} else {
		$barcodeBlock = '^BY2,2,' . $barH . "\n"
			. '^FO' . $barX . ',' . $barY . '^BCB,' . $barH . ',N,N,N' . "\n"
			. '^FD' . zpl_escape_field($gtinDigits) . '^FS';
	}

	$sepX = 0;
	$sepW = max(200, $pw - 4);

	// Cada renglón con el mismo ^A0N que Nachos (sin ^FB que estrecha)
	$nameBlock = '';
	$lineStep = $nameFont + $nameLineGap;
	foreach ($nameLines as $i => $line) {
		$y = $nameY + ($i * $lineStep);
		$nameBlock .= '^FO' . $nameX . ',' . $y . '^A0N,' . $nameFont . ',' . $nameCharW . '^FD' . $line . '^FS' . "\n";
	}

	$zpl = '^XA
^CI28
^PW' . $pw . '
^LL' . $ll . '
^LH0,0
^LS' . $ls . '
' . $nameBlock . '^FO' . $sepX . ',' . $sepY . '^GB' . $sepW . ',2,2^FS
' . $barcodeBlock . '
^FO' . $dataX . ',' . $uniY . '^A0N,' . $uniFont . ',' . $uniFont . '^FDUnidades: ' . $unidades . '^FS
^FO' . $dataX . ',' . $loteY . '^A0N,' . $loteFont . ',' . $loteFont . '^FDLote: ' . zpl_escape_field($lote) . '^FS
^FO' . $dataX . ',' . $elabY . '^A0N,' . $elabFont . ',' . $elabFont . '^FDElaboracion: ' . zpl_escape_field($elab_txt) . '^FS
';
	if ($expirDays > 0) {
		$zpl .= '^FO' . $dataX . ',' . $expY . '^A0N,' . $expFont . ',' . $expFont . '^FDExp: ' . zpl_escape_field($exp_txt) . '^FS' . "\n";
	}
	$zpl .= '^FO' . $dataX . ',' . $gtinY . '^A0N,20,18^FDGTIN: ' . zpl_escape_field($gtinDisp) . '^FS
^PQ' . $copies . '
^XZ';
	return $zpl;
}
