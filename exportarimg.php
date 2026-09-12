
<?php
include("conections.php");
//include ("autentication_seguridad.php");

 
$link = conec_mysql();


$today = date('Y-m-d');

// Validar que los datos POST mínimos existen (permitimos elab_day y caducidad vacíos)
if (empty($_POST['txt_codigo']) || empty($_POST['cant'])) {
    die("Error: Faltan datos requeridos en el formulario.");
}
	
$txt_codigo =  isset($_POST['txt_codigo']) ? $_POST['txt_codigo'] : '';
//$txt_codigo2 =  $_POST['txt_codigo2'];  
ob_start();
echo $txt_codigo ;

$cant =  isset($_POST['cant']) ? $_POST['cant'] : 0;  

$caducidad =  isset($_POST['caducidad']) ? $_POST['caducidad'] : 0;  

// Si caducidad está vacío, intentar usar caducidad1
if (empty($caducidad) && isset($_POST['caducidad1'])) {
    $caducidad = $_POST['caducidad1'];
}

// Asegurar que sean numéricos antes de formatear
$cant = empty($cant) ? 0 : intval($cant);
$caducidad = empty($caducidad) ? 0 : intval($caducidad);

$cant =   sprintf("%03d",$cant);

$caducidad =  sprintf("%03d",$caducidad);

//$txt_precio =  $_POST['txt_precio'];  
 
$elab_day =  isset($_POST['elab_day']) ? $_POST['elab_day'] : '';  

// Convertir fecha de YYYY-MM-DD a DD-MM-YYYY si viene del input type="date"
if (!empty($elab_day) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $elab_day)) {
    $elab_day = date("d-m-Y", strtotime($elab_day));
}

// Bandera para saber si se envió fecha de elaboración
$elab_provided = !empty($elab_day);
 
//$txt_descrip =  $_POST['txt_descrip'];

// $elab_day2 = date("Y-m-d", strtotime($elab_day2)); // Asegura formato correcto
// $mesnum = date("n", strtotime($elab_day2));       // Número del mes (1–12)
// $dia = date("d", strtotime($elab_day2));          // Día con 2 dígitos

// switch ($mesnum) {
//     case 1:  $mescod = "E0"; break;
//     case 2:  $mescod = "F0"; break;
//     case 3:  $mescod = "M0"; break;
//     case 4:  $mescod = "AL"; break;
//     case 5:  $mescod = "MA"; break;
//     case 6:  $mescod = "JO"; break;
//     case 7:  $mescod = "JU"; break;
//     case 8:  $mescod = "AO"; break;
//     case 9:  $mescod = "SE"; break;
//     case 10: $mescod = "OC"; break;
//     case 11: $mescod = "NV"; break;
//     case 12: $mescod = "DI"; break;
//     default: $mescod = "XX"; // En caso de error
// }

// $lote = $mescod . $dia;

// if ('025413' == $txt_codigo) 
// 			{
		 
// 				$checker = 1;
// 			$checker = 1;
// 				$zpl = '^XA
// ^CI28
// ^PW183                 ; ancho 0.90"  (183 dots)
// ^LL495                 ; largo  2.44" (495 dots)
// ^LS0
 

// ^CWZ,E:LEXENDDECA.TTF  ; asigna Lexend Deca (TTF) al alias Z

// ^FX ---------- Texto ----------
// ^FO140,20 ^AZR,30,24^FD'.$txt_descrip.'^FS
// ^FO115,20 ^AZR,26,22^FD'.$txt_descrip2.' ^FS
// ^FO70,20 ^AZR,24,20^FDExp.: 12/10/25^FS
// ^FO40, 20^AZR,24,20^FD#Lote: '.$lote .'^FS

// ^FX ---------- Precio (más grande) ----------
// ^FO20,300^AZR,35,25^FD'. $txt_precio.'^FS

// ^FX ---------- Código de barras GTIN-13 (EAN-13) ----------
// ^BY1,2.0,140           ; módulo delgado para 0.90" (puedes probar ^BY2 si hay lecturas pobres)
// ^FWR

// ^PQ1
// ^XZ';
				
// echo 	$zpl;

// 			}
 
