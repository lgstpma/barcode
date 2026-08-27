<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
  <?php

  include("conections.php");
  $link = conec_mysql();
  
  
$alphabet = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

$action   =  @$action = $_GET['action']; 

function mysqli_stmt_bind_params_dynamic(mysqli_stmt $stmt, $types, array &$params)
{
  $bind = array($types);
  foreach ($params as $k => $v) {
    $bind[] = &$params[$k];
  }
  return call_user_func_array(array($stmt, 'bind_param'), $bind);
}

function html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function build_search_table_html($link, $q)
{
  $q = trim((string)$q);
  if ($q === '') return null;

  // numérico => abrir item como siempre
  if (preg_match('/^\d+$/', $q)) {
    $code = sprintf("%06d", (int)$q);
    header("Location: search_results.php?code=" . urlencode($code) . "&bttn_actualizar=FM");
    exit;
  }

  $qEsc = mysqli_real_escape_string($link, $q);
  $qLike = '%' . $qEsc . '%';
  $sql = "
    SELECT items.codigo, items.codigo2, items.descrip, items.descrip2, items.descrip3,
           items.precio2, items.etiqueta, items.navidad, items.exp,
           lbls.printer AS printer
    FROM items
    LEFT JOIN lbls ON lbls.id = items.codigo
    WHERE items.navidad = 'FM'
      AND (
        items.descrip LIKE '$qLike'
        OR items.descrip2 LIKE '$qLike'
        OR items.descrip3 LIKE '$qLike'
        OR CONCAT(IFNULL(items.descrip,''),' ',IFNULL(items.descrip2,'')) LIKE '$qLike'
      )
    ORDER BY items.descrip3 ASC
    LIMIT 120
  ";
  $res = mysqli_query($link, $sql);

  if (!$res) {
    return '<div style="margin-top:10px; padding:10px; border:1px solid #f00; color:#900;">'
      . '<b>Error en búsqueda:</b> ' . html(mysqli_error($link)) . '</div>';
  }

  $out = '<div class="tabcontent" style="display:block; margin-top:10px;">'
    . '<table border="0" id="search_name_results" class="display" style="width:100%">'
    . '<tr><thead>'
    . '<tr><td align="center"><h2>BÚSQUEDA: ' . html($q) . '</h2></td></tr>'
    . '<td width="55"># codigo</td>'
    . '<td width="15">Precio</td>'
    . '<td width="50">Caducidad</td>'
    . '<td width="260">Descripción</td>'
    . '<td width="90">Printer</td>'
    . '<td width="75"># Etiqueta</td>'
    . '</thead>';

  $found = false;
  $cont = 1;
  if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
      $found = true;
      $bg = ($cont % 2 === 0) ? ' bgcolor="#C0C0C0"' : ' bgcolor="#FFFFFF"';
      $cont++;
      $desc = trim((string)$r['descrip3']);
      if ($desc === '') $desc = trim((string)$r['descrip'] . ' ' . (string)$r['descrip2']);

      $codigo = (string)$r['codigo'];
      $codigo2 = (string)$r['codigo2'];
      $out .= '<tr' . $bg . '>'
        . '<td align="center"><a href="search_results.php?code=' . html($codigo) . '&bttn_actualizar=FM&txt_codigo2=' . html($codigo2) . ' ">' . html($codigo) . '</a></td>'
        . '<td>' . html($r['precio2']) . '</td>'
        . '<td align="center">' . html($r['exp']) . '</td>'
        . '<td>' . html($desc) . '</td>'
        . '<td>' . html($r['printer']) . '</td>'
        . '<td>' . html($r['etiqueta']) . '</td>'
        . '</tr>';
    }
    mysqli_free_result($res);
  }

  if (!$found) {
    $out .= '<tr><td colspan="6">No se encontraron items.</td></tr>';
  }

  $out .= '</table></div>';
  return $out;
}

