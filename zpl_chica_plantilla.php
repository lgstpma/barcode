<?php
/**
 * Plantilla ZPL chica — SOLO el formato que compartiste (sin VB6 / sin ^PW/^LL).
 *
 * Usada por etiqueta 1, 9 y 15.
 *
 * Márgenes / offset: print_chica_offset.cfg → ^LT / ^LS / ^LH solo en ESTE job.
 * No se guarda configuración en la impresora (sin ^JUS), así VB6 sigue igual.
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

function zpl_chica_offset_cfg_path()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'print_chica_offset.cfg';
}

/**
 * Lee offsets de job (no permanentes en impresora).
 * @return array{lt:int,ls:int,lh_x:int,lh_y:int}
 */
function zpl_chica_load_offsets()
{
	$out = array('lt' => 10, 'ls' => 0, 'lh_x' => 0, 'lh_y' => 0);
	$path = zpl_chica_offset_cfg_path();
	if (!is_readable($path)) {
		return $out;
	}
	$lines = @file($path, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines)) {
		return $out;
	}
	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		if (strpos($line, '=') === false) {
			continue;
		}
		list($k, $v) = array_map('trim', explode('=', $line, 2));
		$k = strtolower($k);
		if ($k === 'lt' || $k === 'ls' || $k === 'lh_x' || $k === 'lh_y') {
			$out[$k] = (int)$v;
		}
	}
	foreach (array('lt', 'ls') as $k) {
		if ($out[$k] < -120) {
			$out[$k] = -120;
		}
		if ($out[$k] > 120) {
			$out[$k] = 120;
		}
	}
	foreach (array('lh_x', 'lh_y') as $k) {
		if ($out[$k] < 0) {
			$out[$k] = 0;
		}
		if ($out[$k] > 400) {
			$out[$k] = 400;
		}
	}
	return $out;
}

function zpl_chica_save_offsets($lt, $ls, $lh_x = 0, $lh_y = 0)
{
	$lt = (int)$lt;
	$ls = (int)$ls;
	$lh_x = (int)$lh_x;
	$lh_y = (int)$lh_y;
	$txt = "# Compensación SOLO jobs nuevos (1/9/15). No afecta VB6. Sin ^JUS.\n";
	$txt .= "# dots @203dpi. lt=vertical (^LT), ls=horizontal (^LS), lh=origen (^LH)\n";
	$txt .= "lt=$lt\nls=$ls\nlh_x=$lh_x\nlh_y=$lh_y\n";
	return @file_put_contents(zpl_chica_offset_cfg_path(), $txt) !== false;
}

/**
 * Geometría editable de la plantilla chica (#1 / #9 / #15).
 * Archivo: label_layouts/shared/tipo_chica.json
 */
function zpl_chica_layout_path()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'label_layouts' . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'tipo_chica.json';
}

function zpl_chica_default_layout()
{
	return array(
		'version' => 1,
		'etiqueta' => 'chica',
		'shared' => true,
		'unit' => 'dots',
		'note' => 'Plantilla ZPL chica compartida por #1 (con fecha), #9 (sin fecha) y #15.',
		'fields' => array(
			'texto' => array(
				'alcance' => 18,
				'font' => 14,
				'x' => 20,
				'y0' => 0,
				'step' => 17,
				'precio_gap' => 2,
			),
			'barcode' => array(
				'x' => 160,
				'h' => 36,
				'by' => 1,
				'y_min' => 42,
				'gap_con_fecha' => 6,
				'gap_sin_fecha' => 16,
			),
		),
	);
}

function zpl_chica_load_layout()
{
	$def = zpl_chica_default_layout();
	$path = zpl_chica_layout_path();
	if (!is_readable($path)) {
		return $def;
	}
	$raw = @file_get_contents($path);
	$data = $raw ? json_decode($raw, true) : null;
	if (!is_array($data)) {
		return $def;
	}
	if (!isset($data['fields']) || !is_array($data['fields'])) {
		$data['fields'] = $def['fields'];
	}
	foreach ($def['fields'] as $name => $vals) {
		if (!isset($data['fields'][$name]) || !is_array($data['fields'][$name])) {
			$data['fields'][$name] = $vals;
			continue;
		}
		foreach ($vals as $k => $v) {
			if (!array_key_exists($k, $data['fields'][$name])) {
				$data['fields'][$name][$k] = $v;
			}
		}
	}
	$data['etiqueta'] = 'chica';
	$data['shared'] = true;
	$data['unit'] = 'dots';
	return $data;
}

