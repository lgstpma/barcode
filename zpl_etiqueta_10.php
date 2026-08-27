<?php
/**
 * ZPL etiqueta 10 — diseño .bmp/.png del producto (carpeta printserver).
 *
 * Zebra no imprime BMP nativo: se convierte a gráfico 1 bit (^GFA).
 * El archivo se busca como printserver/{codigo}.bmp (luego .png / .jpg).
 *
 * Conversión fija (203 dpi): dots = twips x 203 / 1440.
 * BMP a design_* (PaintPicture), cortado arriba del barcode.
 * lbl_lines 1:1 + sesgo X +200; si el texto pisa el arte, se mueve al hueco libre.
 * Sin overlay de nombre/ingredientes (van en la imagen).
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

function etiqueta10_images_dir()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'printserver';
}

function etiqueta10_pad_codigo($codigo)
{
	$codigo = preg_replace('/\D/', '', (string)$codigo);
	$codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
	if (strlen($codigo) > 6) {
		$codigo = substr($codigo, -6);
	}
	return $codigo;
}

function etiqueta10_u16le($bin, $off)
{
	$a = unpack('v', substr($bin, $off, 2));
	return (int)$a[1];
}

function etiqueta10_u32le($bin, $off)
{
	$a = unpack('V', substr($bin, $off, 4));
	return (int)$a[1];
}

function etiqueta10_i32le($bin, $off)
{
	$u = etiqueta10_u32le($bin, $off);
	if ($u >= 2147483648) {
		return $u - 4294967296;
	}
	return $u;
}

function etiqueta10_twips_to_dots($twips)
{
	return (int)round(((float)$twips) * 203.0 / 1440.0);
}

function etiqueta10_inches_to_dots($inches)
{
	return (int)round(((float)$inches) * 203.0);
}

function etiqueta10_find_image($codigo)
{
	$dir = etiqueta10_images_dir();
	$code = etiqueta10_pad_codigo($codigo);
	$names = array($code, ltrim($code, '0'));
	if ($names[1] === '') {
		$names[1] = '0';
	}
	$exts = array('bmp', 'BMP', 'png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG');
	foreach ($names as $name) {
		foreach ($exts as $ext) {
			$path = $dir . DIRECTORY_SEPARATOR . $name . '.' . $ext;
			if (is_file($path)) {
				return $path;
			}
		}
	}
	return '';
}

function etiqueta10_luma($r, $g, $b)
{
	return 0.299 * $r + 0.587 * $g + 0.114 * $b;
}

/** Buffer de bits: 1 = negro. */
function etiqueta10_new_bits($w, $h)
{
	return array(
		'w' => (int)$w,
		'h' => (int)$h,
		'p' => str_repeat("\x00", (int)$w * (int)$h),
	);
}

function etiqueta10_set_black(&$bits, $x, $y)
{
	if ($x < 0 || $y < 0 || $x >= $bits['w'] || $y >= $bits['h']) {
		return;
	}
	$bits['p'][$y * $bits['w'] + $x] = "\x01";
}

