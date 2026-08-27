<html>
 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
 
<body    >    
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
 
 
<style type="text/css">
#caja_busqueda /*estilos para la caja principal de busqueda*/
{
width:50px;
padding-left:6px;
height:25px;
border:solid 2px #979DAE;
font-size:14px;


}
#caja_busqueda1 /*estilos para la caja principal de busqueda*/
{
width:50px;
padding-left:6px;
height:25px;
border:solid 2px #979DAE;
font-size:14px;


}
#caducidad1 /*estilos para la caja principal de busqueda*/
{
width:50px;
padding-left:6px;
height:25px;
border:solid 2px #979DAE;
font-size:14px;


}
#display /*estilos para la caja principal en donde se puestran los resultados de la busqueda en forma de lista*/
{
width:600px;
display:none;
overflow:hidden;
z-index:10;
border: solid 1px #666;
}
.display_box /*estilos para cada caja unitaria de cada usuario que se muestra*/
{
padding:2px;
padding-left:6px;
font-size:14px;
height:30px;
text-decoration:none;
color:#000; 
border: solo #333
}

.display_box:hover /*estilos para cada caja unitaria de cada usuario que se muestra. cuando el mause se pocisiona sobre el area*/
{
background: #7f93bc;
color: #FFF;
}
.desc
{
color:#666;
font-size:16;
}
.desc:hover
{
color:#FFF;
}

/* Easy Tooltip */
</style>
 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
<link href="/default.css" rel="stylesheet" type="text/css" />
<body>    
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" type="text/css" href="css/default.css"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <script language="JavaScript" src="jquery-1.5.1.min.js">
 
 // Select your input element.
var numInput = document.querySelector('input');

// Listen for input event on numInput.
numInput.addEventListener('input', function(){
    // Let's match only digits.
    var num = this.value.match(/^\d+$/);
    if (num === null) {
        // If we have no match, value will be empty.
        this.value = "";
    }
}, false)
 
  
 </Script>
 
 <script>
 
  function enable_update_button() {
	
	document.getElementById('update_button').disabled=  "true";
	   
  }
  
  
  
  function check_elab(){
	   
	  	var num_label = document.getElementById('etiqueta').value;
		
		
		
				
		 
	     if (num_label === '9') 
		 			{
         		  
		 			document.getElementById('elab_day').disabled= true;
					document.getElementById('caducidad1').disabled= true;
		 
    				}
		
		 
	     if (num_label === '7') 
		 			{
         		  
		 			document.getElementById('elab_day').required = true;
		 
    				}
	  
	  			}
				
				
 function calcular() {
	
var numero = document.getElementById('caducidad1');

var  TuFecha1 = new Date(document.getElementById('elab_day').value); 

var  TuFecha2 = new Date(document.getElementById('elab_day').value); 

  //dias a sumar
var dias = parseInt(numero.value);
  
  //nueva fecha sumada
  
TuFecha1.setDate(TuFecha1.getDate() + dias);
  
    
  
  //formato de salida para la fecha
 resultado.innerText = TuFecha1.getUTCDate() + '/' + (TuFecha1.getUTCMonth()+1)+ '/' + TuFecha1.getUTCFullYear();
  
  
var  hoy = new Date(); 
 
var dif_days  = TuFecha2.getUTCDate() - hoy.getUTCDate();
 
document.getElementById('caja_busqueda1').value = dias ;//dias -
  
	}
 </script>

<?php

include("conections.php");

$link = conec_mysql();
  		
$action_clone =  @$action_clone=$_GET['clone_botton']; 
			
$action =  @$action=$_GET['bttn_actualizar']; 
		
$instruc =  @$instruc=$_GET['instruc']; 

$ingre =  @$ingre=$_GET['ingre']; 

$descrip =  @$descrip=$_GET['descrip']; 

$obs =  @$obs=$_GET['obs']; 

$code =  @$code=$_GET['code']; 

$reg_sanitario =  @$reg_sanitario=$_GET['reg_sanitario']; 

$etiqueta =  @$etiqueta=$_GET['etiqueta']; 
$printer =  @$printer=$_GET['printer']; 
$width =  @$width=$_GET['width']; 
$height =  @$height=$_GET['height']; 

$bar_x =  @$bar_x=$_GET['bar_x']; 
$bar_y =  @$bar_y=$_GET['bar_y']; 
$bar_width =  @$bar_width=$_GET['bar_width']; 
$bar_height =  @$height=$_GET['bar_height']; 


$bar_x2 =  @$bar_x2=$_GET['bar_x2']; 
$bar_y2 =  @$bar_y2=$_GET['bar_y2']; 
$bar_width2 =  @$bar_width2=$_GET['bar_width2']; 
$bar_height2 =  @$bar_height2=$_GET['bar_height2']; 