function compute_odoo_mysql_navidad_diff($link)
{
  list($models, $db, $uid, $password) = odoo_connect();
  $odooProducts = odoo_search_products_with_barcode($models, $db, $uid, $password);

  // Clave de comparación: items.codigo (MySQL) vs default_code (Referencia interna de Odoo)
  $byCode = array(); // code => ['default_code'=>..,'barcode'=>..,'name'=>..]
  foreach ((array)$odooProducts as $p) {
    $dc = isset($p['default_code']) ? normalize_inventory_code($p['default_code']) : '';
    if ($dc === '') continue;
    $byCode[$dc] = array(
      'default_code' => $dc,
      'name' => isset($p['name']) ? $p['name'] : '',
      'barcode' => isset($p['barcode']) ? (string)$p['barcode'] : '',
    );
  }

  $codes = array_keys($byCode);
  $mysqlRows = array(); // code => row
  foreach (array_chunk($codes, 400) as $chunk) {
    $n = count($chunk);
    if ($n <= 0) continue;
    $placeholders = implode(',', array_fill(0, $n, '?'));
    $types = str_repeat('s', $n);
    $sql = "SELECT codigo, navidad, descrip, descrip2 FROM items WHERE codigo IN ($placeholders)";
    $st = mysqli_prepare($link, $sql);
    if (!$st) continue;
    mysqli_stmt_bind_params_dynamic($st, $types, $chunk);
    mysqli_stmt_execute($st);
    if (function_exists('mysqli_stmt_get_result')) {
      $res = mysqli_stmt_get_result($st);
      if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
          $c = normalize_inventory_code($r['codigo']);
          $mysqlRows[$c] = $r;
        }
        mysqli_free_result($res);
      }
    }
    mysqli_stmt_close($st);
  }

  $missingFM = array();    // code => ['odoo'=>..,'mysql'=>..]
  $missingMySQL = array(); // code => ['barcode'=>..,'name'=>..]
  foreach ($byCode as $c => $p) {
    if (!isset($mysqlRows[$c])) {
      $missingMySQL[$c] = $p;
      continue;
    }
    $nav = isset($mysqlRows[$c]['navidad']) ? trim((string)$mysqlRows[$c]['navidad']) : '';
    if ($nav !== 'FM') {
      $missingFM[$c] = array('odoo' => $p, 'mysql' => $mysqlRows[$c]);
    }
  }

  return array($byCode, $mysqlRows, $missingFM, $missingMySQL);
}

// Verificación Odoo vs MySQL: items que existen en Odoo pero NO aparecen por letras por no tener navidad='FM'
$table_odoo_fm_check = null;
$table_odoo_fm_apply_result = null;
$table_odoo_fm_clear_result = null;

$search_table_html = null;
if (isset($_POST['clave'])) {
  $search_table_html = build_search_table_html($link, $_POST['clave']);
}

if ($action === 'apply_fm') {
  try {
    list($byCode, $mysqlRows, $missingFM, $missingMySQL) = compute_odoo_mysql_navidad_diff($link);

    $toUpdate = array_keys($missingFM);
    $updated = 0;
    foreach (array_chunk($toUpdate, 400) as $chunk) {
      $n = count($chunk);
      if ($n <= 0) continue;
      $placeholders = implode(',', array_fill(0, $n, '?'));
      $types = 's' . str_repeat('s', $n);
      $params = $chunk;
      array_unshift($params, 'FM');
      $sqlUp = "UPDATE items SET navidad = ? WHERE codigo IN ($placeholders)";
      $stUp = mysqli_prepare($link, $sqlUp);
      if (!$stUp) continue;
      mysqli_stmt_bind_params_dynamic($stUp, $types, $params);
      mysqli_stmt_execute($stUp);
      $updated += (int)mysqli_stmt_affected_rows($stUp);
      mysqli_stmt_close($stUp);
    }

    $table_odoo_fm_apply_result = '<div style="margin:10px 0; padding:10px; border:1px solid #0a0; color:#060;">'
      . '<b>FM aplicado.</b> Códigos detectados para actualizar: ' . count($toUpdate) . ' | Filas afectadas: ' . $updated
      . ' | <a href="index.php?action=check_fm">Volver a verificar</a>'
      . '</div>';
  } catch (Exception $e) {
    $table_odoo_fm_apply_result = '<div style="margin:10px 0; padding:10px; border:1px solid #f00; color:#900;">Error aplicando FM: ' . html($e->getMessage()) . '</div>';
  }
}