function etiqueta10_load_bmp($path, $threshold = 160)
{
	$bin = @file_get_contents($path);
	if ($bin === false || strlen($bin) < 54) {
		return array('ok' => false, 'error' => 'bmp ilegible');
	}
	if (substr($bin, 0, 2) !== 'BM') {
		return array('ok' => false, 'error' => 'no es BMP');
	}
	$offBits = etiqueta10_u32le($bin, 10);
	$hdrSize = etiqueta10_u32le($bin, 14);
	$width = etiqueta10_i32le($bin, 18);
	$heightRaw = etiqueta10_i32le($bin, 22);
	$topDown = $heightRaw < 0;
	$height = abs($heightRaw);
	$bitCount = etiqueta10_u16le($bin, 28);
	$compr = ($hdrSize >= 20) ? etiqueta10_u32le($bin, 30) : 0;
	if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000) {
		return array('ok' => false, 'error' => 'BMP fuera de rango');
	}
	if ($compr !== 0 && !($bitCount === 32 && $compr === 3)) {
		return array('ok' => false, 'error' => 'BMP comprimido no soportado');
	}
	$rowSize = ((int)(($bitCount * $width + 31) / 32)) * 4;
	$pal = '';
	$palCount = 0;
	if ($bitCount <= 8) {
		$palOff = 14 + $hdrSize;
		$clrUsed = ($hdrSize >= 36) ? etiqueta10_u32le($bin, 46) : 0;
		$palCount = $clrUsed > 0 ? $clrUsed : (1 << $bitCount);
		$pal = substr($bin, $palOff, $palCount * 4);
	}
	$bits = etiqueta10_new_bits($width, $height);
	for ($y = 0; $y < $height; $y++) {
		$srcY = $topDown ? $y : ($height - 1 - $y);
		$rowOff = $offBits + $srcY * $rowSize;
		if ($rowOff + $rowSize > strlen($bin)) {
			break;
		}
		if ($bitCount === 24 || $bitCount === 32) {
			$bpp = ($bitCount === 32) ? 4 : 3;
			for ($x = 0; $x < $width; $x++) {
				$p = $rowOff + $x * $bpp;
				$b = ord($bin[$p]);
				$g = ord($bin[$p + 1]);
				$r = ord($bin[$p + 2]);
				if (etiqueta10_luma($r, $g, $b) < $threshold) {
					etiqueta10_set_black($bits, $x, $y);
				}
			}
		} elseif ($bitCount === 8) {
			for ($x = 0; $x < $width; $x++) {
				$idx = ord($bin[$rowOff + $x]);
				$po = $idx * 4;
				if ($po + 2 >= strlen($pal)) {
					continue;
				}
				$b = ord($pal[$po]);
				$g = ord($pal[$po + 1]);
				$r = ord($pal[$po + 2]);
				if (etiqueta10_luma($r, $g, $b) < $threshold) {
					etiqueta10_set_black($bits, $x, $y);
				}
			}
		} elseif ($bitCount === 1) {
			$black0 = true;
			$black1 = false;
			if (strlen($pal) >= 8) {
				$black0 = etiqueta10_luma(ord($pal[2]), ord($pal[1]), ord($pal[0])) < $threshold;
				$black1 = etiqueta10_luma(ord($pal[6]), ord($pal[5]), ord($pal[4])) < $threshold;
			}
			for ($x = 0; $x < $width; $x++) {
				$byte = ord($bin[$rowOff + (int)($x / 8)]);
				$bit = ($byte >> (7 - ($x % 8))) & 1;
				if ($bit ? $black1 : $black0) {
					etiqueta10_set_black($bits, $x, $y);
				}
			}
		} else {
			return array('ok' => false, 'error' => 'BMP ' . $bitCount . ' bpp no soportado');
		}
	}
	return array('ok' => true, 'bits' => $bits);
}

function etiqueta10_load_gd($path, $threshold = 160)
{
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	$im = null;
	if ($ext === 'png' && function_exists('imagecreatefrompng')) {
		$im = @imagecreatefrompng($path);
	} elseif (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) {
		$im = @imagecreatefromjpeg($path);
	} elseif ($ext === 'bmp' && function_exists('imagecreatefrombmp')) {
		$im = @imagecreatefrombmp($path);
	}
	if (!$im) {
		return array('ok' => false, 'error' => 'GD no pudo abrir la imagen');
	}
	$w = imagesx($im);
	$h = imagesy($im);
	$bits = etiqueta10_new_bits($w, $h);
	for ($y = 0; $y < $h; $y++) {
		for ($x = 0; $x < $w; $x++) {
			$rgb = imagecolorat($im, $x, $y);
			if (imageistruecolor($im)) {
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
			} else {
				$c = imagecolorsforindex($im, $rgb);
				$r = $c['red'];
				$g = $c['green'];
				$b = $c['blue'];
			}
			if (etiqueta10_luma($r, $g, $b) < $threshold) {
				etiqueta10_set_black($bits, $x, $y);
			}
		}
	}
	imagedestroy($im);
	return array('ok' => true, 'bits' => $bits);
}

function etiqueta10_load_image($path)
{
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	if ($ext === 'bmp') {
		$r = etiqueta10_load_bmp($path);
		if ($r['ok']) {
			return $r;
		}
		$gd = etiqueta10_load_gd($path);
		if ($gd['ok']) {
			return $gd;
		}
		return $r;
	}
	return etiqueta10_load_gd($path);
}

function etiqueta10_resize_contain($bits, $dw, $dh)
{
	$sw = $bits['w'];
	$sh = $bits['h'];
	$dw = max(1, (int)$dw);
	$dh = max(1, (int)$dh);
	$scale = min($dw / $sw, $dh / $sh);
	$nw = max(1, (int)round($sw * $scale));
	$nh = max(1, (int)round($sh * $scale));
	$out = etiqueta10_new_bits($nw, $nh);
	for ($y = 0; $y < $nh; $y++) {
		$sy = (int)min($sh - 1, floor($y * $sh / $nh));
		for ($x = 0; $x < $nw; $x++) {
			$sx = (int)min($sw - 1, floor($x * $sw / $nw));
			if ($bits['p'][$sy * $sw + $sx] !== "\x00") {
				etiqueta10_set_black($out, $x, $y);
			}
		}
	}
	return $out;
}