$design_x =  @$design_x = $_GET['design_x']; 
$design_y =  @$design_y = $_GET['design_y']; 
$design_width =  @$design_width = $_GET['design_width']; 
$design_height =  @$design_height = $_GET['design_height']; 

$temp_id =  $code;
	
$exp_editable = "";
	
 
   
if ($action <> 'Actualizar' and $action <> 'FM' and $action_clone <> 'Clonar') {
	
$temp_id =  $_POST['clave'];  
$temp_id =   sprintf("%06d",$temp_id);
$exp_editable = "";
 
}
else 
{

if ($action == 'FM')
{
$temp_id =  $code;

}
 

if ($action == 'Actualizar')
{
	
$descripcion_font = @$descripcion_font=$_GET['descripcion_font']; 
$descripcion_bold = @$descripcion_bold=$_GET['descripcion_bold']; 
$descripcion_x = @$descripcion_x=$_GET['descripcion_x']; 
$descripcion_y = @$descripcion_y=$_GET['descripcion_y']; 
$descripcion_alcance = @$descripcion_alcance=$_GET['descripcion_alcance']; 
$descripcion_renglon = @$descripcion_renglon=$_GET['descripcion_renglon'];
$descripcion_titulo = @$descripcion_titulo=$_GET['descripcion_titulo'];


$itemid_font = @$itemid_font=$_GET['itemid_font'];
$itemid_bold = @$itemid_bold=$_GET['itemid_bold'];
$itemid_x = @$itemid_x=$_GET['itemid_x'];
$itemid_y = @$itemid_y=$_GET['itemid_y'];
$itemid_alcance = @$itemid_alcance=$_GET['itemid_alcance'];
$itemid_renglon = @$itemid_renglon=$_GET['itemid_renglon'];
$itemid_titulo = @$itemid_titulo=$_GET['itemid_titulo'];


$precio_font = @$precio_font=$_GET['precio_font'];
$precio_bold = @$precio_bold=$_GET['precio_bold'];
$precio_x = @$precio_x=$_GET['precio_x'];
$precio_y = @$precio_y=$_GET['precio_y'];
$precio_alcance = @$precio_alcance=$_GET['precio_alcance'];
$precio_renglon = @$precio_renglon=$_GET['precio_renglon'];
$precio_titulo = @$precio_titulo=$_GET['precio_titulo'];

$fecha_font = @$fecha_font=$_GET['fecha_font'];
$fecha_bold = @$fecha_bold=$_GET['fecha_bold'];
$fecha_x = @$fecha_x=$_GET['fecha_x'];
$fecha_y = @$fecha_y=$_GET['fecha_y'];
$fecha_alcance = @$fecha_alcance=$_GET['fecha_alcance'];
$fecha_renglon = @$fecha_renglon=$_GET['fecha_renglon'];
$fecha_titulo = @$fecha_titulo=$_GET['fecha_titulo'];

$ingredientes_font =  @$ingredientes_font=$_GET['ingredientes_font'];
$ingredientes_bold =  @$ingredientes_bold=$_GET['ingredientes_bold'];
$ingredientes_x =  @$ingredientes_x=$_GET['ingredientes_x'];
$ingredientes_y =  @$ingredientes_y=$_GET['ingredientes_y'];
$ingredientes_alcance =  @$ingredientes_alcance=$_GET['ingredientes_alcance'];
$ingredientes_renglon =  @$ingredientes_renglon=$_GET['ingredientes_renglon'];
$ingredientes_titulo =  @$ingredientes_titulo=$_GET['ingredientes_titulo'];

$especif_font =  @$especif_font=$_GET['especif_font'];
$especif_bold =  @$especif_bold=$_GET['especif_bold'];
$especif_x =  @$especif_x=$_GET['especif_x'];
$especif_y =  @$specif_y=$_GET['especif_y'];
$especif_alcance =  @$especif_alcance=$_GET['especif_alcance'];
$especif_renglon =  @$especif_renglon=$_GET['especif_renglon'];
$especif_titulo =  @$especif_titulo=$_GET['especif_titulo'];

$reg_sanitario_font =  @$reg_sanitario_font=$_GET['reg_sanitario_font'];
$reg_sanitario_bold =  @$reg_sanitario_bold=$_GET['reg_sanitario_bold'];
$reg_sanitario_x =  @$reg_sanitario_x=$_GET['reg_sanitario_x'];
$reg_sanitario_y =  @$reg_sanitario_y=$_GET['reg_sanitario_y'];
$reg_sanitario_alcance =  @$reg_sanitario_alcance=$_GET['reg_sanitario_alcance'];
$reg_sanitario_renglon =  @$reg_sanitario_renglon=$_GET['reg_sanitario_renglon'];
$reg_sanitario_titulo =  @$reg_sanitario_titulo=$_GET['reg_sanitario_titulo'];
 
$obs_font =  @$obs_font=$_GET['obs_font'];
$obs_bold =  @$obs_bold=$_GET['obs_bold'];
$obs_x =  @$obs_x=$_GET['obs_x'];
$obs_y =  @$obs_y=$_GET['obs_y'];
$obs_alcance =  @$obs_alcance=$_GET['obs_alcance'];
$obs_renglon =  @$obs_renglon=$_GET['obs_renglon'];
$obs_titulo =  @$obs_titulo=$_GET['obs_titulo'];
 
 
  
$reg_sanitario =  @$reg_sanitario=$_GET['reg_sanitario']; 
$etiqueta =  @$etiqueta=$_GET['etiqueta']; 
$printer =  @$printer=$_GET['printer']; 
$width =  @$width=$_GET['width']; 
$height =  @$height=$_GET['height']; 

  

			$query_update = "
			UPDATE 
			`mypastelito`.`items` 
			SET 
			`especif`='". $instruc."',
			`ingredientes`='".$ingre."',
			`descrip3`='".$descrip."',
			`etiqueta`='".$etiqueta ."',
			`reg_sanitario`='".$reg_sanitario ."',
			`obs`='".$obs ."' 
			WHERE
			`codigo`='".$code."'";
				
			$result_item = mysqli_query($link,$query_update) ;


			$query_update_lbl = "
			UPDATE 
			`mypastelito`.`lbls` 
			SET 
			`width`= ". $width.",
			`height`=".$height.",
			`printer`= '".$printer."',
			`bar_x`= ".$bar_x .",
			`bar_y`= ".$bar_y .",
			`bar_width`= ".$bar_width .",	
			`bar_height`= ".$bar_height.",
			`FM_bar_x`= ".$bar_x2 .",
			`FM_bar_y`= ".$bar_y2 .",
			`FM_bar_width`= ".$bar_width2 .",	
			`FM_bar_height`= ".$bar_height2.",
			`design_x`= ".$design_x .",
			`design_y`= ".$design_y .",
			`design_width`= ".$design_width .",	
			`design_height`= ".$design_height." 	 	 						 
			WHERE
			`id`='".$code."'";
				
			$result_item_update_lbl = mysqli_query($link,$query_update_lbl) ;





			$query_lbl_config_update = "
			UPDATE 
			`mypastelito`.`lbl_lines`
			SET
			`font`='$descripcion_font',
			`bold`='$descripcion_bold', 
			`x`='$descripcion_x', 
			`y`='$descripcion_y',
			`alcance`='$descripcion_alcance', 
			`renglon`='$descripcion_renglon', 
			`titulo`='$descripcion_titulo' 
			WHERE `lblid` = '".$code."'
			AND
			descrip = 'descripcion'";
			
			$result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;


			$query_lbl_config_update = "
			UPDATE 
			`mypastelito`.`lbl_lines`
			SET
			`font`='$itemid_font',
			`bold`='$itemid_bold', 
			`x`='$itemid_x', 
			`y`='$itemid_y',
			`alcance`='$itemid_alcance', 
			`renglon`='$itemid_renglon', 
			`titulo`='$itemid_titulo' 
			
			WHERE `lblid`='".$code."'
			AND
			descrip = 'itemid'
			
			" 
			;
			
			$result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;

			$query_lbl_config_update = "
			UPDATE 
			`mypastelito`.`lbl_lines`
			SET
			`font`='$precio_font',
			`bold`='$precio_bold', 
			`x`='$precio_x', 
			`y`='$precio_y',
			`alcance`='$precio_alcance', 
			`renglon`='$precio_renglon', 
			`titulo`='$precio_titulo' 
			
			WHERE `lblid`='".$code."'
			AND
			descrip = 'precio'
			
			" 
			;
			
			$result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;



		  $query_lbl_config_update = "
		  UPDATE 
		  `mypastelito`.`lbl_lines`
		  SET
		  `font`='$fecha_font',
		  `bold`='$fecha_bold', 
		  `x`='$fecha_x', 
		  `y`='$fecha_y',
		  `alcance`='$fecha_alcance', 
		  `renglon`='$fecha_renglon', 
		  `titulo`='$fecha_titulo' 
		  
		  WHERE `lblid`='".$code."'
		  AND
		  descrip = 'fecha'";

		  $result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;


		  $query_lbl_config_update = "
		  UPDATE 
		  `mypastelito`.`lbl_lines`
		  SET
		  `font`='$ingredientes_font',
		  `bold`='$ingredientes_bold', 
		  `x`='$ingredientes_x', 
		  `y`='$ingredientes_y',
		  `alcance`='$ingredientes_alcance', 
		  `renglon`='$ingredientes_renglon', 
		  `titulo`='$ingredientes_titulo' 
		  
		  WHERE `lblid`='".$code."'
		  AND
		  descrip = 'ingredientes'";

		  $result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;



		  $query_lbl_config_update = "
		  UPDATE 
		  `mypastelito`.`lbl_lines`
		  SET
		  `font`='$especif_font',
		  `bold`='$especif_bold', 
		  `x`='$especif_x', 
		  `y`='$especif_y',
		  `alcance`='$especif_alcance', 
		  `renglon`='$especif_renglon', 
		  `titulo`='$especif_titulo' 
		  
		  WHERE `lblid`='".$code."'
		  AND
		  descrip = 'especif'";

		  $result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;


		  $query_lbl_config_update = "
		  UPDATE 
		  `mypastelito`.`lbl_lines`
		  SET
		  `font`='$reg_sanitario_font',
		  `bold`='$reg_sanitario_bold', 
		  `x`='$reg_sanitario_x', 
		  `y`='$reg_sanitario_y',
		  `alcance`='$reg_sanitario_alcance', 
		  `renglon`='$reg_sanitario_renglon', 
		  `titulo`='$reg_sanitario_titulo' 
		  WHERE `lblid`='".$code."'
		  AND
		  descrip = 'reg_sanitario'";

$result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;

  $query_lbl_config_update = "
		  UPDATE 
		  `mypastelito`.`lbl_lines`
		  SET
		  `font`='$obs_font',
		  `bold`='$obs_bold', 
		  `x`='$obs_x', 
		  `y`='$obs_y',
		  `alcance`='$obs_alcance', 
		  `renglon`='$obs_renglon', 
		  `titulo`='$obs_titulo' 
		  WHERE `lblid`='".$code."'
		  AND
		  descrip = 'obs'";

$result_lbl_config_update = mysqli_query($link,$query_lbl_config_update) ;



	}
	
	
	 echo $action_clone;
  
if ($action_clone == 'Clonar')
{
	
	 		  
	  $cloneto = @$code_clone=$_GET['code_clone']; 

  
	  $descripcion_font = @$descripcion_font=$_GET['descripcion_font']; 
	  $descripcion_bold = @$descripcion_bold=$_GET['descripcion_bold']; 
	  $descripcion_x = @$descripcion_x=$_GET['descripcion_x']; 
	  $descripcion_y = @$descripcion_y=$_GET['descripcion_y']; 
	  $descripcion_alcance = @$descripcion_alcance=$_GET['descripcion_alcance']; 
	  $descripcion_renglon = @$descripcion_renglon=$_GET['descripcion_renglon'];
	  $descripcion_titulo = @$descripcion_titulo=$_GET['descripcion_titulo'];
	  
	  
	  $itemid_font = @$itemid_font=$_GET['itemid_font'];
	  $itemid_bold = @$itemid_bold=$_GET['itemid_bold'];
	  $itemid_x = @$itemid_x=$_GET['itemid_x'];
	  $itemid_y = @$itemid_y=$_GET['itemid_y'];
	  $itemid_alcance = @$itemid_alcance=$_GET['itemid_alcance'];
	  $itemid_renglon = @$itemid_renglon=$_GET['itemid_renglon'];
	  $itemid_titulo = @$itemid_titulo=$_GET['itemid_titulo'];

	  
	  $precio_font = @$precio_font=$_GET['precio_font'];
	  $precio_bold = @$precio_bold=$_GET['precio_bold'];
	  $precio_x = @$precio_x=$_GET['precio_x'];
	  $precio_y = @$precio_y=$_GET['precio_y'];
	  $precio_alcance = @$precio_alcance=$_GET['precio_alcance'];
	  $precio_renglon = @$precio_renglon=$_GET['precio_renglon'];
	  $precio_titulo = @$precio_titulo=$_GET['precio_titulo'];
	  
	  $fecha_font = @$fecha_font=$_GET['fecha_font'];
	  $fecha_bold = @$fecha_bold=$_GET['fecha_bold'];
	  $fecha_x = @$fecha_x=$_GET['fecha_x'];
	  $fecha_y = @$fecha_y=$_GET['fecha_y'];
	  $fecha_alcance = @$fecha_alcance=$_GET['fecha_alcance'];
	  $fecha_renglon = @$fecha_renglon=$_GET['fecha_renglon'];
	  $fecha_titulo = @$fecha_titulo=$_GET['fecha_titulo'];
	  
	  $ingredientes_font =  @$ingredientes_font=$_GET['ingredientes_font'];
	  $ingredientes_bold =  @$ingredientes_bold=$_GET['ingredientes_bold'];
	  $ingredientes_x =  @$ingredientes_x=$_GET['ingredientes_x'];
	  $ingredientes_y =  @$ingredientes_y=$_GET['ingredientes_y'];
	  $ingredientes_alcance =  @$ingredientes_alcance=$_GET['ingredientes_alcance'];
	  $ingredientes_renglon =  @$ingredientes_renglon=$_GET['ingredientes_renglon'];
	  $ingredientes_titulo =  @$ingredientes_titulo=$_GET['ingredientes_titulo'];

	  $especif_font =  @$especif_font=$_GET['especif_font'];
	  $especif_bold =  @$especif_bold=$_GET['especif_bold'];
	  $especif_x =  @$especif_x=$_GET['especif_x'];
	  $especif_y =  @$specif_y=$_GET['especif_y'];
	  $especif_alcance =  @$especif_alcance=$_GET['especif_alcance'];
	  $especif_renglon =  @$especif_renglon=$_GET['especif_renglon'];
	  $especif_titulo =  @$especif_titulo=$_GET['especif_titulo'];
	  
	  $reg_sanitario_font =  @$reg_sanitario_font=$_GET['reg_sanitario_font'];
	  $reg_sanitario_bold =  @$reg_sanitario_bold=$_GET['reg_sanitario_bold'];
	  $reg_sanitario_x =  @$reg_sanitario_x=$_GET['reg_sanitario_x'];
	  $reg_sanitario_y =  @$reg_sanitario_y=$_GET['reg_sanitario_y'];
	  $reg_sanitario_alcance =  @$reg_sanitario_alcance=$_GET['reg_sanitario_alcance'];
	  $reg_sanitario_renglon =  @$reg_sanitario_renglon=$_GET['reg_sanitario_renglon'];
	  $reg_sanitario_titulo =  @$reg_sanitario_titulo=$_GET['reg_sanitario_titulo'];
			 
	  $obs_font =  @$obs_font=$_GET['obs_font'];
	  $obs_bold =  @$obs_bold=$_GET['obs_bold'];
	  $obs_x =  @$obs_x=$_GET['obs_x'];
	  $obs_y =  @$obs_y=$_GET['obs_y'];
	  $obs_alcance =  @$obs_alcance=$_GET['obs_alcance'];
	  $obs_renglon =  @$obs_renglon=$_GET['obs_renglon'];
	  $obs_titulo =  @$obs_titulo=$_GET['obs_titulo'];
 		
	  $reg_sanitario =  @$reg_sanitario=$_GET['reg_sanitario']; 
	  $etiqueta =  @$etiqueta=$_GET['etiqueta']; 
	  $printer =  @$printer=$_GET['printer']; 
	  $width =  @$width=$_GET['width']; 
	  $height =  @$height=$_GET['height']; 

  
  
  
  $query_insert_lbl = "
  INSERT 
  INTO 
  `mypastelito`.`lbls` 
  (`id`, 
  `descrip`, 
  `width`, 
  `height`,
  `printer`,
  `bar_x`, 
  `bar_y`, 
  `bar_width`,
  `bar_height`,
  `FM_bar_x`,
  `FM_bar_y`,
  `FM_bar_width`,
  `FM_bar_height`)
   VALUES 
   (
   '" .$cloneto ."',
   'lbl7 2.5x1.5', 
   '". $width."',
   '".$height."', 
   '".$printer."',
   '".$bar_x ."',
   '".$bar_y ."',
   '".$bar_width ."',
   '".$bar_height."',
   '".$bar_x2 ."',
   '".$bar_y2 ."',
   '".$bar_width2 ."',
   '".$bar_height2."')";

		echo   $query_insert_lbl ;		
  $result_item__insert_lbl = mysqli_query($link,$query_insert_lbl) ;



	$query_lbl_config_insert_descripcion = "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'descripcion',
	 '$descripcion_font',
	 '$descripcion_bold',
	 '$descripcion_x',
	 '$descripcion_y',
	 '$descripcion_alcance',
	 '$descripcion_renglon',
	 '$descripcion_titulo')";
  	
	  $result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_descripcion) ;

 
	$query_lbl_config_insert_itemid = "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'itemid',
	 '$itemid_font',
	 '$itemid_bold',
	 '$itemid_x',
	 '$itemid_y',
	 '$itemid_alcance',
	 '$itemid_renglon',
	 '$itemid_titulo')";
 	
 $result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_itemid) ;


	$query_lbl_config_insert_precio= "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'precio',
	 '$precio_font',
	 '$precio_bold',
	 '$precio_x',
	 '$precio_y',
	 '$precio_alcance',
	 '$precio_renglon',
	 '$precio_titulo')";
 	
 $result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_precio) ;
 
 