if ($action === 'clear_fm_not_in_odoo') {
  try {
    list($byCode, $mysqlRows, $missingFM, $missingMySQL) = compute_odoo_mysql_navidad_diff($link);
    $odooCodesSet = array_flip(array_keys($byCode));

    // Traer items en MySQL con navidad='FM' y borrar FM si no están en Odoo
    $sqlFm = "SELECT codigo, navidad, descrip, descrip2 FROM items WHERE navidad = 'FM'";
    $resFm = mysqli_query($link, $sqlFm);
    if (!$resFm) {
      throw new Exception("No se pudo consultar items con navidad='FM'.");
    }

    $toClear = array();
    while ($r = mysqli_fetch_assoc($resFm)) {
      $c = normalize_inventory_code($r['codigo']);
      if ($c === '') continue;
      if (!isset($odooCodesSet[$c])) {
        $toClear[] = $c;
      }
    }
    mysqli_free_result($resFm);

    $cleared = 0;
    foreach (array_chunk($toClear, 400) as $chunk) {
      $n = count($chunk);
      if ($n <= 0) continue;
      $placeholders = implode(',', array_fill(0, $n, '?'));
      $types = str_repeat('s', $n);
      $sqlUp = "UPDATE items SET navidad = '' WHERE codigo IN ($placeholders)";
      $stUp = mysqli_prepare($link, $sqlUp);
      if (!$stUp) continue;
      mysqli_stmt_bind_params_dynamic($stUp, $types, $chunk);
      mysqli_stmt_execute($stUp);
      $cleared += (int)mysqli_stmt_affected_rows($stUp);
      mysqli_stmt_close($stUp);
    }

    $table_odoo_fm_clear_result = '<div style="margin:10px 0; padding:10px; border:1px solid #0a0; color:#060;">'
      . '<b>FM removido.</b> Códigos detectados para limpiar: ' . count($toClear) . ' | Filas afectadas: ' . $cleared
      . ' | <a href="index.php?action=check_fm">Volver a verificar</a>'
      . '</div>';
  } catch (Exception $e) {
    $table_odoo_fm_clear_result = '<div style="margin:10px 0; padding:10px; border:1px solid #f00; color:#900;">Error quitando FM: ' . html($e->getMessage()) . '</div>';
  }
}