/** Estira al rectángulo exacto (como PaintPicture del VB6). Así x/y de lbl_lines coinciden. */
function etiqueta10_resize_stretch($bits, $dw, $dh)
{
	$sw = $bits['w'];
	$sh = $bits['h'];
	$dw = max(1, (int)$dw);
	$dh = max(1, (int)$dh);
	$out = etiqueta10_new_bits($dw, $dh);
	for ($y = 0; $y < $dh; $y++) {
		$sy = (int)min($sh - 1, floor($y * $sh / $dh));
		for ($x = 0; $x < $dw; $x++) {
			$sx = (int)min($sw - 1, floor($x * $sw / $dw));
			if ($bits['p'][$sy * $sw + $sx] !== "\x00") {
				etiqueta10_set_black($out, $x, $y);
			}
		}
	}
	return $out;
}

function etiqueta10_to_gfa($bits)
{
	$w = $bits['w'];
	$h = $bits['h'];
	$bpr = (int)ceil($w / 8.0);
	$data = '';
	for ($y = 0; $y < $h; $y++) {
		$rowOff = $y * $w;
		for ($bx = 0; $bx < $bpr; $bx++) {
			$byte = 0;
			for ($bit = 0; $bit < 8; $bit++) {
				$x = $bx * 8 + $bit;
				$on = ($x < $w && $bits['p'][$rowOff + $x] !== "\x00") ? 1 : 0;
				$byte = ($byte << 1) | $on;
			}
			$data .= chr($byte);
		}
	}
	$total = strlen($data);
	return array(
		'hex' => strtoupper(bin2hex($data)),
		'total' => $total,
		'bpr' => $bpr,
		'w' => $w,
		'h' => $h,
	);
}

function etiqueta10_parse_printer_size($printer)
{
	$printer = (string)$printer;
	if (preg_match('/(\d+(?:\.\d+)?)x(\d+(?:\.\d+)?)/i', $printer, $m)) {
		$a = (float)$m[1];
		$b = (float)$m[2];
		if ($a > 0 && $b > 0 && $a <= 12 && $b <= 12) {
			return array(
				'w' => etiqueta10_inches_to_dots($a),
				'h' => etiqueta10_inches_to_dots($b),
			);
		}
	}
	return null;
}

function etiqueta10_lookup_lbls($link, $codigo)
{
	$out = array();
	if (!$link) {
		return $out;
	}
	$code = mysqli_real_escape_string($link, etiqueta10_pad_codigo($codigo));
	$res = mysqli_query($link, "SELECT * FROM lbls WHERE id = '" . $code . "' LIMIT 1");
	if ($res && ($row = mysqli_fetch_assoc($res))) {
		$out = $row;
		mysqli_free_result($res);
	} elseif ($res) {
		mysqli_free_result($res);
	}
	return $out;
}

function etiqueta10_lookup_lines($link, $codigo)
{
	$lines = array();
	if (!$link) {
		return $lines;
	}
	$code = mysqli_real_escape_string($link, etiqueta10_pad_codigo($codigo));
	$res = mysqli_query($link, "SELECT * FROM lbl_lines WHERE lblid = '" . $code . "' ORDER BY id ASC");
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$lines[] = $row;
		}
		mysqli_free_result($res);
	}
	return $lines;
}

function etiqueta10_font_dots($pt)
{
	$h = (int)round(((float)$pt) * 203.0 / 72.0);
	return $h < 10 ? 10 : $h;
}

function etiqueta10_lote_code($ts)
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

function etiqueta10_parse_fecha($fecha)
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