$query_lbl_config_insert_fecha = "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'fecha',
	 '$fecha_font',
	 '$fecha_bold',
	 '$fecha_x',
	 '$fecha_y',
	 '$fecha_alcance',
	 '$fecha_renglon',
	 '$fecha_titulo')";
 	
 	$result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_fecha) ;
 	
	
	
	$query_lbl_config_insert_ingredientes = "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'ingredientes',
	 '$ingredientes_font',
	 '$ingredientes_bold',
	 '$ingredientes_x',
	 '$ingredientes_y',
	 '$ingredientes_alcance',
	 '$ingredientes_renglon',
	 '$ingredientes_titulo')";
 	
 	$result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_ingredientes) ;
		 
		 
 	$query_lbl_config_insert_especif = "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'especif',
	 '$especif_font',
	 '$especif_bold',
	 '$especif_x',
	 '$especif_y',
	 '$especif_alcance',
	 '$especif_renglon',
	 '$especif_titulo')";
 	
 $result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_especif) ;


 $query_lbl_config_insert_reg_sanitario = "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'reg_sanitario',
	 '$reg_sanitario_font',
	 '$reg_sanitario_bold',
	 '$reg_sanitario_x',
	 '$reg_sanitario_y',
	 '$reg_sanitario_alcance',
	 '$reg_sanitario_renglon',
	 '$reg_sanitario_titulo')";
 	
 $result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_reg_sanitario);