if ($action === 'check_fm') {
  try {
    list($byCode, $mysqlRows, $missingFM, $missingMySQL) = compute_odoo_mysql_navidad_diff($link);
    $odooCodesSet = array_flip(array_keys($byCode));

    // Items con FM en MySQL que no existen en Odoo
    $notInOdooButFM = array(); // code => row
    $sqlFm = "SELECT codigo, navidad, descrip, descrip2 FROM items WHERE navidad = 'FM'";
    $resFm = mysqli_query($link, $sqlFm);
    if ($resFm) {
      while ($r = mysqli_fetch_assoc($resFm)) {
        $c = normalize_inventory_code($r['codigo']);
        if ($c === '') continue;
        if (!isset($odooCodesSet[$c])) {
          $notInOdooButFM[$c] = $r;
        }
      }
      mysqli_free_result($resFm);
    }

    $table_odoo_fm_check = '<div style="margin:10px 0; padding:10px; border:1px solid #ccc;">'
      . '<h3>Verificación Odoo vs MySQL (filtro letras: navidad = FM)</h3>'
      . '<p><b>Total Odoo con barcode:</b> ' . count($byCode)
      . ' | <b>En MySQL sin FM:</b> ' . count($missingFM)
      . ' | <b>No existen en MySQL:</b> ' . count($missingMySQL)
      . ' | <b>En MySQL con FM pero NO están en Odoo:</b> ' . count($notInOdooButFM)
      . '</p>';

    if (count($missingFM) > 0) {
      $table_odoo_fm_check .= '<form method="post" action="index.php?action=apply_fm" style="margin:6px 0;">'
        . '<input type="submit" value="APLICAR FM a estos items" onclick="return confirm(\'Esto actualizará items.navidad=FM para los códigos listados. ¿Continuar?\');" />'
        . '</form>';
    }

    if (count($notInOdooButFM) > 0) {
      $table_odoo_fm_check .= '<form method="post" action="index.php?action=clear_fm_not_in_odoo" style="margin:6px 0;">'
        . '<input type="submit" value="QUITAR FM (no están en Odoo)" onclick="return confirm(\'Esto quitará FM (items.navidad vacio) a los códigos que NO existen en Odoo. ¿Continuar?\');" />'
        . '</form>';
    }

    $table_odoo_fm_check .= '<h4>En MySQL pero navidad <> FM</h4>'
      . '<table border="1" cellspacing="0" cellpadding="4" style="width:100%; font-size:14px">'
      . '<tr><th>Código (MySQL)</th><th>Navidad</th><th>Descripción (MySQL)</th><th>Nombre (Odoo)</th><th>Ref. interna (Odoo)</th></tr>';
    foreach ($missingFM as $c => $x) {
      $r = $x['mysql'];
      $p = $x['odoo'];
      $desc = trim((string)$r['descrip'] . ' ' . (string)$r['descrip2']);
      $table_odoo_fm_check .= '<tr>'
        . '<td>' . html($c) . '</td>'
        . '<td>' . html($r['navidad']) . '</td>'
        . '<td>' . html($desc) . '</td>'
        . '<td>' . html($p['name']) . '</td>'
        . '<td>' . html($p['default_code']) . '</td>'
        . '</tr>';
    }
    $table_odoo_fm_check .= '</table>';

    $table_odoo_fm_check .= '<h4>En Odoo pero no existen en MySQL</h4>'
      . '<table border="1" cellspacing="0" cellpadding="4" style="width:100%; font-size:14px">'
      . '<tr><th>Ref. interna (Odoo)</th><th>Navidad (MySQL)</th><th>Nombre (Odoo)</th><th>Barcode</th></tr>';
    foreach ($missingMySQL as $c => $p) {
      $table_odoo_fm_check .= '<tr>'
        . '<td>' . html($c) . '</td>'
        . '<td>—</td>'
        . '<td>' . html($p['name']) . '</td>'
        . '<td>' . html($p['barcode']) . '</td>'
        . '</tr>';
    }
    $table_odoo_fm_check .= '</table>';

    $table_odoo_fm_check .= '<h4>En MySQL con FM pero NO están en Odoo</h4>'
      . '<table border="1" cellspacing="0" cellpadding="4" style="width:100%; font-size:14px">'
      . '<tr><th>Código</th><th>Navidad</th><th>Descripción (MySQL)</th></tr>';
    foreach ($notInOdooButFM as $c => $r) {
      $desc = trim((string)$r['descrip'] . ' ' . (string)$r['descrip2']);
      $table_odoo_fm_check .= '<tr>'
        . '<td>' . html($c) . '</td>'
        . '<td>' . html($r['navidad']) . '</td>'
        . '<td>' . html($desc) . '</td>'
        . '</tr>';
    }
    $table_odoo_fm_check .= '</table></div>';
  } catch (Exception $e) {
    $table_odoo_fm_check = '<div style="margin:10px 0; padding:10px; border:1px solid #f00; color:#900;">Error Odoo: ' . html($e->getMessage()) . '</div>';
  }
}
			
  ?>

<head>
  <style type="text/css">
    #caja_busqueda

    /*estilos para la caja principal de busqueda*/
      {
      width: 300px;
      height: 25px;
      border: solid 2px #979DAE;
      font-size: 25px;
    }
#div{

