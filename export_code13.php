<?php
/**
 * Impresión GTIN chica — botón "Imprimir GTIN" (NO es items.etiqueta #13).
 * Genera ZPL horizontal SoftShop y encola como etiqueta=gtin → GK420t_chica.
 */
include("conections.php");
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_gtin.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_report_lib.php');

$link = conec_mysql();

$txt_codigo2 = isset($_POST['txt_codigo2']) ? trim((string)$_POST['txt_codigo2']) : '';
$cant = isset($_POST['cant2']) ? (int)$_POST['cant2'] : 0;
if ($cant < 1) {
	$cant = 1;
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
$codigo2 = isset($row_items_info['codigo2']) ? $row_items_info['codigo2'] : $txt_codigo2;
if (trim((string)$codigo2) === '') {
	$codigo2 = $txt_codigo2;
}

$zpl = build_zpl_etiqueta_gtin($codigo2, $descrip, $descrip2, $cant);
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'chica_gti13.zpl', $zpl);

$printerGtin = 'GK420t_chica';
$qid = enqueue_zpl($link, 'gtin', $itemid, $zpl, $printerGtin);
$notes = 'GTIN chica (no es formato #13).';
$path = print_migrate_uses_new_queue($printerGtin) ? 'mysql' : 'legacy';
$title = trim($descrip . ' ' . $descrip2);

print_report_render(array(
	'codigo' => (string)$itemid,
	'descrip' => $title !== '' ? $title : ('GTIN ' . $codigo2),
	'tipo' => 'gtin',
	'cant' => (string)$cant,
	'printer' => $printerGtin,
	'qid' => $qid ? (int)$qid : 0,
	'path' => $path,
	'notes' => $notes,
	'zpl' => $zpl,
	'raw_log' => '',
));