$query_lbl_config_insert_obs= "
	INSERT 
	INTO
	`mypastelito`.`lbl_lines` 
	(`lblid`,
	`descrip`,
	`font`,
	`bold`,
	`x`,
	`y`,
	`alcance`,
	`renglon`,
	`titulo`)
	 VALUES
	 ('".$cloneto."',
	 'obs',
	 '$obs_font',
	 '$obs_bold',
	 '$obs_x',
	 '$obs_y',
	 '$obs_alcance',
	 '$obs_renglon',
	 '$obs_titulo')";
 	
 $result_lbl_config_insert = mysqli_query($link,$query_lbl_config_insert_obs);


}
  
	
	}


$query_id_items = "
SELECT 
*
FROM
items
WHERE 
codigo = '".$temp_id."'";

  
//echo $query_id_items;
$result_item = mysqli_query($link,$query_id_items) ;
$row = mysqli_fetch_array($result_item);
  
 
$query_lbl_items = "
SELECT 
*
FROM
lbls
WHERE 
id = '".$temp_id."'";


$result_lbl_items= mysqli_query($link,$query_lbl_items);

$row_lbl_items = mysqli_fetch_array($result_lbl_items);
   
  if ($row['exp'] == 1 ) 
  
  {$exp_editable = ' hidden';}
	   
    
 ?>