float: left;

}
    #display

    /*estilos para la caja principal en donde se puestran los resultados de la busqueda en forma de lista*/
      {
      width: 600px;
      display: none;
      overflow: hidden;
      z-index: 10;
      border: solid 1px #666;
    }

    .display_box

    /*estilos para cada caja unitaria de cada usuario que se muestra*/
      {
      padding: 2px;
      padding-left: 6px;
      font-size: 18px;
      height: 30px;
      text-decoration: none;
      color: #000;
      border: solo #333
    }

    .display_box:hover

    /*estilos para cada caja unitaria de cada usuario que se muestra. cuando el mause se pocisiona sobre el area*/
      {
      background: #7f93bc;
      color: #FFF;
    }

    .desc {
      color: #666;
      font-size: 18;
    }

    .desc:hover {
      color: #FFF;
    }

    .menu-ajustes {
      float: right;
      margin: 4px 8px 8px 0;
      font-family: Arial;
      font-size: 13px;
    }
    .menu-ajustes summary {
      cursor: pointer;
      list-style: none;
      padding: 6px 10px;
      border: 1px solid #ccc;
      background: #f1f1f1;
      user-select: none;
    }
    .menu-ajustes summary::-webkit-details-marker { display: none; }
    .menu-ajustes .menu-panel {
      position: absolute;
      right: 8px;
      margin-top: 2px;
      background: #fff;
      border: 1px solid #ccc;
      min-width: 220px;
      z-index: 20;
      padding: 6px 0;
    }
    .menu-ajustes .menu-panel a {
      display: block;
      padding: 8px 12px;
      color: #000;
      text-decoration: none;
    }
    .menu-ajustes .menu-panel a:hover {
      background: #7f93bc;
      color: #fff;
    }
    .menu-ajustes .btn-sync-odoo {
      display: block;
      margin: 6px 10px 8px 10px;
      padding: 8px 10px;
      text-align: center;
      background: #2b6cb0;
      color: #fff !important;
      border: 1px solid #1a4f86;
      border-radius: 3px;
      font-weight: bold;
    }
    .menu-ajustes .btn-sync-odoo:hover {
      background: #1a4f86;
      color: #fff !important;
    }
    .menu-ajustes img {
      vertical-align: middle;
      margin-right: 6px;
    }

    /* Easy Tooltip */




    body {
      font-family: Arial;
      background-image: url();
      background-repeat: no-repeat;
    }

    /* Style the tab */
    .tab {
      overflow: hidden;
      border: 1px solid #ccc;
      background-color: #f1f1f1;
    }

    /* Style the buttons inside the tab */
    .tab button {
      background-color: inherit;
      float: left;
      border: none;
      outline: none;
      cursor: pointer;
      padding: 14px 16px;
      transition: 0.3s;
      font-size: 17px;
    }

    /* Change background color of buttons on hover */
    .tab button:hover {
      background-color: #ddd;
    }

    /* Create an active/current tablink class */
    .tab button.active {
      background-color: #ccc;
    }

    /* Style the tab content */
    .tabcontent {
      display: none;
      padding: 6px 12px;
      border: 1px solid #ccc;
      border-top: none;
    }
  </style>
  <script>
    $(document).ready(function() {
      $('#distrib').DataTable({
        "scrollY": "200px",
        "scrollCollapse": true,
        "paging": false
      });
    });

    function openCity(evt, cityName) {
      var i, tabcontent, tablinks;
      tabcontent = document.getElementsByClassName("tabcontent");
      for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
      }
      tablinks = document.getElementsByClassName("tablinks");
      for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
      }
      document.getElementById(cityName).style.display = "block";
      evt.currentTarget.className += " active";
	  
	  makeTableHTM(items);
    }
  </script>

  <script language="JavaScript" src="jquery-1.5.1.min.js">
    window.onload = init;

    function init() {
      document.getElementById("clave	").focus();
    }
  </script>
  <script language="JavaScript" src="jquery.watermarkinput.js"></script>
  <script type="text/javascript" src="script.js"></script>
  <script> 
  
   
 

