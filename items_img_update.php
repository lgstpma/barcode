<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>

    <script>
        function getUrlVars() {
            var vars = {};

            var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
                vars[key] = value;
            });

            return vars;
        }

        function check() {

            var url_itemid = getUrlVars()["itemid"];
            var url_ln = getUrlVars()["ln"];
            var url_docnum = getUrlVars()["docnum"];

            window.location.href = "windows_details_products1.php?id=" + url_ln + "&trans=PED&docnum=" + url_docnum;
        }
    </script>
    <?php
    include("autentication_seguridad.php");
    include("conections.php");

    $link = conec_mysql();

    $itemid =  @$itemid = $_GET['itemid'];
    $docnum =  @$docnum = $_GET['docnum'];




    $archivo = $_FILES["imagen"]["tmp_name"];
    $tamanio = $_FILES["imagen"]["size"];
    $tipo_img   = $_FILES["imagen"]["type"];
    $nombre  = $_FILES["imagen"]["name"];

    $tipo_img  = substr($tipo_img, 6);

    if ($archivo != "") {
        $fp = fopen($archivo, "rb");
        $contenido = fread($fp, $tamanio);
        $contenido = addslashes($contenido);
        fclose($fp);

        //		$result_guardar_imagen= mysql_query( "
        //		UPDATE 
        //		ordimg 
        //		SET 
        //		img = '$contenido' 
        //		WHERE 
        //		ordid =   $docnum 
        //		AND
        //		itemid =  $itemid ")or die(mysql_error());
        //
        //		if(mysql_affected_rows( $link) > 0)
        //		print "Se ha guardado La Imagen del Equipo en la base de datos.<p>";
        //		else 
        $result_guardar_imagen = mysqli_query($link, "
		INSERT 
		INTO  
		ordimg 
		(img,
		ordid,
		itemid,
		type)
		values
		( 
		'$contenido',
		$docnum,
		$itemid,
		 '$tipo_img')
	 ") or die(mysqli_error($link));
    }







    ?>
    <script>
        check();
    </script>