<html>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<body bgcolor="#FFFFFF"  onload="check_elab();"  >
<SCRIPT language=JavaScript>
<!-- 
function win()
{
window.opener.location.href="../facturacion_index.php";
self.close();
//-->
}
</SCRIPT>
<form name="form1" method="post" action= "exportarimg.php"  target="_self">
<table width="254" height="119" border="1" cellspacing="1">
  <tr>
    <td height="24" colspan="2" bgcolor="#FFFFFF"><img  src="barcode.php?text=<?php echo $row['codigo']; ?>&amp;texto=<?php echo $row['descrip']; ?>&amp;texto2=<?php echo $row['descrip2']; ?>&amp;precio=<?php echo $row['precio1']; ?>" alt="testing" width="115" height="20" border="0" align="top" id="barcode1"  wid="wid"/>
      <?php  echo $row['codigo'] ?></td>
  </tr>
  <tr>
    <td height="24" colspan="2" bgcolor="#FFFFFF" ><?php echo( $row['descrip']); ?><strong>
      <input type="text" hidden="hidden" name="txt_descrip" id="textfield3" value="<?php echo( $row['descrip']); ?>" />
      <input type="text" hidden="hidden" name="txt_codigo" id="txt_codigo" value="<?php  echo $row['codigo'] ?>" />
    </strong></td>
  </tr>
  <tr>
    <td height="24" colspan="2" bgcolor="#FFFFFF" ><?php echo  $row['descrip2'] ?><strong>
      <input type="text" hidden="hidden"  name="txt_descrip2" id="textfield" value="<?php echo  $row['descrip2'] ?> " />
    </strong></td>
  </tr>
  <tr>
 
    <td width="246" bgcolor="#FFFFFF" ><strong> B/.
      <?php  echo $row['precio2'] ?>
      <input type="text"  hidden="hidden" name="txt_precio" id="textfield4" value="<?php  echo $row['precio1'] ?>" />
    </strong></td>
  </tr>