function etiqueta10_mb_len($s)
{
	return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function etiqueta10_mb_sub($s, $start, $len = null)
{
	if (function_exists('mb_substr')) {
		return $len === null ? mb_substr($s, $start, null, 'UTF-8') : mb_substr($s, $start, $len, 'UTF-8');
	}
	return $len === null ? substr($s, $start) : substr($s, $start, $len);
}

function etiqueta10_wrap_field($text, $alcance, $maxLines = 4)
{
	$text = trim(preg_replace('/\s+/', ' ', (string)$text));
	$alcance = (int)$alcance;
	if ($alcance < 1) {
		return array($text);
	}
	$lines = array();
	while ($text !== '' && count($lines) < $maxLines) {
		$len = etiqueta10_mb_len($text);
		if ($len <= $alcance) {
			$lines[] = $text;
			break;
		}
		$chunk = etiqueta10_mb_sub($text, 0, $alcance);
		$sp = function_exists('mb_strrpos') ? mb_strrpos($chunk, ' ', 0, 'UTF-8') : strrpos($chunk, ' ');
		if ($sp === false || $sp < 1) {
			$sp = $alcance;
		}
		$lines[] = trim(etiqueta10_mb_sub($text, 0, $sp));
		$text = trim(etiqueta10_mb_sub($text, $sp));
	}
	return $lines;
}
/**
 * Sesgo empirico en coords de lbl_lines (twips).
 * En pruebas, itemid x=100 quedaba corto; con 300 caia bien (+200).
 */
function etiqueta10_line_x_bias_twips()
{
	return 200;
}

function etiqueta10_line_y_bias_twips()
{
	return 0;
}

/** Rectangulo con poca tinta en buffer 1-bit. */
function etiqueta10_region_clear($bits, $x, $y, $rw, $rh, $maxInk = 0.12)
{
	if (!$bits || empty($bits['p'])) {
		return true;
	}
	$w = (int)$bits['w'];
	$h = (int)$bits['h'];
	$x = max(0, (int)$x);
	$y = max(0, (int)$y);
	$rw = max(1, (int)$rw);
	$rh = max(1, (int)$rh);
	if ($x >= $w || $y >= $h) {
		return false;
	}
	if ($x + $rw > $w) {
		$rw = $w - $x;
	}
	if ($y + $rh > $h) {
		$rh = $h - $y;
	}
	if ($rw < 4 || $rh < 6) {
		return false;
	}
	$ink = 0;
	$n = 0;
	$ys = max(1, (int)floor($rh / 8));
	$xs = max(1, (int)floor($rw / 10));
	for ($yy = $y; $yy < $y + $rh; $yy += $ys) {
		$off = $yy * $w;
		for ($xx = $x; $xx < $x + $rw; $xx += $xs) {
			$n++;
			if ($bits['p'][$off + $xx] !== "\x00") {
				$ink++;
			}
		}
	}
	return $n > 0 && ($ink / (float)$n) <= $maxInk;
}

/**
 * Ancla lbl_lines; si cae sobre arte, mueve Y al hueco libre (sin pisar barcode).
 * @return array{0:int,1:int}
 */
function etiqueta10_place_text_xy($labelBits, $x, $y, $tw, $th, $pw, $ll, $barTop)
{
	$tw = max(24, (int)$tw);
	$th = max(10, (int)$th);
	$x = max(0, min((int)$x, $pw - 8));
	$y = max(0, (int)$y);
	if ($barTop > 0 && $y + $th > $barTop - 2) {
		$y = max(0, $barTop - $th - 2);
	}
	if ($y + $th > $ll) {
		$y = max(0, $ll - $th);
	}
	if (etiqueta10_region_clear($labelBits, $x, $y, $tw, $th)) {
		return array($x, $y);
	}
	$deltas = array(0, -8, -16, -24, -36, -48, -64, -80, 8, 16, 24, 36, 48);
	$best = null;
	$bestScore = -1;
	foreach ($deltas as $dy) {
		$yy = $y + $dy;
		if ($yy < 0 || $yy + $th > $ll) {
			continue;
		}
		if ($barTop > 0 && $yy + $th > $barTop - 2) {
			continue;
		}
		if (!etiqueta10_region_clear($labelBits, $x, $yy, $tw, $th, 0.14)) {
			continue;
		}
		$score = 1000 - abs($dy) + (int)($yy * 0.5);
		if ($score > $bestScore) {
			$bestScore = $score;
			$best = $yy;
		}
	}
	if ($best !== null) {
		return array($x, (int)$best);
	}
	return array($x, $y);
}

function etiqueta10_compose_label_bits($artBits, $ox, $oy, $pw, $ll)
{
	$out = etiqueta10_new_bits($pw, $ll);
	$aw = (int)$artBits['w'];
	$ah = (int)$artBits['h'];
	for ($y = 0; $y < $ah; $y++) {
		$ty = $oy + $y;
		if ($ty < 0 || $ty >= $ll) {
			continue;
		}
		for ($x = 0; $x < $aw; $x++) {
			$tx = $ox + $x;
			if ($tx < 0 || $tx >= $pw) {
				continue;
			}
			if ($artBits['p'][$y * $aw + $x] !== "\x00") {
				etiqueta10_set_black($out, $tx, $ty);
			}
		}
	}
	return $out;
}

/**
 * Textos lbl_lines: coords BD (+ sesgo); si pisan BMP, se acomodan al hueco.
 * @param array|null $labelBits arte compuesto al tamano etiqueta
 * @param int $barTop Y barcode dots (0 = sin barra)
 * @param array|null $overlaysOut si se pasa, guarda {x,y,h,text} para preview PNG
 */
function etiqueta10_append_lbl_lines(&$zpl, $lines, $ctx, $pw, $ll, $labelBits = null, $barTop = 0, &$overlaysOut = null)
{
	if (!is_array($lines) || count($lines) === 0) {
		return;
	}
	$biasX = etiqueta10_line_x_bias_twips();
	$biasY = etiqueta10_line_y_bias_twips();
	$itemid = isset($ctx['itemid']) ? etiqueta10_pad_codigo($ctx['itemid']) : '';
	$precio = isset($ctx['precio']) ? (float)$ctx['precio'] : 0;
	$reg = isset($ctx['reg_sanitario']) ? (string)$ctx['reg_sanitario'] : '';
	$expir = isset($ctx['expir']) ? (int)$ctx['expir'] : 0;
	$base = etiqueta10_parse_fecha(isset($ctx['fecha_manufact']) ? $ctx['fecha_manufact'] : '');
	if ($base === false) {
		$base = time();
	}
	$diaexp = ($expir != 0) ? date('d/m/Y', strtotime('+' . $expir . ' days', $base)) : '';
	$collect = is_array($overlaysOut);

	foreach ($lines as $line) {
		$field = isset($line['descrip']) ? strtolower(trim((string)$line['descrip'])) : '';
		$titulo = isset($line['titulo']) ? (string)$line['titulo'] : '';
		$xTw = (float)(isset($line['x']) ? $line['x'] : 0) + $biasX;
		$yTw = (float)(isset($line['y']) ? $line['y'] : 0) + $biasY;
		$x = etiqueta10_twips_to_dots($xTw);
		$y = etiqueta10_twips_to_dots($yTw);
		$h = etiqueta10_font_dots(isset($line['font']) ? $line['font'] : 8);
		$w = (int)round($h * 0.55);
		if ($w < 8) {
			$w = 8;
		}
		if ($x < 0) {
			$x = 0;
		}
		if ($y < 0 || $y >= $ll) {
			continue;
		}

		$text = '';
		$extraLote = '';
		if ($field === 'descripcion' || $field === 'ingredientes' || $field === 'especif' || $field === 'obs') {
			continue;
		} elseif ($field === 'itemid') {
			$text = $titulo . '[' . $itemid . ']';
		} elseif ($field === 'precio') {
			if ($precio == 0.0) {
				continue;
			}
			$text = $titulo . number_format($precio, 2, '.', ',');
		} elseif ($field === 'fecha') {
			if ($expir == 0 || $diaexp === '') {
				continue;
			}
			$text = $titulo . $diaexp;
			$extraLote = 'Lote: ' . etiqueta10_lote_code($base);
		} elseif ($field === 'reg_sanitario') {
			if ($reg === '' || $reg === '0') {
				continue;
			}
			$text = $titulo . $reg;
		} else {
			continue;
		}

		$text = trim($text);
		if ($text === '') {
			continue;
		}

		$estW = (int)max(40, strlen($text) * $w * 0.62);
		if ($x + 20 > $pw) {
			$x = max(0, $pw - min($estW, 80));
		}
		$blockH = $h + (($extraLote !== '') ? ($h + 4) : 0);
		$xy = etiqueta10_place_text_xy($labelBits, $x, $y, $estW, $blockH, $pw, $ll, $barTop);
		$x = $xy[0];
		$y = $xy[1];

		$zpl .= "^FO" . $x . "," . $y . "^A0N," . $h . "," . $w . "^FD" . zpl_escape_field($text) . "^FS\n";
		if ($collect) {
			$overlaysOut[] = array('x' => $x, 'y' => $y, 'h' => $h, 'text' => $text);
		}
		if ($extraLote !== '') {
			$yLote = $y + $h + 2;
			if ($yLote + $h < $ll && ($barTop <= 0 || $yLote + $h <= $barTop - 2)) {
				$zpl .= "^FO" . $x . "," . $yLote . "^A0N," . $h . "," . $w . "^FD" . zpl_escape_field($extraLote) . "^FS\n";
				if ($collect) {
					$overlaysOut[] = array('x' => $x, 'y' => $yLote, 'h' => $h, 'text' => $extraLote);
				}
			}
		}
	}
}

/**
 * @param string $barcode_data Codigo Code128 (itemid o itemid2). Vacio = $codigo.
 * @param array  $ctx Datos variables + opcional lines
 * @param resource|null $link MySQL lbl_lines
 */
function build_zpl_etiqueta_10($codigo, $cant = 1, $lbls = array(), $barcode_data = '', $ctx = array(), $link = null)
{
	$codigo = etiqueta10_pad_codigo($codigo);
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$barData = trim((string)$barcode_data);
	if ($barData === '') {
		$barData = $codigo;
	} else {
		$barDataDigits = preg_replace('/\D/', '', $barData);
		if ($barDataDigits !== '') {
			$barData = $barDataDigits;
		}
	}
	$path = etiqueta10_find_image($codigo);
	if ($path === '') {
		return array('ok' => false, 'error' => 'No hay imagen en printserver para ' . $codigo);
	}
	$loaded = etiqueta10_load_image($path);
	if (empty($loaded['ok'])) {
		return array('ok' => false, 'error' => isset($loaded['error']) ? $loaded['error'] : 'no se pudo leer imagen', 'path' => $path);
	}

	$printer = isset($lbls['printer']) ? trim((string)$lbls['printer']) : '';
	if (!empty($lbls['width']) && !empty($lbls['height'])) {
		$pw = max(1, etiqueta10_twips_to_dots($lbls['width']));
		$ll = max(1, etiqueta10_twips_to_dots($lbls['height']));
		if ($pw < $ll && $ll / $pw > 1.6) {
			$tmp = $pw;
			$pw = $ll;
			$ll = $tmp;
		}
	} else {
		$size = etiqueta10_parse_printer_size($printer);
		if ($size) {
			$pw = $size['w'];
			$ll = $size['h'];
		} else {
			$pw = $loaded['bits']['w'];
			$ll = $loaded['bits']['h'];
		}
	}

	$dx = !empty($lbls['design_x']) ? etiqueta10_twips_to_dots($lbls['design_x']) : 0;
	$dy = !empty($lbls['design_y']) ? etiqueta10_twips_to_dots($lbls['design_y']) : 0;
	$dw = !empty($lbls['design_width']) ? etiqueta10_twips_to_dots($lbls['design_width']) : $pw;
	$dh = !empty($lbls['design_height']) ? etiqueta10_twips_to_dots($lbls['design_height']) : $ll;
	if ($dx < 0) {
		$dx = 0;
	}
	if ($dy < 0) {
		$dy = 0;
	}
	if ($dw < 8) {
		$dw = $pw;
	}
	if ($dh < 8) {
		$dh = $ll;
	}

	$bx = isset($lbls['bar_x']) && $lbls['bar_x'] !== '' && $lbls['bar_x'] !== null
		? etiqueta10_twips_to_dots($lbls['bar_x']) : -1;
	$by = isset($lbls['bar_y']) && $lbls['bar_y'] !== '' && $lbls['bar_y'] !== null
		? etiqueta10_twips_to_dots($lbls['bar_y']) : -1;
	$bw = !empty($lbls['bar_width']) ? etiqueta10_twips_to_dots($lbls['bar_width']) : 0;
	$bh = !empty($lbls['bar_height']) ? etiqueta10_twips_to_dots($lbls['bar_height']) : 0;
	$useBar = ($bx >= 0 && $by >= 0 && $bw > 20 && $bh > 10 && $by < $ll);

	// Diseño usa solo design_* (no comprimir/estirar el BMP al mover bar_y).
	// Antes se recortaba dh = bar_y - design_y y eso "apiñaba" la imagen al subir la barra.
	if ($dw > $pw - $dx) {
		$dw = max(8, $pw - $dx);
	}
	if ($dh > $ll - $dy) {
		$dh = max(8, $ll - $dy);
	}
	$maxDim = max($pw, $ll) * 3;
	if ($dw > $maxDim) {
		$dw = $maxDim;
	}
	if ($dh > $maxDim) {
		$dh = $maxDim;
	}

	$fitted = etiqueta10_resize_stretch($loaded['bits'], $dw, $dh);
	$gfa = etiqueta10_to_gfa($fitted);
	$labelBits = etiqueta10_compose_label_bits($fitted, $dx, $dy, $pw, $ll);

	$zpl = "^XA\n^FWN\n^PON\n^CI28\n^PW" . $pw . "\n^LL" . $ll . "\n^LH0,0\n";
	$zpl .= "^FO" . $dx . "," . $dy . "^GFA," . $gfa['total'] . "," . $gfa['total'] . "," . $gfa['bpr'] . "," . $gfa['hex'] . "^FS\n";

	$barTop = 0;
	$barMod = 2; // fijo como ^BY2 de la impresora (no estirar al bar_width)
	if ($useBar) {
		if ($bx + $bw > $pw) {
			$bw = $pw - $bx;
		}
		if ($by + $bh > $ll) {
			$bh = $ll - $by - 2;
		}
		if ($bh < 18) {
			$bh = 18;
		}
		if ($bw > 30 && $bh > 12) {
			$barTop = $by;
			$zpl .= "^FO" . $bx . "," . $by . "^BY2,2," . $bh . "\n";
			$zpl .= "^BCN," . $bh . ",N,N,N\n^FD" . $barData . "^FS\n";
		}
	}

	if (!isset($ctx['itemid'])) {
		$ctx['itemid'] = $codigo;
	}
	$lines = array();
	if (isset($ctx['lines']) && is_array($ctx['lines'])) {
		$lines = $ctx['lines'];
	} elseif ($link) {
		$lines = etiqueta10_lookup_lines($link, $codigo);
	}
	$overlays = array();
	if (!empty($GLOBALS['etiqueta10_want_preview_bits'])) {
		etiqueta10_append_lbl_lines($zpl, $lines, $ctx, $pw, $ll, $labelBits, $barTop, $overlays);
	} else {
		etiqueta10_append_lbl_lines($zpl, $lines, $ctx, $pw, $ll, $labelBits, $barTop);
	}

	$zpl .= "^PQ" . $cant . "\n^XZ\n";
	$out = array(
		'ok' => true,
		'zpl' => $zpl,
		'path' => $path,
		'pw' => $pw,
		'll' => $ll,
		'printer' => $printer,
		'img_w' => $loaded['bits']['w'],
		'img_h' => $loaded['bits']['h'],
		'bias_x' => etiqueta10_line_x_bias_twips(),
		'bias_y' => etiqueta10_line_y_bias_twips(),
		'lines' => count($lines),
	);
	if (!empty($GLOBALS['etiqueta10_want_preview_bits'])) {
		$out['label_bits'] = $labelBits;
		$out['overlays'] = $overlays;
		$out['bar_box'] = array(
			'use' => $useBar && $barTop > 0,
			'x' => $bx,
			'y' => $by,
			'w' => $bw,
			'h' => $bh,
			'data' => $barData,
			'module' => $barMod,
		);
	}
	return $out;
}

function etiqueta10_preview_font_file()
{
	$cands = array(
		'C:\\Windows\\Fonts\\arial.ttf',
		'C:\\Windows\\Fonts\\Arial.ttf',
		'C:\\Windows\\Fonts\\tahoma.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
	);
	foreach ($cands as $f) {
		if (is_file($f)) {
			return $f;
		}
	}
	return '';
}

function etiqueta10_preview_draw_text($im, $x, $y, $hDots, $text, $color)
{
	$text = (string)$text;
	if ($text === '') {
		return;
	}
	$fontFile = etiqueta10_preview_font_file();
	$size = max(7, (int)round($hDots * 0.70));
	if ($fontFile !== '' && function_exists('imagettftext')) {
		imagettftext($im, $size, 0, (int)$x, (int)($y + $size), $color, $fontFile, $text);
		return;
	}
	$font = 2;
	if ($hDots >= 20) {
		$font = 5;
	} elseif ($hDots >= 14) {
		$font = 4;
	} elseif ($hDots >= 11) {
		$font = 3;
	}
	imagestring($im, $font, (int)$x, (int)$y, $text, $color);
}

/**
 * Patrones Code128 (B) — 11 modulos por simbolo; 1=barra, 0=espacio.
 * Solo para preview PNG (la impresion ZPL sigue con ^BC).
 */
function etiqueta10_code128_patterns()
{
	static $p = null;
	if ($p !== null) {
		return $p;
	}
	$p = array(
		'11011001100', '11001101100', '11001100110', '10010011000', '10010001100',
		'10001001100', '10011001000', '10011000100', '10001100100', '11001001000',
		'11001000100', '11000100100', '10110011100', '10011011100', '10011001110',
		'10111001100', '10011101100', '10011100110', '11001110010', '11001011100',
		'11001001110', '11011100100', '11001110100', '11101101110', '11101001100',
		'11100101100', '11100100110', '11101100100', '11100110100', '11100110010',
		'11011011000', '11011000110', '11000110110', '10100011000', '10001011000',
		'10001000110', '10110001000', '10001101000', '10001100010', '11010001000',
		'11000101000', '11000100010', '10110111000', '10110001110', '10001101110',
		'10111011000', '10111000110', '10001110110', '11101110110', '11010001110',
		'11000101110', '11011101000', '11011100010', '11011101110', '11101011000',
		'11101000110', '11100010110', '11101101000', '11101100010', '11100011010',
		'11101111010', '11001000010', '11110001010', '10100110000', '10100001100',
		'10010110000', '10010000110', '10000101100', '10000100110', '10110010000',
		'10110000100', '10011010000', '10011000010', '10000110100', '10000110010',
		'11000010010', '11001010000', '11110111010', '11000010100', '10001111010',
		'10100111100', '10010111100', '10010011110', '10111100100', '10011110100',
		'10011110010', '11110100100', '11110010100', '11110010010', '11011011110',
		'11011110110', '11110110110', '10101111000', '10100011110', '10001011110',
		'10111101000', '10111100010', '11110101000', '11110100010', '10111011110',
		'10111101110', '11101011110', '11110101110', '11010000100', '11010010000',
		'11010011100', '11000111010',
	);
	return $p;
}

/**
 * @return string secuencia de '1'/'0' (modulos) para Code128-B, o '' si falla
 */
function etiqueta10_code128b_modules($data)
{
	$data = (string)$data;
	if ($data === '') {
		return '';
	}
	$patterns = etiqueta10_code128_patterns();
	$startB = 104;
	$stop = 106;
	$codes = array($startB);
	$sum = $startB;
	$n = strlen($data);
	for ($i = 0; $i < $n; $i++) {
		$c = ord($data[$i]);
		if ($c < 32 || $c > 127) {
			$c = 32;
		}
		$val = $c - 32;
		$codes[] = $val;
		$sum += $val * ($i + 1);
	}
	$codes[] = $sum % 103;
	$codes[] = $stop;
	$out = '';
	foreach ($codes as $idx => $code) {
		if (!isset($patterns[$code])) {
			return '';
		}
		$out .= $patterns[$code];
		// STOP tiene 2 barras extras (13 modulos): patron + "11"
		if ($idx === count($codes) - 1) {
			$out .= '11';
		}
	}
	return $out;
}

/**
 * Dibuja Code128 como la impresora: ^BY2 (modulo 2 dots), alto bar_height.
 * bar_width solo limita el tope; no se estira el codigo.
 */
function etiqueta10_preview_draw_barcode($im, $barBox, $black, $white)
{
	if (!is_array($barBox) || empty($barBox['use'])) {
		return;
	}
	$bx = (int)$barBox['x'];
	$by = (int)$barBox['y'];
	$bw = (int)$barBox['w'];
	$bh = (int)$barBox['h'];
	$data = isset($barBox['data']) ? (string)$barBox['data'] : '';
	$mod = isset($barBox['module']) ? (int)$barBox['module'] : 2;
	if ($mod < 1) {
		$mod = 2;
	}
	// Forzar mismo ancho de modulo que ZPL ^BY2
	$mod = 2;
	if ($bw < 20 || $bh < 8 || $data === '') {
		return;
	}
	$mods = etiqueta10_code128b_modules($data);
	if ($mods === '') {
		return;
	}
	$len = strlen($mods);
	$drawW = $len * $mod;
	if ($drawW < 1) {
		return;
	}
	// No rellenar todo bar_width: solo el ancho natural del Code128
	imagefilledrectangle($im, $bx, $by, $bx + $drawW - 1, $by + $bh - 1, $white);
	for ($i = 0; $i < $len; $i++) {
		if ($mods[$i] !== '1') {
			continue;
		}
		$x1 = $bx + $i * $mod;
		$x2 = $x1 + $mod - 1;
		imagefilledrectangle($im, $x1, $by, $x2, $by + $bh - 1, $black);
	}
}

/**
 * PNG local (sin Labelary) a partir del buffer 1-bit + textos + barcode lbls.
 * @param array $bits
 * @param array $barBox optional x,y,w,h,use,data
 * @param array $overlays list of {x,y,h,text}
 * @return string|false PNG bytes
 */
function etiqueta10_label_bits_to_png($bits, $barBox = null, $overlays = null)
{
	if (!function_exists('imagecreatetruecolor') || empty($bits['p'])) {
		return false;
	}
	$w = (int)$bits['w'];
	$h = (int)$bits['h'];
	if ($w < 1 || $h < 1 || $w > 4000 || $h > 4000) {
		return false;
	}
	$im = imagecreatetruecolor($w, $h);
	if (!$im) {
		return false;
	}
	$white = imagecolorallocate($im, 255, 255, 255);
	$black = imagecolorallocate($im, 0, 0, 0);
	imagefill($im, 0, 0, $white);
	$p = $bits['p'];
	for ($y = 0; $y < $h; $y++) {
		$off = $y * $w;
		for ($x = 0; $x < $w; $x++) {
			if ($p[$off + $x] !== "\x00") {
				imagesetpixel($im, $x, $y, $black);
			}
		}
	}
	etiqueta10_preview_draw_barcode($im, $barBox, $black, $white);
	if (is_array($overlays)) {
		foreach ($overlays as $ov) {
			$tx = isset($ov['x']) ? (int)$ov['x'] : 0;
			$ty = isset($ov['y']) ? (int)$ov['y'] : 0;
			$th = isset($ov['h']) ? (int)$ov['h'] : 12;
			$tt = isset($ov['text']) ? (string)$ov['text'] : '';
			etiqueta10_preview_draw_text($im, $tx, $ty, $th, $tt, $black);
		}
	}
	ob_start();
	imagepng($im);
	$png = ob_get_clean();
	imagedestroy($im);
	return $png !== false && $png !== '' ? $png : false;
}

/** Formato 14 VB6: igual que 10 (BMP) pero barcode = itemid2/codigo2. */
function build_zpl_etiqueta_14_bmp($codigo, $codigo2, $cant = 1, $lbls = array(), $ctx = array(), $link = null)
{
	return build_zpl_etiqueta_10($codigo, $cant, $lbls, $codigo2, $ctx, $link);
}