// Bang it altogether
document.body.insertAdjacentHTML('beforeend', createTable(data));
   </script>
 
  
  <link rel="stylesheet" type="text/css" href="css/default.css" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  
<body>

  <span class="tablinks">
  <?php
 //
//		$items_sale = $models->execute_kw(
//		$db,
//		$uid,
//		$password,
//		'product.template',
//		'search_read',
//		array(
//		array(
//		array('barcode','=',  false  ) ,
//        array('purchase_ok', '=', false) ,
//        array('sale_ok', '=', true),
//				)), 
//
//		array('fields' => array( 'id','name' ,'list_price','create_uid' 
//		) , 'limit'=>10 )
//		
//			);

	// print  json_encode($items_sale);
 
	// $table_odoo_codigo = '<div>      
//<table border="1"  id="distrib"  class="display" style="width:100%">
//
//<tr><thead>
//<tr>
//<td width="85" colspan="4">
//<center>
//<b>Items en Odoo sin Codigo de barra</bold></center></td></tr>
//<td width="85" ><center><img src="IMG/Red_Alert.gif" width="50" height="35"></center></td>
//<td width="280">Descripción</td>  
//<td width="30" ># Precio</td>
//<td width="100" >Creado Por</td>
//</thead>';
//	 
//	 foreach ($items_sale as $items_cod) 
// 		{
// 
//		$table_odoo_codigo = $table_odoo_codigo . '
//		<tr>
//		<td align=center>
//		<a  href="index.php?code=' . $items_cod["id"] . '&bttn_actualizar=add_item">Agregar Codigo</a>
//		</td>
//		<td align=left>
//		 ' . $items_cod["name"] . '  
//		</td>
//		 <td>' . number_format($items_cod["list_price"],2) . ' </td>  
//      <td>' . $items_cod["create_uid"][1]   . ' </td></tr>';
//			
//			
//		}
//	
//	$table_odoo_codigo = $table_odoo_codigo . '</table>';
	
?>
  </span>
  <title>Buscar Items</title>
  </head>

  <body>
  <p>
  <?php

  if ($table_odoo_fm_apply_result) {
    echo $table_odoo_fm_apply_result;
  }

  if ($table_odoo_fm_clear_result) {
    echo $table_odoo_fm_clear_result;
  }

  if ($table_odoo_fm_check) {
    echo $table_odoo_fm_check;
  }
  
  if (!isset($items_cod)) 
  {

  }
else {
	  print $table_odoo_codigo;
  }
   
    ;?>
  <details class="menu-ajustes">
    <summary>Ajustes ▾</summary>
    <div class="menu-panel">
      <a class="btn-sync-odoo" href="index.php?action=check_fm" title="Compara productos de Odoo con MySQL y actualiza el filtro FM">Sincronizar con Odoo</a>
      <a href="index.php?action=help" title="Help"><img src="IMG/emergency.png" width="15" height="15" alt="">Ayuda</a>
    </div>
  </details>
  <form action="index.php" method="POST">
    <p><strong> Sistema de impresion de Etiquetas.</strong></p>
      <p> Codigo<strong>:</strong>
        <input type="text" class="busca" id="caja_busqueda" name="clave" autocomplete="off" required onKeyDown="settab()" placeholder="Código o nombre" />
        <input type="submit" name="button" id="button" value="Buscar">
</form>
 
 <div class="tab">