</table>
<table width="574" border="0">
  <tr>
    <td align="left">&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td width="191" align="left"><strong>  Cantidad: 
    </strong></td>
    <td width="348"><strong>
      <input type="number"   min="0"  align="left" name="cant" id="caja_busqueda" value="" autocomplete="off" required   />
    </strong></td>
    <td width="21">&nbsp;</td>
    </tr>
  <tr>
    <td height="29" align="left"><strong>Fecha Elaboración:</strong></td>
    <td><input type="date"    name="elab_day"  id="elab_day"  value=""    required="required"  onchange="calcular()"  /></td>
<td></td>
</tr>
  <tr>
    <td height="28"><strong> Dias de vencimiento: </strong></td>
    <td colspan="2"><strong>
      <input type="number" min="0" align="left"  name="caducidad1" id="caducidad1" value=""   autocomplete="off"  onkeyup="calcular()" />
      <em>Caduca:&nbsp;<span id="resultado">
      
      </span></em></strong></td>
  </tr>
  <tr>
    <td height="28">&nbsp;</td>
    <td colspan="2"><strong>
      <input  hidden   type="number"  min="0" align="left"  name="caducidad" id="caja_busqueda1" value="" autocomplete="off"  onkeyup="calcular()"  />
    </strong></td>
  </tr>
  <tr>
    <td height="28">&nbsp;</td>
    <td colspan="2"><strong>
      <input name="Submit" type=submit  onclick="update_dias()"  value="Imprimir"/>
    </strong></td>
    </tr>
  	<tr>
    <td height="28">&nbsp;</td>
    <td colspan="2"><strong><div class="txt-heading"><a id="btnEmpty" href="index.php">[Nueva Busqueda]</a></div>
      </strong></td>
  </tr>
  <tr>
    <td height="28">&nbsp;</td>
    <td colspan="2">&nbsp;</td>
  </tr>
