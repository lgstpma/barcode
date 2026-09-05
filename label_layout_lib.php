<?php
/**
 * Layouts + datos de producto en JSON local.
 * Esta copia NO escribe MySQL (no toca produccion). Solo lee items/lbls para sembrar.
 *
 * Estructura:
 *   label_layouts/items/{codigo}.json   → por producto (tipicamente tipo 10/14)
 *   label_layouts/shared/tipo_1.json    → layout fijo SoftShop #1
 *   label_layouts/shared/tipo_5.json
 *   label_layouts/shared/tipo_9.json
 */

function label_layouts_root()
{
	return __DIR__ . DIRECTORY_SEPARATOR . 'label_layouts';
}

function label_layouts_items_dir()
{
	$dir = label_layouts_root() . DIRECTORY_SEPARATOR . 'items';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function label_layouts_shared_dir()
{
	$dir = label_layouts_root() . DIRECTORY_SEPARATOR . 'shared';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	return $dir;
}

function label_layout_pad_codigo($codigo)
{
	$codigo = preg_replace('/\D/', '', (string)$codigo);
	if ($codigo === '') {
		$codigo = '0';
	}
	$codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
	if (strlen($codigo) > 6) {
		$codigo = substr($codigo, -6);
	}
	return $codigo;
}

function label_layout_item_path($codigo)
{
	$code = label_layout_pad_codigo($codigo);
	return label_layouts_items_dir() . DIRECTORY_SEPARATOR . $code . '.json';
}

function label_layout_shared_path($tipo)
{
	$tipo = (string)(int)$tipo;
	return label_layouts_shared_dir() . DIRECTORY_SEPARATOR . 'tipo_' . $tipo . '.json';
}

function label_layout_read_json_file($path)
{
	if (!is_file($path)) {
		return null;
	}
	$raw = @file_get_contents($path);
	if ($raw === false || $raw === '') {
		return null;
	}
	$data = json_decode($raw, true);
	return is_array($data) ? $data : null;
}

function label_layout_write_json_file($path, $data)
{
	$dir = dirname($path);
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	$data['updated_at'] = date('c');
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return false;
	}
	return @file_put_contents($path, $json) !== false;
}

/** Defaults compartidos tipo 1 (valores VB SoftShop). */
function label_layout_default_shared_1()
{
	return array(
		'version' => 1,
		'etiqueta' => '1',
		'shared' => true,
		'unit' => 'twips_vb',
		'fields' => array(
			'barcode' => array('x' => 150, 'y' => 10, 'w' => 3500, 'h' => 1000),
			'itemid' => array('x' => 1700, 'y' => 30, 'font' => 5, 'titulo' => ''),
			'precio' => array('x' => 1495, 'y' => 137, 'font' => 9, 'titulo' => 'B/.'),
			'descripcion' => array('x' => 120, 'y' => 325, 'font' => 6, 'alcance' => 24, 'renglon' => 117, 'titulo' => ''),
			'fecha' => array('x' => 150, 'y' => 590, 'font' => 5, 'titulo' => 'EXP:'),
		),
	);
}

function label_layout_default_shared_9()
{
	$d = label_layout_default_shared_1();
	$d['etiqueta'] = '9';
	// Tipo 9 = misma base que 1 sin fecha
	unset($d['fields']['fecha']);
	return $d;
}

function label_layout_default_shared_5()
{
	return array(
		'version' => 1,
		'etiqueta' => '5',
		'shared' => true,
		'unit' => 'twips',
		'fields' => array(
			'barcode' => array('x' => 1, 'y' => 70, 'h' => 350),
			'precio' => array('x' => 2500, 'y' => 60, 'font' => 12, 'titulo' => 'B/.'),
			'itemid' => array('x' => 10, 'y' => 1800, 'font' => 7, 'titulo' => ''),
			'descripcion' => array('x' => 10, 'y' => 400, 'font' => 10, 'alcance' => 33, 'renglon' => 200, 'titulo' => ''),
			'ingredientes' => array('x' => 10, 'y' => 820, 'font' => 10, 'titulo' => ''),
			'fecha' => array('x' => 2100, 'y' => 1800, 'font' => 8, 'titulo' => 'EXP:'),
		),
		'label' => array('width' => 3395, 'height' => 2263),
	);
}

