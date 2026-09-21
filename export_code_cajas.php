<?php
/**
 * Impresión de etiquetas grandes para cajas/paquetes (nachos, galletas, etc.).
 * Similar a export_code13.php y export_gti13.php pero formato caja grande.
 * Encola como etiqueta=cajas → imprime en formato grande para cajetas.
 */
include("conections.php");
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_report_lib.php');

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

$caducidad = sprintf("%03d", $caducidad);

$elab_day = isset($_POST['elab_day']) ? trim((string)$_POST['elab_day']) : date('Y-m-d');
$elab_day_formatted = date("d-m-Y", strtotime($elab_day));

// Buscar producto por codigo2 o codigo
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

// ZPL para etiqueta grande de caja (formato similar al que mencionaste)
$zpl = '
^XA
^CI28
^PW609
^LL406
^LH0,0

^FO15,15^GB579,376,2^FS

^FO30,35^A0N,32,32^FB549,65,2,0,C,0^FD'.$descrip.'^FS
^FO35,35^A0N,32,32^FB549,65,2,0,C,0^FD'.$descrip2.'^FS

^BY2,3,90
^FO145,115^BCN,90,Y,N,N
^FD'.$row_items_info['codigo'].'^FS

^FO45,245^A0N,30,30^FDUnidades:^FS
^FO175,245^A0N,30,30^FD'.$cant.'^FS

^FO45,285^A0N,30,30^FDLote:^FS
^FO175,285^A0N,30,30^FD'.$caducidad.'^FS

^FO45,325^A0N,30,30^FDElab:^FS
^FO175,325^A0N,30,30^FD'.$elab_day_formatted.'^FS

^XZ
^PQ003
^XZ';

// Guardar archivo ZPL
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta_cajas.zpl', $zpl);

// Imprimir vía cola MySQL (nuevo servicio) o legacy
$printer = 'impresora_cajas'; // Configurar la impresora Zebra apropiada
$qid = enqueue_zpl($link, 'cajas', $itemid, $zpl, $printer);
$notes = 'Etiqueta grande para cajeta de ' . $cant . ' unidades + lote ' . $caducidad . ' - Elaboración: ' . $elab_day_formatted;
$path = print_migrate_uses_new_queue($printer) ? 'mysql' : 'legacy';
$title = trim($descrip3 !== '' ? $descrip3 : ($descrip . ' ' . $descrip2));

print_report_render(array(
	'codigo' => (string)$itemid,
	'descrip' => $title !== '' ? $title : ('Cajas ' . $txt_codigo2),
	'tipo' => 'cajas',
	'cant' => (string)$cant,
	'printer' => $printer,
	'qid' => $qid ? (int)$qid : 0,
	'path' => $path,
	'notes' => $notes,
	'zpl' => $zpl,
	'raw_log' => '',
));