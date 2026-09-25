<?php
/**
 * Impresión GTI 13 — caja unidades 3"×2" (Supermercado Rey).
 * Botón "Imprimir GTI 13" (NO es items.etiqueta #13 ni GTIN SoftShop).
 * Encola como etiqueta=gti13 → GK420t_2x3.
 */
include("conections.php");
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_gti13.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_report_lib.php');

$link = conec_mysql();

$txt_codigo2 = isset($_POST['txt_codigo2']) ? trim((string)$_POST['txt_codigo2']) : '';
$unidades = isset($_POST['unidades']) ? (int)$_POST['unidades'] : 0;
if ($unidades < 1) {
	die('Error: indica las unidades de la caja.');
}

$cant = isset($_POST['cant_gti13']) ? (int)$_POST['cant_gti13'] : (isset($_POST['cant2']) ? (int)$_POST['cant2'] : 1);
if ($cant < 1) {
	$cant = 1;
}

$elab_day = isset($_POST['elab_day']) ? trim((string)$_POST['elab_day']) : date('Y-m-d');
$expir = 0;
if (isset($_POST['caducidad1']) && $_POST['caducidad1'] !== '') {
	$expir = (int)$_POST['caducidad1'];
} elseif (isset($_POST['caducidad']) && $_POST['caducidad'] !== '') {
	$expir = (int)$_POST['caducidad'];
}
if ($expir < 0) {
	$expir = 0;
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
$descrip3 = isset($row_items_info['descrip3']) ? $row_items_info['descrip3'] : '';
$codigo2 = isset($row_items_info['codigo2']) ? $row_items_info['codigo2'] : $txt_codigo2;
if (trim((string)$codigo2) === '') {
	$codigo2 = $txt_codigo2;
}

include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
$layoutGti13 = label_layout_ensure_shared('gti13');
$zpl = build_zpl_etiqueta_gti13($codigo2, $descrip, $descrip2, $unidades, $cant, $elab_day, $expir, $descrip3, $layoutGti13);
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'caja_gti13.zpl', $zpl);

$printer = 'GK420t_2x3';
$qid = enqueue_zpl($link, 'gti13', $itemid, $zpl, $printer);
$notes = 'GTI 13 caja 2x3 (Rey) + QR La Cocina de Sofy. No es GTIN SoftShop ni formato #13.';
$path = print_migrate_uses_new_queue($printer) ? 'mysql' : 'legacy';
$title = trim($descrip3 !== '' ? $descrip3 : ($descrip . ' ' . $descrip2));

print_report_render(array(
	'codigo' => (string)$itemid,
	'descrip' => $title !== '' ? $title : ('GTI13 ' . $codigo2),
	'tipo' => 'gti13',
	'cant' => (string)$cant,
	'printer' => $printer,
	'qid' => $qid ? (int)$qid : 0,
	'path' => $path,
	'notes' => $notes,
	'zpl' => $zpl,
	'raw_log' => '',
));