function label_layout_ensure_shared($tipo)
{
	$path = label_layout_shared_path($tipo);
	$existing = label_layout_read_json_file($path);
	if ($existing) {
		return $existing;
	}
	if ($tipo == 1) {
		$d = label_layout_default_shared_1();
	} elseif ($tipo == 5) {
		$d = label_layout_default_shared_5();
	} elseif ($tipo == 9) {
		$d = label_layout_default_shared_9();
	} else {
		$d = array('version' => 1, 'etiqueta' => (string)$tipo, 'shared' => true, 'fields' => array());
	}
	label_layout_write_json_file($path, $d);
	return $d;
}

/**
 * Arma layout de item desde MySQL (lbls + lbl_lines) para sembrar JSON.
 */
function label_layout_seed_from_mysql($link, $codigo)
{
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_10.php');
	$code = label_layout_pad_codigo($codigo);
	$lbls = etiqueta10_lookup_lbls($link, $code);
	$lines = etiqueta10_lookup_lines($link, $code);
	$fields = array();
	foreach ($lines as $line) {
		$key = isset($line['descrip']) ? strtolower(trim((string)$line['descrip'])) : '';
		if ($key === '') {
			continue;
		}
		$fields[$key] = array(
			'x' => isset($line['x']) ? (int)$line['x'] : 0,
			'y' => isset($line['y']) ? (int)$line['y'] : 0,
			'font' => isset($line['font']) ? (float)$line['font'] : 8,
			'bold' => !empty($line['bold']) && $line['bold'] !== 'false' && $line['bold'] !== '0',
			'alcance' => isset($line['alcance']) ? (int)$line['alcance'] : 0,
			'renglon' => isset($line['renglon']) ? (int)$line['renglon'] : 0,
			'titulo' => isset($line['titulo']) ? (string)$line['titulo'] : '',
		);
	}
	$lblKeys = array(
		'printer', 'width', 'height', 'orientacion',
		'bar_x', 'bar_y', 'bar_width', 'bar_height',
		'design_x', 'design_y', 'design_width', 'design_height',
		'FM_bar_x', 'FM_bar_y', 'FM_bar_width', 'FM_bar_height',
	);
	$lblOut = array();
	foreach ($lblKeys as $k) {
		if (isset($lbls[$k]) && $lbls[$k] !== null && $lbls[$k] !== '') {
			$lblOut[$k] = $lbls[$k];
		}
	}
	$item = array();
	if ($link) {
		$codeEsc = mysqli_real_escape_string($link, $code);
		$res = mysqli_query($link, "SELECT * FROM items WHERE codigo='$codeEsc' LIMIT 1");
		if ($res && ($row = mysqli_fetch_assoc($res))) {
			$item = label_item_from_row($row);
			mysqli_free_result($res);
		}
	}
	return array(
		'version' => 1,
		'codigo' => $code,
		'etiqueta' => isset($item['etiqueta']) ? (string)$item['etiqueta'] : '',
		'shared' => false,
		'unit' => 'twips',
		'lbls' => $lblOut,
		'fields' => $fields,
		'item' => $item,
		'from_mysql' => true,
	);
}

function label_item_keys()
{
	return array(
		'codigo', 'codigo2', 'descrip', 'descrip2', 'descrip3',
		'ingredientes', 'especif', 'obs', 'reg_sanitario',
		'precio1', 'precio2', 'etiqueta', 'exp',
	);
}

function label_item_from_row($row)
{
	$out = array();
	if (!is_array($row)) {
		return $out;
	}
	foreach (label_item_keys() as $k) {
		if (array_key_exists($k, $row)) {
			$out[$k] = $row[$k];
		}
	}
	return $out;
}

/** Superpone datos JSON sobre una fila de items (MySQL queda intacto). */
function label_item_apply_to_row($row, $layout)
{
	if (!is_array($row)) {
		$row = array();
	}
	if (!is_array($layout)) {
		return $row;
	}
	if (!empty($layout['item']) && is_array($layout['item'])) {
		foreach ($layout['item'] as $k => $v) {
			$row[$k] = $v;
		}
	}
	if (isset($layout['etiqueta']) && (string)$layout['etiqueta'] !== '') {
		$row['etiqueta'] = $layout['etiqueta'];
	}
	return $row;
}

