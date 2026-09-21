<?php
/**
 * Impresión de etiquetas grandes para cajas/paquetes (nachos, galletas, etc.).
 * Versión LEGACY - sin cola MySQL.
 * Genera el ZPL y crea un batch para enviar a la impresora GK420t_2x3.
 * Igual que los servicios antiguos del proyecto.
 */
include("conections.php");

$link = conec_mysql();

// Capturar datos del formulario (igual que los servicios viejos)
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

// Buscar producto en BD (misma lógica que export_code13.php / export_gti13.php)
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

// Generar ZPL para etiqueta grande de caja (formato 609×406 mm)
// MÁS A LA IZQUIERDA (margen X=2), logo, barcode ancho, letra grande
$zpl = '
^XA
^CI28
^PW609
^LL406
^LH0,0

^FO2,2^XGimg/logo.png,35,35^FS

^FO2,40^GB605,330,2^FS

^FO5,50^A0N,36,36^FB590,70,2,0,C,0^FD'.$descrip.'^FS
^FO5,50^A0N,36,36^FB590,70,2,0,C,0^FD'.$descrip2.'^FS

^BY3,3,70
^FO140,105^BCN,70,Y,N,N
^FD'.$row_items_info['codigo'].'^FS

^FO5,235^A0N,36,36^FDUnidades:^FS
^FO170,235^A0N,36,36^FD'.$cant.'^FS

^FO5,280^A0N,36,36^FDLote:^FS
^FO170,280^A0N,36,36^FD'.$caducidad.'^FS

^FO5,325^A0N,36,36^FDElaboraci\'on:^FS
^FO170,325^A0N,36,36^FD'.$elab_day_formatted.'^FS

^XZ
^PQ003
^XZ';

// Guardar archivo ZPL localmente (mismo patrón que los servicios viejos)
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta_cajas.zpl', $zpl);

// CREAR batch para envío directo a impresora (SIN MySQL)
// Igual que tenían en el proyecto viejo: start /max cmd /k "batch_path"
$batch_path = __DIR__ . '\\etiquetas_cajas.bat';
$comando = 'start /max cmd /k "' . $batch_path . '"';

// También guardar el batch para que el worker o el usuario pueda ejecutarlo
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiquetas_cajas.bat', $comando);

// Reportar resultado (vía legacy, sin qid de MySQL)
print_report_render(array(
	'codigo' => (string)$itemid,
	'descrip' => $descrip . ' ' . $descrip2,
	'tipo' => 'cajas_legacy',
	'cant' => (string)$cant,
	'printer' => 'GK420t_2x3', // Impresora Zebra legacy
	'qid' => 0, // Sin cola MySQL
	'path' => 'legacy', // Usar path legacy
	'notes' => 'Etiqueta grande para cajeta de ' . $cant . ' unidades + lote ' . $caducidad . ' - Elaboraci\'n: ' . $elab_day_formatted . ' (modo legacy, sin MySQL)',
	'zpl' => $zpl,
	'raw_log' => '',
));

echo "<br><br>";
echo "Etiqueta ZPL generada y guardada en: etiqueta_cajas.zpl<br>";
echo "Batch creado para impresora GK420t_2x3<br>";
echo "<a href='index.php'><button>Nueva Búsqueda</button></a>";