</table>
</form>
<form>
  <table width="1000" border="0">
    <tr>
      <td align="left"><strong>Descripción:</strong></td>
      <td colspan="8"><strong>
        <input  type="text" name="descrip" id="descrip" min="0" autocomplete="off" value="<?php echo( $row['descrip3']); ?>" size="100" align="left"  />
      </strong></td>
    </tr>
    <tr>
      <td width="146" align="left"><strong> Ingredientes:</strong></td>
      <td colspan="8"><strong>
        <input  type="text" name="ingre" id="ingre" min="0" autocomplete="off" value="<?php echo( $row['ingredientes']); ?>" size="100" align="left"   />
      </strong></td>
    </tr>
    <tr>
      <td height="26" align="left"><strong> Instrucciones: </strong></td>
      <td colspan="8"><strong>
        <input  type="text" name="instruc" id="instruc" min="0" autocomplete="off" value="<?php echo( $row['especif']); ?>" size="100" align="left"   />
      </strong></td>
    </tr>
    <tr>
      <td height="41"><strong>alérgenos:</strong></td>
      <td colspan="8"><strong>
        <input  type="text" name="obs" id="obs" min="0" autocomplete="off" value="<?php echo( $row['obs']); ?>" size="100" align="left"   />
      </strong></td>
    </tr>
    <tr>
      <td height="40"><strong>Registro Sanitario:</strong></td>
      <td width="212"><strong>
        <input  type="text" name="reg_sanitario" id="reg_sanitario" min="0" autocomplete="off" value="<?php echo( $row['reg_sanitario']); ?>" size="10" align="left"   />
      </strong></td>
      <td colspan="2" bgcolor="#CCCCCC">Codigo de Barra:</td>
      <td colspan="2" bgcolor="#CCCCCC">FM Logo:</td>
      <td colspan="2" bgcolor="#CCCCCC">Diseño</td>
      <td width="324">&nbsp;</td>
    </tr>
    <tr>
      <td height="27">Tipo Etiqueta:</td>
      <td><strong>
        <input  type="type" name="etiqueta" id="etiqueta" min="0" autocomplete="off" value="<?php echo( $row['etiqueta']); ?>" size="5" align="left"   />
      </strong></td>
      <td width="48" bgcolor="#CCCCCC">X:</td>
      <td width="35" bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_x" id="bar_x" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['bar_x']); ?>" size="5" align="left"   />
      </strong></td>
      <td width="47" bgcolor="#CCCCCC">X: </td>
      <td width="31" bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_x2" id="bar_x2" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['FM_bar_x']); ?>" size="5" align="left"   />
      </strong></td>
      <td width="59" bgcolor="#CCCCCC">X: </td>
      <td width="60" bgcolor="#CCCCCC"><strong>
        <input  type="text" name="design_x" id="design_x" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['design_x']); ?>" size="5" align="left"   />
      </strong></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="27">Impresora:</td>
      <td><strong>
        <input  type="text" name="printer" id="printer" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['printer']); ?>" size="15" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Y:</td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_y" id="bar_y" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['bar_y']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Y: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_y2" id="bar_y2" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['FM_bar_y']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Y: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="design_y" id="design_y" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['design_y']); ?>" size="5" align="left"   />
      </strong></td>
      <td><strong>
        <label for="checkbox"></label>
      </strong></td>
    </tr>
    <tr>
      <td height="30">Ancho:</td>
      <td><strong>
        <input  type="text" name="width" id="width" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['width']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Ancho:</td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_width" id="bar_width" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['bar_width']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Ancho: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_width2" id="bar_width2" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['FM_bar_width']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Ancho: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="design_width" id="design_width" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['design_width']); ?>" size="5" align="left"   />
      </strong></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="25">Alto:</td>
      <td><strong>
        <input  type="text" name="height" id="height" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['height']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Alto: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_height" id="bar_height" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['bar_height']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Alto: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="bar_height2" id="bar_height2" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['FM_bar_height']); ?>" size="5" align="left"   />
      </strong></td>
      <td bgcolor="#CCCCCC">Alto: </td>
      <td bgcolor="#CCCCCC"><strong>
        <input  type="text" name="design_height" id="design_height" min="0" autocomplete="off" value="<?php echo( $row_lbl_items['design_height']); ?>" size="5" align="left"   />
      </strong></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="25">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="28" align="right">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="28" align="right">copiar a </td>
      <td><strong>
        <input  type="text" name="code_clone" id="code_clone" min="0" autocomplete="off" value=" " size="30" align="left"   />
        <input name="clone_botton" type="submit"   value="Clonar"  id="clone_botton"  onclick="search_results.php?code=document.getElementById('txt_codigo').value&amp;especif=document.getElementById('instruc').value&amp;instruct=document.getElementById('ingre').value"   disabled="disabled" />
        <input type="checkbox" name="checkbox2" id="checkbox2"   onchange="document.getElementById('clone_botton').disabled = !this.checked;" />
      </strong></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
    <td height="28" align="right">&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    </tr>
    <tr>
    
    <table>
    <td align="center"></td>
     <td  align="center">Font</td>
	  <td align="center">Bold</td>
      <td align="center">X</td>
	   <td  align="center">Y</td>
	     <td align="center">Alcance</td>
		   <td  align="center">Renglon</td>
		      <td align="center">Titulo</td>
            
    <?php 
	
 
