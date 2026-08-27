 
<?php 
 //header("Content-Type: text/html; charset=utf-8");
/////DBF DATA ///////////
$cnn = 'prueba_pastelito';
$usr = '';
$pass = ''; 
//////////////////////////

///// MYSQL DATA /////////
// (Restaurado) Conexión original del proyecto.
// Si necesitas cambiarlo, cámbialo aquí.
$mysql_adress = "64.210.64.60:3333";
$mysql_user   = "root";
$mysql_pass   = "";
$mysql_db     = "mypastelito";
//////////////////////////

///// ODOO DATA /////////
// Datos tomados de tu otro proyecto (Ripcord/XML-RPC)
$odoo_url      = "https://la-cocina-de-sofy-sh.odoo.com";
$odoo_db       = "la-cocina-de-sofy-sh-main-6859926";
$odoo_username = "sistemas@lacocinadesofy.com";
$odoo_password = "253bb964a4a8f6c4431324ce32bd493a4c7ecacb";
require_once(__DIR__ . DIRECTORY_SEPARATOR . "ripcord.php");
//////////////////////////

function odoo_connect()
{
  global $odoo_url, $odoo_db, $odoo_username, $odoo_password;
  $base = rtrim($odoo_url, "/");
  $common = ripcord::client($base . "/xmlrpc/2/common");
  $uid = $common->authenticate($odoo_db, $odoo_username, $odoo_password, array());
  if (!$uid) {
    throw new Exception("Error autenticando en Odoo.");
  }
  $models = ripcord::client($base . "/xmlrpc/2/object");
  return array($models, $odoo_db, (int)$uid, $odoo_password);
}

function normalize_inventory_code($code)
{
  $s = trim((string)$code);
  if ($s === "") return "";
  if (preg_match('/^\d+$/', $s)) {
    return str_pad((string)((int)$s), 6, "0", STR_PAD_LEFT);
  }
  return $s;
}

function odoo_search_products_with_barcode($models, $db, $uid, $password)
{
  return $models->execute_kw(
    $db,
    $uid,
    $password,
    "product.template",
    "search_read",
    array(
      array(
        array("sale_ok", "=", true)
      )
    ),
    array(
      // default_code = Referencia interna (la que compararemos vs items.codigo)
      "fields" => array("id", "name", "default_code", "barcode"),
    )
  );
}


function conec_mysql() 
{ 
global $mysql_adress, $mysql_user, $mysql_pass, $mysql_db;

$link_mysql = mysqli_connect($mysql_adress, $mysql_user, $mysql_pass);
if (!$link_mysql) 
{ 
echo "Error conectando a la base de datos."; 
exit(); 
} 
if (!mysqli_select_db($link_mysql, $mysql_db)) 
{ 
echo "Error seleccionando la tabla  ."; 
exit(); 
} 

mysqli_set_charset($link_mysql, "utf8mb4");

return $link_mysql; 
} 


function conec_mysql_64bits() 

{ 
if (!($odbc = odbc_connect ('pastelito', '',''))) 
{ 
echo "Error conectando a la base de datos."; 
exit(); 
} 
return $odbc; 
} 




function conec_odbc() 

{ 
if (!($odbc = odbc_connect ('pastelito', '',''))) 
{ 
echo "Error conectando a la base de datos."; 
exit(); 
} 
return $odbc; 
} 



//$odbc_rms_items = odbc_connect ('RMS','sa','Sc.2012') or die('Could Not Connect to ODBC Database!');



function conec_odbc_caja1() 

{ 
if (!($odbc = odbc_connect ('pastelito', '',''))) 
{ 
echo "Error conectando a la base de datos."; 
exit(); 
} 
return $odbc; 
} 

// EJEMPLO DE ODBC. TABLAS DBF DE PASTELITO.
//$query_odbc = "UPDATE facturacab SET tran = ' '  WHERE factura = $fact_activar";
//$odbc_lnk = conec_odbc();
//$queryexe = odbc_do($odbc_lnk, $queryodbc);
//$querycab = odbc_do($odbc_conexion_vfp, $query_odbc_facturacab);



 
?>