$query_items_info = '
SELECT 
*
FROM
items 
WHERE  
codigo = '.$txt_codigo;
 
 $result_items_info= mysqli_query($link, $query_items_info);
	
	if (!$result_items_info) {
		die("Error en query de items: " . mysqli_error($link));
	}

 $row_items_info = mysqli_fetch_array($result_items_info);
	
	if (!$row_items_info) {
		die("No se encontró el código en la base de datos: " . mysqli_error($link));
	}

	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
	$ovExp = label_overlay_from_json($link, $txt_codigo, $row_items_info, array());
	$row_items_info = $ovExp['row'];
	
	 //echo $row_items_info['descrip3'];
	
	// Verificar tipo de etiqueta
	$etiqueta_tipo = isset($row_items_info['etiqueta']) ? $row_items_info['etiqueta'] : '';
	


	if ($etiqueta_tipo == '21') {
echo "<br>Tipo de etiqueta: 13-1 - Generando ZPL e imprimiendo...<br>";
		
		// Obtener datos del producto
		$descrip = isset($row_items_info['descrip']) ? $row_items_info['descrip'] : '';
		$descrip2 = isset($row_items_info['descrip2']) ? $row_items_info['descrip2'] : '';
		$precio = isset($row_items_info['precio2']) ? $row_items_info['precio2'] : '';
		$codigo2 = isset($row_items_info['codigo2']) ? $row_items_info['codigo2'] : '';
		if ($elab_provided) {
			$elab_timestamp = strtotime($elab_day);
			$fecha_vencimiento = strtotime("+$caducidad days", $elab_timestamp);
			$fecha_exp = date('d/m/y', $fecha_vencimiento);
			$mesnum = date("n", $elab_timestamp);
			$dia = date("d", $elab_timestamp);
		} else {
			// Valores por defecto cuando no hay fecha de elaboración
			$fecha_exp = '01-01-1970';
			$mesnum = 0;
			$dia = '00';
		}
		switch ($mesnum) {
			case 1:  $mescod = "E0"; break;
			case 2:  $mescod = "F0"; break;
			case 3:  $mescod = "M0"; break;
			case 4:  $mescod = "AL"; break;
			case 5:  $mescod = "MA"; break;
			case 6:  $mescod = "JO"; break;
			case 7:  $mescod = "JU"; break;
			case 8:  $mescod = "AO"; break;
			case 9:  $mescod = "SE"; break;
			case 10: $mescod = "OC"; break;
			case 11: $mescod = "NV"; break;
			case 12: $mescod = "DI"; break;
			default: $mescod = "XX";
		}
		
		$lote = $mescod . $dia;

			// Generar código ZPL
		$zpl = '^XA
^AD,54
^CFA,12
^LT10
^CWZ,E:LEXENDDECA.TTF
^CFA,10
^CFA,20
^FO180,65^AZN,25,10^FD$'. $precio.'^FS
^CFA,14
^FO25,5^FD'.$descrip.'^FS
^FO15,25^FD'.$descrip2.'^FS
^CFA,14
^FO25,85^FD#Lote: '.$lote .'^FS
^CFA,14
^FO15,65^FDExp.:'.$fecha_exp.'^FS
^CFA,14
^FO10,80^FD-^FS
^PQ'.$cant .'
^XZ
  ';
		PRINT $zpl;
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'chica.zpl', $zpl);

		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		$qid = enqueue_zpl_21($link, (string)$txt_codigo, $zpl);
		if ($qid) {
			echo enqueue_zpl_path_html($qid, 'GK420t_chica');
		} else {
			echo "<br>No se pudo encolar ZPL 21: " . mysqli_error($link) . "<br>";
		}


	}

	$estado_label_print = 0;
	if ($etiqueta_tipo == '1' || $etiqueta_tipo == 1) {
		echo "<br>Tipo de etiqueta: 1 (SofyShop 1&quot; x 0.5&quot;) - Generando ZPL y encolando...<br>";
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_1.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
		$descrip1 = isset($row_items_info['descrip3']) ? $row_items_info['descrip3'] : '';
		$precio1 = isset($row_items_info['precio2']) ? $row_items_info['precio2'] : 0;
		$layout1 = label_layout_ensure_shared(1);
		$zpl = build_zpl_etiqueta_1($txt_codigo, $descrip1, $precio1, intval($cant), intval($caducidad), $elab_day, true, $layout1);
		echo "<br>Layout: <strong>JSON shared tipo_1</strong><br>";
		PRINT $zpl;
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta1.zpl', $zpl);
		$qid = enqueue_zpl_1($link, (string)$txt_codigo, $zpl);
		if ($qid) {
			$estado_label_print = 1;
			echo enqueue_zpl_path_html($qid, 'GK420t_chica');
		} else {
			echo "<br>No se pudo encolar ZPL 1: " . mysqli_error($link) . "<br>";
		}
	}

	if ($etiqueta_tipo == '9' || $etiqueta_tipo == 9) {
		echo "<br>Tipo de etiqueta: 9 (no comestible, 1&quot; x 0.5&quot;, sin EXP/lote) - Generando ZPL y encolando...<br>";
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_1.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
		$descrip9 = isset($row_items_info['descrip3']) ? $row_items_info['descrip3'] : '';
		$precio9 = isset($row_items_info['precio2']) ? $row_items_info['precio2'] : 0;
		$layout9 = label_layout_ensure_shared(9);
		$zpl = build_zpl_etiqueta_9($txt_codigo, $descrip9, $precio9, intval($cant), $layout9);
		echo "<br>Layout: <strong>JSON shared tipo_9</strong><br>";
		PRINT $zpl;
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta9.zpl', $zpl);
		$qid = enqueue_zpl($link, '9', (string)$txt_codigo, $zpl);
		if ($qid) {
			$estado_label_print = 1;
			echo enqueue_zpl_path_html($qid, 'GK420t_chica');
		} else {
			echo "<br>No se pudo encolar ZPL 9: " . mysqli_error($link) . "<br>";
		}
	}

	if ($etiqueta_tipo == '5' || $etiqueta_tipo == 5) {
		echo "<br>Tipo de etiqueta: 5 (mediana SoftShop + ingredientes) - Generando ZPL y encolando...<br>";
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_5.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
		$descrip5 = isset($row_items_info['descrip3']) ? $row_items_info['descrip3'] : '';
		$precio5 = isset($row_items_info['precio2']) ? $row_items_info['precio2'] : 0;
		$ingre5 = isset($row_items_info['ingredientes']) ? $row_items_info['ingredientes'] : '';
		$layout5 = label_layout_ensure_shared(5);
		$zpl = build_zpl_etiqueta_5($txt_codigo, $descrip5, $precio5, intval($cant), intval($caducidad), $elab_day, $ingre5, $layout5);
		echo "<br>Layout: <strong>JSON shared tipo_5</strong><br>";
		PRINT htmlspecialchars($zpl);
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta5.zpl', $zpl);
		$qid = enqueue_zpl($link, '5', (string)$txt_codigo, $zpl, 'GK420t_grande');
		if ($qid) {
			$estado_label_print = 1;
			echo enqueue_zpl_path_html($qid, 'GK420t_grande');
		} else {
			echo "<br>No se pudo encolar ZPL 5: " . mysqli_error($link) . "<br>";
		}
	}

	if ($etiqueta_tipo == '10' || $etiqueta_tipo == 10) {
		echo "<br>Tipo de etiqueta: 10 (diseño BMP + codigo + lbl_lines) - Convirtiendo a ZPL...<br>";
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_10.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
		$lbls10 = etiqueta10_lookup_lbls($link, $txt_codigo);
		$lines10 = etiqueta10_lookup_lines($link, $txt_codigo);
		$layout10 = label_layout_load_item($link, $txt_codigo, true);
		$srcLayout10 = 'MySQL';
		if ($layout10) {
			$merged10 = label_layout_apply_to_lbls_lines($layout10, $lbls10, $lines10);
			$lbls10 = $merged10['lbls'];
			$lines10 = $merged10['lines'];
			$srcLayout10 = 'JSON local items/' . label_layout_pad_codigo($txt_codigo) . '.json';
		}
		echo "<br>Layout coords: <strong>" . htmlspecialchars($srcLayout10) . "</strong><br>";
		$ctx10 = array(
			'itemid' => (string)$txt_codigo,
			'precio' => isset($row_items_info['precio2']) ? $row_items_info['precio2'] : 0,
			'descripcion' => isset($row_items_info['descrip3']) ? $row_items_info['descrip3'] : '',
			'ingredientes' => isset($row_items_info['ingredientes']) ? $row_items_info['ingredientes'] : '',
			'especif' => isset($row_items_info['especif']) ? $row_items_info['especif'] : '',
			'obs' => isset($row_items_info['obs']) ? $row_items_info['obs'] : '',
			'reg_sanitario' => isset($row_items_info['reg_sanitario']) ? $row_items_info['reg_sanitario'] : '',
			'expir' => intval($caducidad),
			'fecha_manufact' => $elab_day,
			'lines' => $lines10,
		);
		$built10 = build_zpl_etiqueta_10($txt_codigo, intval($cant), $lbls10, '', $ctx10, $link);
		if (!empty($built10['ok'])) {
			$zpl = $built10['zpl'];
			PRINT htmlspecialchars(substr($zpl, 0, 500)) . "...<br>";
			file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta10.zpl', $zpl);
			$printer10 = isset($built10['printer']) ? $built10['printer'] : '';
			$qid = enqueue_zpl($link, '10', (string)$txt_codigo, $zpl, $printer10);
			if ($qid) {
				$estado_label_print = 1;
				$nLines = isset($built10['lines']) ? (int)$built10['lines'] : 0;
				echo enqueue_zpl_path_html($qid, $printer10);
				echo "<br>Imagen: " . htmlspecialchars(basename($built10['path'])) . ". lbl_lines: $nLines.<br>";
			} else {
				echo "<br>No se pudo encolar ZPL 10: " . mysqli_error($link) . "<br>";
			}
		} else {
			$err10 = isset($built10['error']) ? $built10['error'] : 'error desconocido';
			echo "<br><strong>No se generó ZPL 10:</strong> " . htmlspecialchars($err10) . "<br>";
			echo "Coloca el diseño en printserver/" . htmlspecialchars(sprintf('%06d', intval($txt_codigo))) . ".bmp<br>";
		}
	}

	if ($etiqueta_tipo == '14' || $etiqueta_tipo == 14) {
		echo "<br>Tipo de etiqueta: 14 (BMP + codigo2/itemid2) - Convirtiendo a ZPL...<br>";
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_14.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'label_layout_lib.php');
		$lbls14 = etiqueta10_lookup_lbls($link, $txt_codigo);
		$lines14 = etiqueta10_lookup_lines($link, $txt_codigo);
		$layout14 = label_layout_load_item($link, $txt_codigo, true);
		$srcLayout14 = 'MySQL';
		if ($layout14) {
			$merged14 = label_layout_apply_to_lbls_lines($layout14, $lbls14, $lines14);
			$lbls14 = $merged14['lbls'];
			$lines14 = $merged14['lines'];
			$srcLayout14 = 'JSON local items/' . label_layout_pad_codigo($txt_codigo) . '.json';
		}
		echo "<br>Layout coords: <strong>" . htmlspecialchars($srcLayout14) . "</strong><br>";
		$codigo2_14 = isset($row_items_info['codigo2']) ? $row_items_info['codigo2'] : $txt_codigo;
		$ctx14 = array(
			'itemid' => (string)$txt_codigo,
			'precio' => isset($row_items_info['precio2']) ? $row_items_info['precio2'] : 0,
			'descripcion' => isset($row_items_info['descrip3']) ? $row_items_info['descrip3'] : '',
			'ingredientes' => isset($row_items_info['ingredientes']) ? $row_items_info['ingredientes'] : '',
			'especif' => isset($row_items_info['especif']) ? $row_items_info['especif'] : '',
			'obs' => isset($row_items_info['obs']) ? $row_items_info['obs'] : '',
			'reg_sanitario' => isset($row_items_info['reg_sanitario']) ? $row_items_info['reg_sanitario'] : '',
			'expir' => intval($caducidad),
			'fecha_manufact' => $elab_day,
			'lines' => $lines14,
		);
		$built14 = build_zpl_etiqueta_14($txt_codigo, $codigo2_14, intval($cant), $lbls14, $ctx14, $link);
		if (!empty($built14['ok'])) {
			$zpl = $built14['zpl'];
			PRINT htmlspecialchars(substr($zpl, 0, 400)) . "...<br>";
			file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta14.zpl', $zpl);
			$printer14 = isset($built14['printer']) ? $built14['printer'] : '';
			$qid = enqueue_zpl($link, '14', (string)$txt_codigo, $zpl, $printer14);
			if ($qid) {
				$estado_label_print = 1;
				echo enqueue_zpl_path_html($qid, $printer14);
				echo "<br>Barras: " . htmlspecialchars((string)$codigo2_14) . ".<br>";
			} else {
				echo "<br>No se pudo encolar ZPL 14: " . mysqli_error($link) . "<br>";
			}
		} else {
			$err14 = isset($built14['error']) ? $built14['error'] : 'error desconocido';
			echo "<br><strong>No se generó ZPL 14:</strong> " . htmlspecialchars($err14) . "<br>";
		}
	}

	if ($etiqueta_tipo == '13' || $etiqueta_tipo == 13) {
		echo "<br>Tipo de etiqueta: <strong>13</strong> (ZPL rotado 0.90\"×2.44\" → impresora por IP)...<br>";
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_13.php');
		include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
		$descrip = isset($row_items_info['descrip']) ? $row_items_info['descrip'] : '';
		$descrip2 = isset($row_items_info['descrip2']) ? $row_items_info['descrip2'] : '';
		if (trim((string)$descrip) === '' && isset($row_items_info['descrip3'])) {
			$descrip = $row_items_info['descrip3'];
		}
		$precio = isset($row_items_info['precio2']) ? $row_items_info['precio2'] : 0;
		$zpl = build_zpl_etiqueta_13(
			$descrip,
			$descrip2,
			$precio,
			intval($cant),
			intval($caducidad),
			$elab_day
		);
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'barcode.zpl', $zpl);
		file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'etiqueta13.zpl', $zpl);

		$sent = etiqueta13_send_ip($zpl);
		if (!empty($sent['ok'])) {
			$estado_label_print = 1;
			echo "<br>Etiqueta #13 enviada por IP a <strong>" . htmlspecialchars($sent['ip']) . ":9100</strong> (" . (int)$sent['written'] . " bytes).<br>";
		} else {
			echo "<br><strong>Fallo envío IP #13:</strong> " . htmlspecialchars(isset($sent['error']) ? $sent['error'] : 'error') . "<br>";
			echo "Revise print_ip_13.cfg (IP actual: " . htmlspecialchars(etiqueta13_printer_ip()) . ").<br>";
		}
		// Copia también a cola local por si quieren reimprimir en Virtual ZPL
		$qid = enqueue_zpl($link, '13', (string)$txt_codigo, $zpl, 'IP:' . etiqueta13_printer_ip());
		if ($qid) {
			echo "<br>También encolada (id $qid) por respaldo.<br>";
		}
	} elseif ($etiqueta_tipo != '21' && $etiqueta_tipo != '1' && $etiqueta_tipo != 1 && $etiqueta_tipo != '9' && $etiqueta_tipo != 9 && $etiqueta_tipo != '5' && $etiqueta_tipo != 5 && $etiqueta_tipo != '10' && $etiqueta_tipo != 10 && $etiqueta_tipo != '13' && $etiqueta_tipo != 13 && $etiqueta_tipo != '14' && $etiqueta_tipo != 14) {
		echo "<br>Tipo de etiqueta: $etiqueta_tipo - Solo guardando en MySQL (sin imprimir)<br>";
	}

	$query_insert_label_test = "
	INSERT 
	INTO  
	isabel_label_print 
	(
	itemid,	r
	descripcion,
	precio,
	cantidad,
	vencimiento,
	fecha,
	especif,
	estado,
	type,
	ingredientes,
	reg_sanitario,
	obs,
	itemid2
	)
	values
	( 
	".$txt_codigo.",
	'".$row_items_info['descrip3']."',
	".$row_items_info['precio2'].",
	$cant,
	$caducidad,
	'". $elab_day."',
	'".$row_items_info['especif']."',
	0,
	'".$row_items_info['etiqueta']."',
	'".$row_items_info['ingredientes']."', 
	'".$row_items_info['reg_sanitario']."',
	'".$row_items_info['obs']. "'  ,
		" . $row_items_info['codigo2'] . "
	)
	";
	
	
	
	
	  
	$query_insert_label = "
	INSERT 
	INTO  
	isabel_label_print 
	(
	itemid,
	descripcion,
	precio,
	cantidad,
	vencimiento,
	fecha,
 
	especif,
	estado,
	type,
	ingredientes,
	reg_sanitario,
	obs ,
	itemid2
	)
	values
	( 
	".$txt_codigo.",
	'".$row_items_info['descrip3']."',
	".$row_items_info['precio2'].",
	$cant,
		$caducidad,
		" . ($elab_provided ? "str_to_date('".$elab_day ."','%d-%m-%Y')" : "'1970-01-01'") . ",
	'".$row_items_info['especif']."',
	".intval($estado_label_print).",
	'".$row_items_info['etiqueta']."',
	'".$row_items_info['ingredientes']."', 
	'".$row_items_info['reg_sanitario']."',
	'".$row_items_info['obs']. "'  ,
		" . $row_items_info['codigo2'] . "
 
	)
	";
	
  
	 
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php');
	$printerLegacy = enqueue_zpl_resolve_printer($link, (string)$etiqueta_tipo, (string)$txt_codigo, '');
	$report_path = 'legacy';
	$report_notes = '';
	$report_qid = 0;
	$report_printer = $printerLegacy;
	$report_zpl = '';

	if ($etiqueta_tipo == '13' || $etiqueta_tipo == 13) {
		echo "<br>Formato #13: impreso por IP, sin isabel_label_print.<br>";
		$report_path = 'ip';
		$report_printer = 'IP:' . (function_exists('etiqueta13_printer_ip') ? etiqueta13_printer_ip() : '');
		$report_notes = 'Envío directo TCP :9100';
		if (isset($qid) && $qid) {
			$report_qid = (int)$qid;
		}
		if (isset($zpl)) {
			$report_zpl = (string)$zpl;
		}
	} elseif (print_migrate_uses_new_queue($printerLegacy)) {
		echo "<br>Impresora <strong>" . htmlspecialchars($printerLegacy) . "</strong> migrada: no se inserta en <code>isabel_label_print</code> (el legacy no debe imprimir esta etiqueta).<br>";
		$report_path = 'mysql';
		$report_notes = 'Cola unificada isabel_zpl_queue → print_service_21';
		if (isset($qid) && $qid) {
			$report_qid = (int)$qid;
		}
		if (isset($zpl)) {
			$report_zpl = (string)$zpl;
		}
	} else {
		$result_guardar_imagen = mysqli_query($link, $query_insert_label);
		if ($result_guardar_imagen) {
			echo "<br>Encolado en <code>isabel_label_print</code> (servicio legacy). Impresora: " . htmlspecialchars($printerLegacy !== '' ? $printerLegacy : '(lbls/default)') . ".<br>";
			$report_notes = 'Legacy Label_Printserver / isabel_label_print';
		} else {
			echo "<br>Error al insertar isabel_label_print: " . htmlspecialchars(mysqli_error($link)) . "<br>";
			$report_notes = 'Error al encolar legacy';
		}
		if (isset($zpl)) {
			$report_zpl = (string)$zpl;
		}
	}

	$raw_log = ob_get_clean();
	include_once(__DIR__ . DIRECTORY_SEPARATOR . 'print_report_lib.php');
	$descripR = '';
	if (isset($row_items_info['descrip3']) && trim((string)$row_items_info['descrip3']) !== '') {
		$descripR = $row_items_info['descrip3'];
	} elseif (isset($row_items_info['descrip'])) {
		$descripR = $row_items_info['descrip'];
	}
	print_report_render(array(
		'codigo' => (string)$txt_codigo,
		'descrip' => (string)$descripR,
		'tipo' => (string)$etiqueta_tipo,
		'cant' => (string)$cant,
		'printer' => (string)$report_printer,
		'qid' => (int)$report_qid,
		'path' => $report_path,
		'notes' => $report_notes,
		'zpl' => $report_zpl,
		'raw_log' => $raw_log,
	));
	exit;