$query_lbl_config = "
SELECT 
*
FROM
lbl_lines
WHERE 
lblid = '".$temp_id."'";


$result_lbl_config= mysqli_query($link,$query_lbl_config);
  
	
		  while($row_lbl_config = mysqli_fetch_array($result_lbl_config))
				{	
				
print '  <tr><td ">'.$row_lbl_config['descrip'].'</td>
     
	 
	  <td>
	  <input  type="text" name="'.$row_lbl_config['descrip'].'_font" id="'.$row_lbl_config['descrip'].'_font" min="0" autocomplete="off" value="'.$row_lbl_config['font'].'" size="5" align="left"/></td>
	  <td   align="right"><input  type="text" name="'.$row_lbl_config['descrip'].'_bold" id="'.$row_lbl_config['descrip'].'_bold" min="0" autocomplete="off" value="'.$row_lbl_config['bold'].'" size="5" align="left"   /></td>
      <td><input  type="text" name="'.$row_lbl_config['descrip'].'_x" id="'.$row_lbl_config['descrip'].'_x" min="0" autocomplete="off" value="'.$row_lbl_config['x'].'" size="5" align="left"   /></td>
	   <td><input  type="text" name="'.$row_lbl_config['descrip'].'_y" id="'.$row_lbl_config['descrip'].'_y" min="0" autocomplete="off" value="'.$row_lbl_config['y'].'" size="5" align="left"   /></td>
	     <td><input  type="text" name="'.$row_lbl_config['descrip'].'_alcance" id="'.$row_lbl_config['descrip'].'_alcance" min="0" autocomplete="off" value="'.$row_lbl_config['alcance'].'" size="5" align="left"   /></td>
		   <td><input  type="text" name="'.$row_lbl_config['descrip'].'_renglon" id="'.$row_lbl_config['descrip'].'_renglon" min="0" autocomplete="off" value="'.$row_lbl_config['renglon'].'" size="5" align="left"   /></td>
		      <td><input  type="text" name="'.$row_lbl_config['descrip'].'_titulo" id="'.$row_lbl_config['descrip'].'_titulo" min="0" autocomplete="off" value="'.$row_lbl_config['titulo'].'" size="20" align="left"   /></td>
	 
	 
		   '	;			
				
				}
	
	
	?>
    
      
      </table>
      <td height="28" align="right">Imagen de Etiqueta</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="28" align="right">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td height="28"><strong>
        <input type="text" hidden="hidden" name="code" id="code" value="<?php  echo $row['codigo'] ?>" />
      </strong></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><strong>
        <input name="bttn_actualizar" type="submit"   value="Actualizar"  id="update_button"  onclick="search_results.php?code=document.getElementById('txt_codigo').value&especif=document.getElementById('instruc').value&instruct=document.getElementById('ingre').value"   disabled="disabled" />
        <input type="checkbox" name="checkbox" id="checkbox"   onchange="document.getElementById('update_button').disabled = !this.checked;" />
      </strong></td>
    </tr>
    <tr>
      <td   colspan="9"></td>
    </tr>
    <tr>
      <td   colspan="9"></td>
    </tr>
    <tr>
      <td   colspan="9"></td>
    </tr>
  </table>

</form>
</body>
</html>
<SCRIPT language=JavaScript>  window.opener.location.reload();
self.close();
</SCRIPT>
</html>

 