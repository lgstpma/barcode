<?php
/**
 * Impresión de etiquetas grandes para cajas/paquetes (nachos, galletas, etc.).
 * Encola como etiqueta=cajas → GK420t_2x3 (mismo worker que el resto).
 * Layout: label_layouts/shared/tipo_cajas.json
 */
include("conections.php");
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_cajas.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_report_lib.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');

$link = conec_mysql();

$txt_codigo2 = isset($_POST['txt_codigo2']) ? trim((string)$_POST['txt_codigo2']) : '';
$cant = isset($_POST['cant_caja2']) ? (int)$_POST['cant_caja2'] : 0;
if ($cant < 1) {
	die('Error: indica la cantidad de unidades en la caja.');
}

$caducidad = isset($_POST['caducidad']) ? (int)$_POST['caducidad'] : 0;
if ($caducidad < 0) {
	$caducidad = 0;
}

$elab_day = isset($_POST['elab_day']) ? trim((string)$_POST['elab_day']) : date('Y-m-d');
$copies = isset($_POST['cant']) ? (int)$_POST['cant'] : 1;
if ($copies < 1) {
	$copies = 1;
}

if ($txt_codigo2 === '') {
	die('Error: falta codigo GTIN (codigo2).');
}

$codeEsc = mysqli_real_escape_string($link, $txt_codigo2);
$query_items_info = 'SELECT * FROM items WHERE codigo2 = "' . $codeEsc . '" LIMIT 1';
$result_items_info = mysqli_query($link, $query_items_info);
if (!$result_items_info) {
	die('Error en query: ' . mysqli_error($link));
}
$row_items_info = mysqli_fetch_array($result_items_info);
if (!$row_items_info) {
	$query2 = 'SELECT * FROM items WHERE codigo = "' . $codeEsc . '" LIMIT 1';
	$result2 = mysqli_query($link, $query2);
	$row_items_info = $result2 ? mysqli_fetch_array($result2) : null;
}
if (!$row_items_info) {
	die('No se encontró producto con codigo2/codigo = ' . htmlspecialchars($txt_codigo2));
}

$itemid = isset($row_items_info['codigo']) ? (string)$row_items_info['codigo'] : $txt_codigo2;
$descrip = isset($row_items_info['descrip']) ? $row_items_info['descrip'] : '';
$descrip2 = isset($row_items_info['descrip2']) ? $row_items_info['descrip2'] : '';
$gtin = isset($row_items_info['codigo2']) ? trim((string)$row_items_info['codigo2']) : '';
if ($gtin === '') {
	$gtin = $txt_codigo2;
}

$layoutCajas = label_layout_ensure_shared('cajas');
$zpl = build_zpl_etiqueta_cajas($gtin, $descrip, $descrip2, $cant, $copies, $elab_day, $caducidad, $layoutCajas);
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta_cajas.zpl', $zpl);

$printer = 'GK420t_2x3';
$qid = enqueue_zpl($link, 'cajas', $itemid, $zpl, $printer);
$path = print_migrate_uses_new_queue($printer) ? 'mysql' : 'legacy';
$elab_ts = strtotime($elab_day);
if ($elab_ts === false) {
	$elab_ts = time();
}
$lote = lote_code($elab_ts);
$logoNote = (strpos($zpl, '^GFA,') !== false) ? 'logo embebido' : 'logo no encontrado';

print_report_render(array(
	'codigo' => (string)$itemid,
	'descrip' => trim($descrip . ' ' . $descrip2),
	'tipo' => 'cajas',
	'cant' => (string)$copies,
	'printer' => $printer,
	'qid' => $qid ? (int)$qid : 0,
	'path' => $path,
	'notes' => 'Caja ' . $cant . ' unidades, lote ' . $lote . ', elaboracion ' . date('d/m/y', $elab_ts) . ' (' . $logoNote . '). Cola cajas → GK420t_2x3.',
	'zpl' => preg_replace('/\^GFA,[^\^]+/', '^GFA,[logo]', $zpl),
	'raw_log' => '',
));