function label_item_apply_to_lbls($lbls, $layout)
{
	if (!is_array($lbls)) {
		$lbls = array();
	}
	if (is_array($layout) && !empty($layout['lbls']) && is_array($layout['lbls'])) {
		foreach ($layout['lbls'] as $k => $v) {
			$lbls[$k] = $v;
		}
	}
	return $lbls;
}

/** Carga JSON del producto y lo aplica a row + lbls. */
function label_overlay_from_json($link, $codigo, $row, $lbls = array())
{
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php';
	$layout = label_layout_load_item($link, $codigo, true);
	if (!$layout) {
		return array('row' => $row, 'lbls' => $lbls, 'layout' => null);
	}
	return array(
		'row' => label_item_apply_to_row($row, $layout),
		'lbls' => label_item_apply_to_lbls($lbls, $layout),
		'layout' => $layout,
	);
}

/**
 * Guarda producto + geometria + campos de texto en JSON por codigo.
 * No toca MySQL.
 */
function label_layout_save_product($codigo, $item, $lbls = array(), $fields = array(), $etiqueta = '')
{
	$code = label_layout_pad_codigo($codigo);
	$existing = label_layout_read_json_file(label_layout_item_path($code));
	if (!is_array($existing)) {
		$existing = array(
			'version' => 1,
			'codigo' => $code,
			'shared' => false,
			'unit' => 'twips',
			'fields' => array(),
			'lbls' => array(),
			'item' => array(),
		);
	}
	if (!isset($existing['item']) || !is_array($existing['item'])) {
		$existing['item'] = array();
	}
	if (is_array($item)) {
		foreach ($item as $k => $v) {
			$existing['item'][$k] = $v;
		}
	}
	$existing['item']['codigo'] = $code;
	if ($etiqueta !== '') {
		$existing['etiqueta'] = (string)$etiqueta;
		$existing['item']['etiqueta'] = (string)$etiqueta;
	} elseif (isset($existing['item']['etiqueta'])) {
		$existing['etiqueta'] = (string)$existing['item']['etiqueta'];
	}
	if (is_array($lbls) && count($lbls)) {
		if (!isset($existing['lbls']) || !is_array($existing['lbls'])) {
			$existing['lbls'] = array();
		}
		foreach ($lbls as $k => $v) {
			$existing['lbls'][$k] = $v;
		}
	}
	if (is_array($fields) && count($fields)) {
		if (!isset($existing['fields']) || !is_array($existing['fields'])) {
			$existing['fields'] = array();
		}
		foreach ($fields as $name => $f) {
			if (!is_array($f)) {
				continue;
			}
			$existing['fields'][$name] = $f;
		}
	}
	$existing['shared'] = false;
	return label_layout_save_item($code, $existing);
}

/**
 * Carga layout de item: JSON local si existe; si no, siembra desde MySQL y opcionalmente guarda.
 */
function label_layout_load_item($link, $codigo, $autoSeed = true)
{
	$path = label_layout_item_path($codigo);
	$data = label_layout_read_json_file($path);
	if ($data) {
		if ((empty($data['item']) || !is_array($data['item'])) && $link) {
			$seed = label_layout_seed_from_mysql($link, $codigo);
			if (!empty($seed['item'])) {
				$data['item'] = $seed['item'];
				if (empty($data['etiqueta']) && !empty($seed['etiqueta'])) {
					$data['etiqueta'] = $seed['etiqueta'];
				}
				label_layout_save_item($codigo, $data);
			}
		}
		$data['path'] = $path;
		$data['exists'] = true;
		return $data;
	}
	if (!$autoSeed || !$link) {
		return null;
	}
	$data = label_layout_seed_from_mysql($link, $codigo);
	// etiqueta desde items
	$code = label_layout_pad_codigo($codigo);
	$codeEsc = mysqli_real_escape_string($link, $code);
	$res = mysqli_query($link, "SELECT etiqueta FROM items WHERE codigo='$codeEsc' LIMIT 1");
	if ($res && ($row = mysqli_fetch_assoc($res))) {
		$data['etiqueta'] = isset($row['etiqueta']) ? (string)$row['etiqueta'] : '';
		mysqli_free_result($res);
	}
	label_layout_write_json_file($path, $data);
	$data['path'] = $path;
	$data['exists'] = true;
	$data['seeded'] = true;
	return $data;
}