<button class="tablinks" onClick="openCity(event, 'A')">A</button>
<button class="tablinks" onClick="openCity(event, 'B')">B</button>
<button class="tablinks" onClick="openCity(event, 'C')">C</button>
<button class="tablinks" onClick="openCity(event, 'D')">D</button>
<button class="tablinks" onClick="openCity(event, 'E')">E</button>
<button class="tablinks" onClick="openCity(event, 'F')">F</button>
<button class="tablinks" onClick="openCity(event, 'G')">G</button>
<button class="tablinks" onClick="openCity(event, 'H')">H</button>     
<button class="tablinks" onClick="openCity(event, 'I')">I</button>   
<button class="tablinks" onClick="openCity(event, 'J')">J</button>  
<button class="tablinks" onClick="openCity(event, 'K')">K</button> 
<button class="tablinks" onClick="openCity(event, 'L')">L</button>
<button class="tablinks" onClick="openCity(event, 'M')">M</button> 
<button class="tablinks" onClick="openCity(event, 'N')">N</button>
<button class="tablinks" onClick="openCity(event, 'O')">O</button>
<button class="tablinks" onClick="openCity(event, 'P')">P</button>
<button class="tablinks" onClick="openCity(event, 'Q')">Q</button>
<button class="tablinks" onClick="openCity(event, 'R')">R</button>
<button class="tablinks" onClick="openCity(event, 'S')">S</button>
<button class="tablinks" onClick="openCity(event, 'T')">T</button>
<button class="tablinks" onClick="openCity(event, 'U')">U</button>
<button class="tablinks" onClick="openCity(event, 'V')">V</button>
<button class="tablinks" onClick="openCity(event, 'W')">W</button>
<button class="tablinks" onClick="openCity(event, 'X')">X</button>
<button class="tablinks" onClick="openCity(event, 'Y')">Y</button>
<button class="tablinks" onClick="openCity(event, 'Z')">Z</button>
<button class="tablinks" onClick="openCity(event, 'London')">TODOS</button>

<!-- (moved) resultados de búsqueda se imprimen debajo del abecedario -->

 
<?php 

			if (	$action == "help")
			{
		 
			$fm_criter_help=  "UPDATE 
			`mypastelito`.`isabel_label_print`
			 SET `estado` = '1'  ";
			
			
			$fm_sql_res = mysqli_query($link, $fm_criter_help);
			
			}
	
				if (	$action == "add_items")
			{
		 
		 
		 	
			$fm_criter_help=  "UPDATE 
			`mypastelito`.`isabel_label_print`
			 SET `estado` = '1'  ";
			
			
			$fm_sql_res = mysqli_query($link, $fm_criter_help);
			
			}
	

?></button>
      </p>
    </div>

<?php
  // Resultados de búsqueda debajo del abecedario (sin quitar la vista).
  if ($search_table_html) {
    echo $search_table_html;
  }
?>
    

<?php 

			 
	function array_items_letters($filter,$link)
	
	{
		
		 $fm_table = '';
		 
		  $fm_criter =  "SELECT items.*, items.descrip AS descripI, lbls.printer AS printer
		   FROM mypastelito.items
		   LEFT JOIN mypastelito.lbls ON lbls.id = items.codigo
		   WHERE items.navidad = 'FM' AND items.Descrip LIKE '".$filter."%'
		   ORDER BY items.descrip ASC";

      $fm_sql_res = mysqli_query($link, $fm_criter);

      $cont_linea = 1;
$abcd = '-';
      while ($fm_row = mysqli_fetch_array($fm_sql_res)) {
        if ($cont_linea % 2 == 0) {
          $bgcolor =  'bgcolor="#C0C0C0"';
        } else {
          $bgcolor =  'bgcolor="#FFFFFF"';
        }


        $cont_linea = $cont_linea + 1;

       $abcd_actual = substr($fm_row["descripI"], 0, 1);

      if  ($abcd_actual  <> $abcd) {


          $abcd = $abcd_actual ;

      }

        $fm_table = $fm_table . '
		<tr ' . $bgcolor . '><td align=center><a  href="search_results.php?code=' . $fm_row["codigo"] . '&bttn_actualizar=FM&txt_codigo2=' . $fm_row["codigo2"] . ' ">' . $fm_row["codigo"] . '</a></td>
	   <td>' . $fm_row["precio2"]   . ' </td> <td align=center>' . htmlspecialchars((string)$fm_row["exp"], ENT_QUOTES, 'UTF-8') . '</td> <td>'  .' '. $fm_row["descripI"] . $fm_row["descrip2"] . '</span></td><td>' . htmlspecialchars((string)$fm_row["printer"], ENT_QUOTES, 'UTF-8') . '</td><td>'   . $fm_row["etiqueta"] . '</span></td></tr>';
      }


      $fm_table = $fm_table . '</table></div>';
	
     
return ($fm_table);
		
		}
		 
		 
		 
		 
		 foreach ($alphabet as &$abcd_list) {
 

		 
		
$array_table = '<div id="'.$abcd_list.'" class="tabcontent">
  
		<table border="0"  id="'.$abcd_list.'"  class="display" style="width:100%">
		<tr><thead>
		 <tr><td align=center><h2>'.$abcd_list.'</h2></td > </tr> 
		<td width="55" ># codigo</td>   

		<td width="15" >Precio</td>
		<td width="50" >Caducidad</td>
		<td width="260">Descripción</td>
		<td width="90" >Printer</td>
		<td width="75" ># Etiqueta</td> 
		</thead>';



$array_table_temp = array_items_letters( $abcd_list ,$link);

$array_table = $array_table.$array_table_temp;
$array_table = $array_table . '</div>';



echo $array_table;

}
		 