function zpl_chica_save_layout(array $layout)
{
	$def = zpl_chica_default_layout();
	$out = $def;
	$out['version'] = isset($layout['version']) ? (int)$layout['version'] : 1;
	$out['updated_at'] = date('c');
	if (isset($layout['fields']) && is_array($layout['fields'])) {
		foreach ($def['fields'] as $name => $vals) {
			if (!isset($layout['fields'][$name]) || !is_array($layout['fields'][$name])) {
				continue;
			}
			foreach ($vals as $k => $v) {
				if (array_key_exists($k, $layout['fields'][$name])) {
					$out['fields'][$name][$k] = (int)$layout['fields'][$name][$k];
				}
			}
		}
	}
	$dir = dirname(zpl_chica_layout_path());
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	return @file_put_contents(zpl_chica_layout_path(), $json . "\n") !== false;
}

function zpl_chica_lote_code($ts)
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

function zpl_chica_parse_fecha($fecha)
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

function zpl_chica_mb_len($s)
{
	return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function zpl_chica_mb_sub($s, $start, $len = null)
{
	if (function_exists('mb_substr')) {
		return $len === null ? mb_substr($s, $start, null, 'UTF-8') : mb_substr($s, $start, $len, 'UTF-8');
	}
	return $len === null ? substr($s, $start) : substr($s, $start, $len);
}

/**
 * Corte SoftShop: retrocede hasta espacio para no partir palabra (como VB alcance/renglón).
 */
function zpl_chica_corte_renglon($cadena, $alcance)
{
	$cadena = (string)$cadena;
	$alcance = (int)$alcance;
	if ($alcance < 1 || zpl_chica_mb_len($cadena) <= $alcance) {
		return 0;
	}
	$char_cont = 0;
	$pos = $alcance;
	$tempstring = zpl_chica_mb_sub($cadena, $pos - 1, 1);
	while ($tempstring !== ' ' && $pos > 1) {
		$char_cont++;
		$pos--;
		if ($char_cont > $alcance) {
			break;
		}
		$tempstring = zpl_chica_mb_sub($cadena, $pos - 1, 1);
	}
	return $char_cont;
}

/**
 * SoftShop #1/#9: descripcion.alcance = 24 → máx. 2 renglones.
 * @return string[]
 */
function zpl_chica_wrap_descrip($descrip, $descrip2 = '', $alcance = 24, $maxLines = 2)
{
	$d1 = trim(preg_replace('/\s+/', ' ', (string)$descrip));
	$d2 = trim(preg_replace('/\s+/', ' ', (string)$descrip2));
	$text = $d1;
	if ($d2 !== '' && stripos($d1, $d2) === false) {
		$text = trim($d1 . ' ' . $d2);
	}
	$text = zpl_escape_field($text);
	$alcance = (int)$alcance;
	if ($alcance < 8) {
		$alcance = 24;
	}
	$maxLines = max(1, (int)$maxLines);
	$lines = array();
	while ($text !== '' && count($lines) < $maxLines) {
		$len = zpl_chica_mb_len($text);
		if ($len <= $alcance || count($lines) === $maxLines - 1) {
			$lines[] = $text;
			$text = '';
			break;
		}
		$cut = zpl_chica_corte_renglon($text, $alcance);
		$take = $alcance - $cut;
		if ($take < 1) {
			$take = $alcance;
		}
		$lines[] = trim(zpl_chica_mb_sub($text, 0, $take));
		$text = trim(zpl_chica_mb_sub($text, $take));
	}
	return $lines;
}

/**
 * @param bool $con_fecha  true = lote/exp/reg (tipos 1 y 15); false = solo nombre+precio+barras (tipo 9)
 * @param bool $con_reg    incluir línea Reg. si hay valor
 */
function build_zpl_chica_plantilla($codigo, $descrip, $descrip2, $precio, $cant, $expir, $fecha_manufact, $reg_sanitario = '', $con_fecha = true, $con_reg = true)
{
	$codigo = str_pad(preg_replace('/\D/', '', (string)$codigo), 6, '0', STR_PAD_LEFT);
	$codigo = substr($codigo, -6);

	$lay = zpl_chica_load_layout();
	$tx = isset($lay['fields']['texto']) ? $lay['fields']['texto'] : array();
	$bc = isset($lay['fields']['barcode']) ? $lay['fields']['barcode'] : array();
	// SoftShop VB alcance=24 era para fuente chica; con ^CFA,14 hay que cortar antes (~18).
	$alcance = isset($tx['alcance']) ? (int)$tx['alcance'] : 18;
	if ($alcance < 8) {
		$alcance = 8;
	}
	if ($alcance > 40) {
		$alcance = 40;
	}
	$fontTxt = isset($tx['font']) ? (int)$tx['font'] : 14;
	if ($fontTxt < 8) {
		$fontTxt = 8;
	}
	if ($fontTxt > 28) {
		$fontTxt = 28;
	}
	$foX = isset($tx['x']) ? (int)$tx['x'] : 20;
	$step = isset($tx['step']) ? (int)$tx['step'] : 17;
	if ($step < 10) {
		$step = 10;
	}
	$y0 = isset($tx['y0']) ? (int)$tx['y0'] : 0;
	$precioGap = isset($tx['precio_gap']) ? (int)$tx['precio_gap'] : 2;
	$barX = isset($bc['x']) ? (int)$bc['x'] : 160;
	$barH = isset($bc['h']) ? (int)$bc['h'] : 36;
	$barBy = isset($bc['by']) ? (int)$bc['by'] : 1;
	if ($barBy < 1) {
		$barBy = 1;
	}
	$yBarMin = isset($bc['y_min']) ? (int)$bc['y_min'] : 42;
	$gapFecha = isset($bc['gap_con_fecha']) ? (int)$bc['gap_con_fecha'] : 6;
	$gapSin = isset($bc['gap_sin_fecha']) ? (int)$bc['gap_sin_fecha'] : 16;

	$descLines = zpl_chica_wrap_descrip($descrip, $descrip2, $alcance, 2);
	if (count($descLines) < 1) {
		$descLines = array('');
	}
	// Segunda línea: no dejar que se desborde otra vez
	if (isset($descLines[1]) && zpl_chica_mb_len($descLines[1]) > $alcance) {
		$descLines[1] = trim(zpl_chica_mb_sub($descLines[1], 0, $alcance));
	}

	$precio = (float)$precio;
	$priceTxt = number_format($precio, 2, '.', '');

	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}

	$off = zpl_chica_load_offsets();
	$lt = (int)$off['lt'];
	$ls = (int)$off['ls'];
	$lhx = (int)$off['lh_x'];
	$lhy = (int)$off['lh_y'];

	$nDesc = count($descLines);
	$y1 = $y0 + $step;
	$yPrecio = ($nDesc >= 2 ? $y1 : $y0) + $step + $precioGap;
	$yLote = $yPrecio + $step;
	$yExp = $yLote + $step;
	$yReg = $yExp + $step;
	$yBar = $con_fecha ? ($yReg + $gapFecha) : ($yPrecio + $gapSin);
	if ($yBar < $yBarMin) {
		$yBar = $yBarMin;
	}

	$zpl = "^XA\n";
	$zpl .= "^FX chica #1/#9 wrap=$alcance step=$step nDesc=$nDesc\n";
	$zpl .= "^LH" . $lhx . "," . $lhy . "\n";
	$zpl .= "^LS" . $ls . "\n";
	$zpl .= "^AD,54\n";
	$zpl .= "^CFA,12\n";
	$zpl .= "^LT" . $lt . "\n";
	$zpl .= "^CWZ,E:LEXENDDECA.TTF\n";
	$zpl .= "^FO" . $barX . "," . (int)$yBar . "\n";
	$zpl .= "^BY" . $barBy . "\n";
	$zpl .= "^BCN," . $barH . ",N,N,N\n";
	$zpl .= "^FD" . $codigo . "^FS\n";
	$zpl .= "^CFA," . $fontTxt . "\n";
	$zpl .= "^FO" . $foX . "," . (int)$y0 . "^FD" . $descLines[0] . "^FS\n";
	if ($nDesc >= 2 && $descLines[1] !== '') {
		$zpl .= "^FO" . $foX . "," . (int)$y1 . "^FD" . $descLines[1] . "^FS\n";
	}
	$zpl .= "^FO" . $foX . "," . (int)$yPrecio . "^FDPrecio:" . zpl_escape_field($priceTxt) . "^FS\n";

	if ($con_fecha) {
		$base = zpl_chica_parse_fecha($fecha_manufact);
		if ($base === false) {
			$base = time();
		}
		$lote = zpl_chica_lote_code($base);
		$expir = (int)$expir;
		$expTxt = '';
		if ($expir > 0) {
			$expTxt = date('d/m/y', strtotime('+' . $expir . ' days', $base));
		}

		$zpl .= "^FO20," . (int)$yLote . "^FD#Lote:" . zpl_escape_field($lote) . "^FS\n";
		if ($expTxt !== '') {
			$zpl .= "^FO20," . (int)$yExp . "^FDExp.:" . zpl_escape_field($expTxt) . "^FS\n";
		}

		if ($con_reg) {
			$reg = zpl_escape_field(trim((string)$reg_sanitario));
			if ($reg !== '') {
				$regLine = (stripos($reg, 'Reg.') === 0) ? $reg : ('Reg.:' . $reg);
				$zpl .= "^FO20," . (int)$yReg . "^FD" . $regLine . "^FS\n";
			}
		}
	}

	$zpl .= "^PQ" . $cant . "\n";
	$zpl .= "^XZ\n";
	return $zpl;
}