function label_layout_save_item($codigo, $data)
{
	$code = label_layout_pad_codigo($codigo);
	$data['codigo'] = $code;
	$data['shared'] = false;
	$data['version'] = isset($data['version']) ? (int)$data['version'] : 1;
	unset($data['path'], $data['exists'], $data['seeded'], $data['from_mysql']);
	return label_layout_write_json_file(label_layout_item_path($code), $data);
}

/**
 * Aplica JSON sobre arrays lbls + lines (para preview tipo 10/14).
 * @return array{lbls:array,lines:array}
 */
function label_layout_apply_to_lbls_lines($layout, $lbls, $lines)
{
	if (!is_array($layout)) {
		return array('lbls' => $lbls, 'lines' => $lines);
	}
	if (!empty($layout['lbls']) && is_array($layout['lbls'])) {
		foreach ($layout['lbls'] as $k => $v) {
			$lbls[$k] = $v;
		}
	}
	if (!empty($layout['fields']) && is_array($layout['fields'])) {
		$byKey = array();
		foreach ($lines as $i => $line) {
			$key = isset($line['descrip']) ? strtolower(trim((string)$line['descrip'])) : '';
			if ($key !== '') {
				$byKey[$key] = $i;
			}
		}
		foreach ($layout['fields'] as $key => $f) {
			$key = strtolower(trim((string)$key));
			if ($key === '' || !is_array($f)) {
				continue;
			}
			if (isset($byKey[$key])) {
				$i = $byKey[$key];
				foreach (array('x', 'y', 'font', 'bold', 'alcance', 'renglon', 'titulo') as $prop) {
					if (array_key_exists($prop, $f)) {
						$lines[$i][$prop] = $f[$prop];
					}
				}
			} else {
				$lines[] = array(
					'descrip' => $key,
					'x' => isset($f['x']) ? $f['x'] : 0,
					'y' => isset($f['y']) ? $f['y'] : 0,
					'font' => isset($f['font']) ? $f['font'] : 8,
					'bold' => !empty($f['bold']),
					'alcance' => isset($f['alcance']) ? $f['alcance'] : 0,
					'renglon' => isset($f['renglon']) ? $f['renglon'] : 0,
					'titulo' => isset($f['titulo']) ? $f['titulo'] : '',
				);
			}
		}
	}
	return array('lbls' => $lbls, 'lines' => $lines);
}

/**
 * Guarda imagen de diseno en printserver/{codigo}.{ext}
 * @return array{ok:bool,path?:string,error?:string}
 */
function label_layout_save_design_image($codigo, $tmpPath, $origName)
{
	$code = label_layout_pad_codigo($codigo);
	if (!is_file($tmpPath)) {
		return array('ok' => false, 'error' => 'Archivo temporal no encontrado');
	}
	$ext = strtolower(pathinfo((string)$origName, PATHINFO_EXTENSION));
	if ($ext === 'jpeg') {
		$ext = 'jpg';
	}
	$allowed = array('bmp', 'png', 'jpg', 'gif');
	if (!in_array($ext, $allowed, true)) {
		// detectar por contenido
		$bin = @file_get_contents($tmpPath, false, null, 0, 8);
		if ($bin !== false && substr($bin, 0, 2) === 'BM') {
			$ext = 'bmp';
		} elseif ($bin !== false && substr($bin, 0, 8) === "\x89PNG\r\n\x1a\n") {
			$ext = 'png';
		} elseif ($bin !== false && (substr($bin, 0, 3) === "\xff\xd8\xff")) {
			$ext = 'jpg';
		} else {
			return array('ok' => false, 'error' => 'Formato no soportado (use bmp/png/jpg)');
		}
	}
	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'printserver';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}
	$dest = $dir . DIRECTORY_SEPARATOR . $code . '.' . $ext;
	// quitar otras extensiones del mismo codigo para que find_image tome la nueva
	foreach ($allowed as $e) {
		$other = $dir . DIRECTORY_SEPARATOR . $code . '.' . $e;
		if ($e !== $ext && is_file($other)) {
			@unlink($other);
		}
	}
	if (!@move_uploaded_file($tmpPath, $dest)) {
		if (!@copy($tmpPath, $dest)) {
			return array('ok' => false, 'error' => 'No se pudo guardar en printserver');
		}
	}
	return array('ok' => true, 'path' => $dest, 'file' => $code . '.' . $ext);
}