?>




    <div id="London" class="tabcontent">
	 
      <?php
 
			 $fm_table = '<div>      
		<table border="0"  id="distrib"  class="display" style="width:100%">
		<tr><thead>
		<td width="55" ># codigo</td>
		<td width="80" ># codigo2</td>	   
		<td width="15" ># Precio Dist</td>
		<td width="15" ># Precio Tienda</td>
		<td width="50" >Caducidad</td>
		<td width="260">Descripción</td>
		<td width="90" >Printer</td>
		<td width="75" ># Etiqueta</td> 
		</thead>';
			 

		
		
	  $fm_criter =  "SELECT items.*, items.descrip AS descripI, lbls.printer AS printer
	   FROM mypastelito.items
	   LEFT JOIN mypastelito.lbls ON lbls.id = items.codigo
	   WHERE items.navidad = 'FM'
	   ORDER BY items.descrip ASC";
 
      $fm_sql_res = mysqli_query($link, $fm_criter);




      $cont_linea = 1;
$abcd = '-';
      while ($fm_row = mysqli_fetch_array($fm_sql_res)) {
        if ($cont_linea % 2 == 0) {
          $bgcolor =  'bgcolor="#C0C0C0"';
        } else {
          $bgcolor =  'bgcolor="#FFFFFF"';
        }


        $cont_linea = $cont_linea + 1;

       $abcd_actual = substr($fm_row["descripI"], 0, 1);

      if  ($abcd_actual  <> $abcd) {

 $fm_table = $fm_table . '<tr><td align=center><h2>' . $abcd_actual . '</h2></td > </tr>';
          $abcd = $abcd_actual ;

      }

        $fm_table = $fm_table . '<tr ' . $bgcolor . '><td align=center><a  href="search_results.php?code=' . $fm_row["codigo"] . '&bttn_actualizar=FM&txt_codigo2=' . $fm_row["codigo2"] . ' ">-' . $fm_row["codigo"] . '</a></td>
		<td align=center><a  href="search_results.php?code=' . $fm_row["codigo"] . '&bttn_actualizar=FM&code2=' . $fm_row["codigo2"] . ' ">' . $fm_row["codigo2"] . '</a></td>
      <td>' . $fm_row["precio1"]   . ' </td> <td>' . $fm_row["precio2"]   . ' </td> <td align=center>' . htmlspecialchars((string)$fm_row["exp"], ENT_QUOTES, 'UTF-8') . '</td> <td>'  .' '. $fm_row["descripI"] . $fm_row["descrip2"] . '</span></td><td>' . htmlspecialchars((string)$fm_row["printer"], ENT_QUOTES, 'UTF-8') . '</td><td>'   . $fm_row["etiqueta"] . '</span></td></tr>';
      }


      $fm_table = $fm_table . '</table></div>';

      echo $fm_table;
      ?>

      
    </div>
    