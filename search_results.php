<html>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>

<body>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />


	<style type="text/css">
		#caja_busqueda

		/*estilos para la caja principal de busqueda*/
			{
			width: 50px;
			padding-left: 6px;
			height: 25px;
			border: solid 2px #979DAE;
			font-size: 14px;


		}

		#caja_busqueda1

		/*estilos para la caja principal de busqueda*/
			{
			width: 50px;
			padding-left: 6px;
			height: 25px;
			border: solid 2px #979DAE;
			font-size: 14px;


		}

		#caducidad1

		/*estilos para la caja principal de busqueda*/
			{
			width: 50px;
			padding-left: 6px;
			height: 25px;
			border: solid 2px #979DAE;
			font-size: 14px;


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
			font-size: 14px;
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
			font-size: 16;
		}

		.desc:hover {
			color: #FFF;
		}

		/* Easy Tooltip */
	</style>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

	<head>
		<link href="/default.css" rel="stylesheet" type="text/css" />

	<body>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="css/default.css" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<script language="JavaScript" src="jquery-1.5.1.min.js">
			// Select your input element.
			var numInput = document.querySelector('input');

			// Listen for input event on numInput.
			numInput.addEventListener('input', function() {
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

				document.getElementById('update_button').disabled = "true";

			}



			function check_elab() {

				var num_label = document.getElementById('etiqueta').value;





				if (num_label === '9') {

					document.getElementById('elab_day').disabled = true;
					document.getElementById('caducidad1').disabled = true;

				}

	if (num_label === '12') {

					document.getElementById('elab_day').disabled = true;
					document.getElementById('caducidad1').disabled = true;

				}
				if (num_label === '7') {

					document.getElementById('elab_day').required = true;

				}

			}


			function calcular() {

				var numero = document.getElementById('caducidad1');

				var TuFecha1 = new Date(document.getElementById('elab_day').value);

				var TuFecha2 = new Date(document.getElementById('elab_day').value);

				//dias a sumar
				var dias = parseInt(numero.value);

				//nueva fecha sumada

				TuFecha1.setDate(TuFecha1.getDate() + dias);

				resultado.innerText = TuFecha1.getUTCDate() + '/' + (TuFecha1.getUTCMonth() + 1) + '/' + TuFecha1.getUTCFullYear();


				var hoy = new Date();

				var dif_days = TuFecha2.getUTCDate() - hoy.getUTCDate();

				document.getElementById('caja_busqueda1').value = dias; //dias -

			}

			function preview_zpl_label() {
				var codeEl = document.getElementById('code') || document.getElementById('txt_codigo');
				var code = codeEl ? codeEl.value : '';
				var cadEl = document.getElementById('caducidad1');
				var elabEl = document.getElementById('elab_day');
				var cad = cadEl && cadEl.value !== '' ? cadEl.value : '0';
				var elab = elabEl ? elabEl.value : '';
				var st = document.getElementById('zpl_preview_status');
				var img = document.getElementById('zpl_preview_img');
				var ta = document.getElementById('zpl_preview_text');
				if (!code) {
					if (st) st.innerHTML = 'Falta codigo';
					return;
				}
				if (st) st.innerHTML = 'Generando vista...';
				if (img) {
					img.style.display = 'none';
					img.removeAttribute('src');
				}
				if (ta) {
					ta.value = '';
					ta.style.display = 'none';
				}
				var base = 'codigo=' + encodeURIComponent(code)
					+ '&caducidad=' + encodeURIComponent(cad)
					+ '&elab_day=' + encodeURIComponent(elab)
					+ '&_=' + (new Date().getTime());
				var xhr = new XMLHttpRequest();
				xhr.open('GET', 'preview_zpl.php?fmt=json&' + base, true);
				xhr.timeout = 60000;
				xhr.onreadystatechange = function () {
					if (xhr.readyState !== 4) return;
					if (xhr.status < 200 || xhr.status >= 300) {
						if (st) st.innerHTML = 'Error al pedir vista previa (' + xhr.status + ')';
						return;
					}
					var r = null;
					try { r = JSON.parse(xhr.responseText); } catch (e1) { r = null; }
					if (!r || !r.ok) {
						if (st) st.innerHTML = (r && r.error) ? r.error : 'No se pudo generar ZPL';
						return;
					}
					if (st) {
						st.innerHTML = 'Tipo ' + r.etiqueta + ' — ' + r.pw + 'x' + r.ll + ' dots. Cargando imagen...';
					}
					if (img) {
						img.onload = function () {
							img.style.display = 'inline';
							if (st) {
								st.innerHTML = 'Tipo ' + r.etiqueta + ' — ' + r.pw + 'x' + r.ll + ' dots ('
									+ r.width_in + '\" x ' + r.height_in + '\")';
							}
						};
						img.onerror = function () {
							if (st) st.innerHTML = 'Tipo ' + r.etiqueta + ' OK, pero la imagen fallo. Pruebe de nuevo.';
						};
						img.src = 'preview_zpl.php?fmt=png&' + base;
					} else if (st) {
						st.innerHTML = 'Tipo ' + r.etiqueta + ' — ZPL ' + (r.zpl_len || '?') + ' bytes';
					}
				};
				xhr.ontimeout = function () {
					if (st) st.innerHTML = 'Tiempo agotado generando vista';
				};
				xhr.onerror = function () {
					if (st) st.innerHTML = 'Error de red al pedir vista previa';
				};
				xhr.send(null);
			}
		</script>

		<?php

		include("conections.php");

		$link = conec_mysql();

		$archivo = '';
		$tipo_img = '';
		$contenido = '';
		$nombre = '';

		$action_clone =  @$action_clone = $_GET['clone_botton'];

		$action =  @$action = $_GET['bttn_actualizar'];

		$instruc =  @$instruc = $_GET['instruc'];

		$ingre =  @$ingre = $_GET['ingre'];
$txt_codigo2 =  @$txt_codigo2 = $_GET['txt_codigo2'];
 
		$txt_precio =  @$txt_precio = $_GET['txt_precio'];
		$dias_exp_post = isset($_GET['exp']) ? (int)$_GET['exp'] : null;
		
		$descrip =  @$descrip = $_GET['descrip'];

		$obs =  @$obs = $_GET['obs'];

		$code =  @$code = $_GET['code'];

		$reg_sanitario =  @$reg_sanitario = $_GET['reg_sanitario'];

		$etiqueta =  @$etiqueta = $_GET['etiqueta'];
		$printer =  @$printer = $_GET['printer'];
		$width =  @$width = $_GET['width'];
		$height =  @$height = $_GET['height'];

		$bar_x =  @$bar_x = $_GET['bar_x'];
		$bar_y =  @$bar_y = $_GET['bar_y'];
		$bar_width =  @$bar_width = $_GET['bar_width'];
		$bar_height =  @$height = $_GET['bar_height'];


		$bar_x2 =  @$bar_x2 = $_GET['bar_x2'];
		$bar_y2 =  @$bar_y2 = $_GET['bar_y2'];
		$bar_width2 =  @$bar_width2 = $_GET['bar_width2'];
		$bar_height2 =  @$bar_height2 = $_GET['bar_height2'];



		$design_x =  @$design_x = $_GET['design_x'];
		$design_y =  @$design_y = $_GET['design_y'];
		$design_width =  @$design_width = $_GET['design_width'];
		$design_height =  @$design_height = $_GET['design_height'];

		$temp_id =  $code;
		$temp_codigo2 = isset($_GET['txt_codigo2']) ? trim((string)$_GET['txt_codigo2']) : '';
		if ($temp_codigo2 === '' && isset($_GET['code2'])) {
			$temp_codigo2 = trim((string)$_GET['code2']);
		}

		$exp_editable = "";



		if ($action <> 'Actualizar' and $action <> 'FM' and $action_clone <> 'Clonar') {

			$q = isset($_POST['clave']) ? trim((string)$_POST['clave']) : '';
			$temp_codigo2 = isset($_POST['txt_codigo2']) ? $_POST['txt_codigo2'] : '';

			// Si no es numérico, buscar por nombre/descripcion y mostrar lista de resultados.
			if ($q !== '' && !preg_match('/^\d+$/', $q)) {
				$qLike = '%' . mysqli_real_escape_string($link, $q) . '%';
				$sqlSearch = "
					SELECT codigo, codigo2, descrip, descrip2, descrip3, precio2, navidad, etiqueta, printer
					FROM items
					WHERE
						navidad = 'FM'
						AND
						(descrip LIKE '$qLike' OR descrip2 LIKE '$qLike' OR descrip3 LIKE '$qLike')
					ORDER BY descrip3 ASC
					LIMIT 80
				";
				$resSearch = mysqli_query($link, $sqlSearch);

				// Mismo estilo/estructura que la búsqueda por letras (index.php)
				echo '<div class="tabcontent" style="display:block;">';
				echo '<table border="0" id="distrib" class="display" style="width:100%">';
				echo '<tr><thead>'
					. '<td width="80"># codigo</td>'
					. '<td width="15">Precio</td>'
					. '<td width="180">Descripción</td>'
					. '<td width="90">Printer</td>'
					. '<td width="75"># Etiqueta</td>'
					. '</thead>';

				$foundAny = false;
				if ($resSearch) {
					while ($r = mysqli_fetch_assoc($resSearch)) {
						$foundAny = true;
						$desc = trim((string)$r['descrip3']);
						if ($desc === '') {
							$desc = trim((string)$r['descrip'] . ' ' . (string)$r['descrip2']);
						}
						$code = (string)$r['codigo'];
						$code2 = (string)$r['codigo2'];
						$precio = htmlspecialchars((string)$r['precio2'], ENT_QUOTES, 'UTF-8');
						$descH = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
						$printer = htmlspecialchars((string)$r['printer'], ENT_QUOTES, 'UTF-8');
						$etiqueta = htmlspecialchars((string)$r['etiqueta'], ENT_QUOTES, 'UTF-8');

						echo '<tr>'
							. '<td align="center"><a href="search_results.php?code=' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '&bttn_actualizar=FM&txt_codigo2=' . htmlspecialchars($code2, ENT_QUOTES, 'UTF-8') . ' ">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</a></td>'
							. '<td>' . $precio . '</td>'
							. '<td>' . $descH . '</td>'
							. '<td>' . $printer . '</td>'
							. '<td>' . $etiqueta . '</td>'
							. '</tr>';
					}
					mysqli_free_result($resSearch);
				}

				if (!$foundAny) {
					echo '<tr><td colspan="5">No se encontraron items.</td></tr>';
				}
				echo '</table></div>';
				exit;
			}

			// Numérico: buscar por código como antes.
			$temp_id = $q;
			$temp_id = sprintf("%06d", (int)$temp_id);
			$exp_editable = "";
		} else {

			if ($action == 'FM') {
				$temp_id =  $code;
			}

			if ($action == 'Actualizar') {

				$descripcion_font = @$descripcion_font = $_GET['descripcion_font'];
				$descripcion_bold = @$descripcion_bold = $_GET['descripcion_bold'];
				$descripcion_x = @$descripcion_x = $_GET['descripcion_x'];
				$descripcion_y = @$descripcion_y = $_GET['descripcion_y'];
				$descripcion_alcance = @$descripcion_alcance = $_GET['descripcion_alcance'];
				$descripcion_renglon = @$descripcion_renglon = $_GET['descripcion_renglon'];
				$descripcion_titulo = @$descripcion_titulo = $_GET['descripcion_titulo'];


				$itemid_font = @$itemid_font = $_GET['itemid_font'];
				$itemid_bold = @$itemid_bold = $_GET['itemid_bold'];
				$itemid_x = @$itemid_x = $_GET['itemid_x'];
				$itemid_y = @$itemid_y = $_GET['itemid_y'];
				$itemid_alcance = @$itemid_alcance = $_GET['itemid_alcance'];
				$itemid_renglon = @$itemid_renglon = $_GET['itemid_renglon'];
				$itemid_titulo = @$itemid_titulo = $_GET['itemid_titulo'];


				$precio_font = @$precio_font = $_GET['precio_font'];
				$precio_bold = @$precio_bold = $_GET['precio_bold'];
				$precio_x = @$precio_x = $_GET['precio_x'];
				$precio_y = @$precio_y = $_GET['precio_y'];
				$precio_alcance = @$precio_alcance = $_GET['precio_alcance'];
				$precio_renglon = @$precio_renglon = $_GET['precio_renglon'];
				$precio_titulo = @$precio_titulo = $_GET['precio_titulo'];

				$fecha_font = @$fecha_font = $_GET['fecha_font'];
				$fecha_bold = @$fecha_bold = $_GET['fecha_bold'];
				$fecha_x = @$fecha_x = $_GET['fecha_x'];
				$fecha_y = @$fecha_y = $_GET['fecha_y'];
				$fecha_alcance = @$fecha_alcance = $_GET['fecha_alcance'];
				$fecha_renglon = @$fecha_renglon = $_GET['fecha_renglon'];
				$fecha_titulo = @$fecha_titulo = $_GET['fecha_titulo'];

				$ingredientes_font =  @$ingredientes_font = $_GET['ingredientes_font'];
				$ingredientes_bold =  @$ingredientes_bold = $_GET['ingredientes_bold'];
				$ingredientes_x =  @$ingredientes_x = $_GET['ingredientes_x'];
				$ingredientes_y =  @$ingredientes_y = $_GET['ingredientes_y'];
				$ingredientes_alcance =  @$ingredientes_alcance = $_GET['ingredientes_alcance'];
				$ingredientes_renglon =  @$ingredientes_renglon = $_GET['ingredientes_renglon'];
				$ingredientes_titulo =  @$ingredientes_titulo = $_GET['ingredientes_titulo'];

				$especif_font =  @$especif_font = $_GET['especif_font'];
				$especif_bold =  @$especif_bold = $_GET['especif_bold'];
				$especif_x =  @$especif_x = $_GET['especif_x'];
				$especif_y =  @$specif_y = $_GET['especif_y'];
				$especif_alcance =  @$especif_alcance = $_GET['especif_alcance'];
				$especif_renglon =  @$especif_renglon = $_GET['especif_renglon'];
				$especif_titulo =  @$especif_titulo = $_GET['especif_titulo'];

				$reg_sanitario_font =  @$reg_sanitario_font = $_GET['reg_sanitario_font'];
				$reg_sanitario_bold =  @$reg_sanitario_bold = $_GET['reg_sanitario_bold'];
				$reg_sanitario_x =  @$reg_sanitario_x = $_GET['reg_sanitario_x'];
				$reg_sanitario_y =  @$reg_sanitario_y = $_GET['reg_sanitario_y'];
				$reg_sanitario_alcance =  @$reg_sanitario_alcance = $_GET['reg_sanitario_alcance'];
				$reg_sanitario_renglon =  @$reg_sanitario_renglon = $_GET['reg_sanitario_renglon'];
				$reg_sanitario_titulo =  @$reg_sanitario_titulo = $_GET['reg_sanitario_titulo'];

				$obs_font =  @$obs_font = $_GET['obs_font'];
				$obs_bold =  @$obs_bold = $_GET['obs_bold'];
				$obs_x =  @$obs_x = $_GET['obs_x'];
				$obs_y =  @$obs_y = $_GET['obs_y'];
				$obs_alcance =  @$obs_alcance = $_GET['obs_alcance'];
				$obs_renglon =  @$obs_renglon = $_GET['obs_renglon'];
				$obs_titulo =  @$obs_titulo = $_GET['obs_titulo'];



				$reg_sanitario =  @$reg_sanitario = $_GET['reg_sanitario'];
				$etiqueta =  @$etiqueta = $_GET['etiqueta'];
				$printer =  @$printer = $_GET['printer'];
				$orientacion =  @$orientacion = $_GET['orientacion'];

				$width =  @$width = $_GET['width'];
				$height =  @$height = $_GET['height'];





				$contenido = '';
				$archivo = '';
				$tipo_img = '';
				$nombre = '';
				$tamanio = 0;
				$hay_imagen = false;
				if (isset($_FILES['imagen']) && is_array($_FILES['imagen']) && isset($_FILES['imagen']['error']) && (int)$_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
					$archivo = isset($_FILES['imagen']['tmp_name']) ? (string)$_FILES['imagen']['tmp_name'] : '';
					$tamanio = isset($_FILES['imagen']['size']) ? (int)$_FILES['imagen']['size'] : 0;
					$tipo_img = isset($_FILES['imagen']['type']) ? (string)$_FILES['imagen']['type'] : '';
					$nombre = isset($_FILES['imagen']['name']) ? (string)$_FILES['imagen']['name'] : '';
					if ($archivo !== '' && is_uploaded_file($archivo) && $tamanio > 0) {
						$fp = fopen($archivo, 'rb');
						if ($fp) {
							$contenido = addslashes(fread($fp, $tamanio));
							fclose($fp);
							$hay_imagen = true;
						}
					}
				}

				$img_set = $hay_imagen ? "`img_prd`='" . $contenido . "', " : '';

				$query_update = "
				UPDATE 
				`mypastelito`.`items` 
				SET 
				`especif`='" . $instruc . "',
				`ingredientes`='" . $ingre . "',
				`descrip3`='" . $descrip . "',
				`etiqueta`='" . $etiqueta . "',
				`reg_sanitario`='" . $reg_sanitario . "',
				`obs`='" . $obs . "' ,
				" . $img_set . "
				`precio2`=  ". $txt_precio." ,
				`codigo2`=  '". $txt_codigo2."',
				`exp`=  ".(int)$dias_exp_post."
				WHERE
				`codigo`='" . $code . "'";

				$result_item = mysqli_query($link, $query_update);


				$query_update_lbl = "
			UPDATE 
			`mypastelito`.`lbls` 
			SET 
			`width`= " . $width . ",
			`height`=" . $height . ",
			`printer`= '" . $printer . "',
			`orientacion`= '" . $orientacion . "',
			`bar_x`= " . $bar_x . ",
			`bar_y`= " . $bar_y . ",
			`bar_width`= " . $bar_width . ",	
			`bar_height`= " . $bar_height . ",
			`FM_bar_x`= " . $bar_x2 . ",
			`FM_bar_y`= " . $bar_y2 . ",
			`FM_bar_width`= " . $bar_width2 . ",	
			`FM_bar_height`= " . $bar_height2 . ",
			`design_x`= " . $design_x . ",
			`design_y`= " . $design_y . ",
			`design_width`= " . $design_width . ",	
			`design_height`= " . $design_height . " 	 	 						 
			WHERE
			`id`='" . $code . "'";

				$result_item_update_lbl = mysqli_query($link, $query_update_lbl);





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
			WHERE `lblid` = '" . $code . "'
			AND
			descrip = 'descripcion'";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);


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
			
			WHERE `lblid`='" . $code . "'
			AND
			descrip = 'itemid'
			
			";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);

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
			
			WHERE `lblid`='" . $code . "'
			AND
			descrip = 'precio'
			
			";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);



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
		  
		  WHERE `lblid`='" . $code . "'
		  AND
		  descrip = 'fecha'";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);


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
		  
		  WHERE `lblid`='" . $code . "'
		  AND
		  descrip = 'ingredientes'";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);



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
		  
		  WHERE `lblid`='" . $code . "'
		  AND
		  descrip = 'especif'";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);


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
		  WHERE `lblid`='" . $code . "'
		  AND
		  descrip = 'reg_sanitario'";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);

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
		  WHERE `lblid`='" . $code . "'
		  AND
		  descrip = 'obs'";

				$result_lbl_config_update = mysqli_query($link, $query_lbl_config_update);
			}




			if ($action_clone == 'Clonar') {


				$cloneto = @$code_clone = $_GET['code_clone'];


				$descripcion_font = @$descripcion_font = $_GET['descripcion_font'];
				$descripcion_bold = @$descripcion_bold = $_GET['descripcion_bold'];
				$descripcion_x = @$descripcion_x = $_GET['descripcion_x'];
				$descripcion_y = @$descripcion_y = $_GET['descripcion_y'];
				$descripcion_alcance = @$descripcion_alcance = $_GET['descripcion_alcance'];
				$descripcion_renglon = @$descripcion_renglon = $_GET['descripcion_renglon'];
				$descripcion_titulo = @$descripcion_titulo = $_GET['descripcion_titulo'];


				$itemid_font = @$itemid_font = $_GET['itemid_font'];
				$itemid_bold = @$itemid_bold = $_GET['itemid_bold'];
				$itemid_x = @$itemid_x = $_GET['itemid_x'];
				$itemid_y = @$itemid_y = $_GET['itemid_y'];
				$itemid_alcance = @$itemid_alcance = $_GET['itemid_alcance'];
				$itemid_renglon = @$itemid_renglon = $_GET['itemid_renglon'];
				$itemid_titulo = @$itemid_titulo = $_GET['itemid_titulo'];


				$precio_font = @$precio_font = $_GET['precio_font'];
				$precio_bold = @$precio_bold = $_GET['precio_bold'];
				$precio_x = @$precio_x = $_GET['precio_x'];
				$precio_y = @$precio_y = $_GET['precio_y'];
				$precio_alcance = @$precio_alcance = $_GET['precio_alcance'];
				$precio_renglon = @$precio_renglon = $_GET['precio_renglon'];
				$precio_titulo = @$precio_titulo = $_GET['precio_titulo'];

				$fecha_font = @$fecha_font = $_GET['fecha_font'];
				$fecha_bold = @$fecha_bold = $_GET['fecha_bold'];
				$fecha_x = @$fecha_x = $_GET['fecha_x'];
				$fecha_y = @$fecha_y = $_GET['fecha_y'];
				$fecha_alcance = @$fecha_alcance = $_GET['fecha_alcance'];
				$fecha_renglon = @$fecha_renglon = $_GET['fecha_renglon'];
				$fecha_titulo = @$fecha_titulo = $_GET['fecha_titulo'];

				$ingredientes_font =  @$ingredientes_font = $_GET['ingredientes_font'];
				$ingredientes_bold =  @$ingredientes_bold = $_GET['ingredientes_bold'];
				$ingredientes_x =  @$ingredientes_x = $_GET['ingredientes_x'];
				$ingredientes_y =  @$ingredientes_y = $_GET['ingredientes_y'];
				$ingredientes_alcance =  @$ingredientes_alcance = $_GET['ingredientes_alcance'];
				$ingredientes_renglon =  @$ingredientes_renglon = $_GET['ingredientes_renglon'];
				$ingredientes_titulo =  @$ingredientes_titulo = $_GET['ingredientes_titulo'];

				$especif_font =  @$especif_font = $_GET['especif_font'];
				$especif_bold =  @$especif_bold = $_GET['especif_bold'];
				$especif_x =  @$especif_x = $_GET['especif_x'];
				$especif_y =  @$specif_y = $_GET['especif_y'];
				$especif_alcance =  @$especif_alcance = $_GET['especif_alcance'];
				$especif_renglon =  @$especif_renglon = $_GET['especif_renglon'];
				$especif_titulo =  @$especif_titulo = $_GET['especif_titulo'];

				$reg_sanitario_font =  @$reg_sanitario_font = $_GET['reg_sanitario_font'];
				$reg_sanitario_bold =  @$reg_sanitario_bold = $_GET['reg_sanitario_bold'];
				$reg_sanitario_x =  @$reg_sanitario_x = $_GET['reg_sanitario_x'];
				$reg_sanitario_y =  @$reg_sanitario_y = $_GET['reg_sanitario_y'];
				$reg_sanitario_alcance =  @$reg_sanitario_alcance = $_GET['reg_sanitario_alcance'];
				$reg_sanitario_renglon =  @$reg_sanitario_renglon = $_GET['reg_sanitario_renglon'];
				$reg_sanitario_titulo =  @$reg_sanitario_titulo = $_GET['reg_sanitario_titulo'];

				$obs_font =  @$obs_font = $_GET['obs_font'];
				$obs_bold =  @$obs_bold = $_GET['obs_bold'];
				$obs_x =  @$obs_x = $_GET['obs_x'];
				$obs_y =  @$obs_y = $_GET['obs_y'];
				$obs_alcance =  @$obs_alcance = $_GET['obs_alcance'];
				$obs_renglon =  @$obs_renglon = $_GET['obs_renglon'];
				$obs_titulo =  @$obs_titulo = $_GET['obs_titulo'];

				$reg_sanitario =  @$reg_sanitario = $_GET['reg_sanitario'];
				$etiqueta =  @$etiqueta = $_GET['etiqueta'];
				$printer =  @$printer = $_GET['printer'];
				$width =  @$width = $_GET['width'];
				$height =  @$height = $_GET['height'];




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
   '" . $cloneto . "',
   'lbl7 2.5x1.5', 
   '" . $width . "',
   '" . $height . "', 
   '" . $printer . "',
   '" . $bar_x . "',
   '" . $bar_y . "',
   '" . $bar_width . "',
   '" . $bar_height . "',
   '" . $bar_x2 . "',
   '" . $bar_y2 . "',
   '" . $bar_width2 . "',
   '" . $bar_height2 . "')";

				//echo   $query_insert_lbl;
				$result_item__insert_lbl = mysqli_query($link, $query_insert_lbl);



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
	 ('" . $cloneto . "',
	 'descripcion',
	 '$descripcion_font',
	 '$descripcion_bold',
	 '$descripcion_x',
	 '$descripcion_y',
	 '$descripcion_alcance',
	 '$descripcion_renglon',
	 '$descripcion_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_descripcion);


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
	 ('" . $cloneto . "',
	 'itemid',
	 '$itemid_font',
	 '$itemid_bold',
	 '$itemid_x',
	 '$itemid_y',
	 '$itemid_alcance',
	 '$itemid_renglon',
	 '$itemid_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_itemid);


				$query_lbl_config_insert_precio = "
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
	 ('" . $cloneto . "',
	 'precio',
	 '$precio_font',
	 '$precio_bold',
	 '$precio_x',
	 '$precio_y',
	 '$precio_alcance',
	 '$precio_renglon',
	 '$precio_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_precio);


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
	 ('" . $cloneto . "',
	 'fecha',
	 '$fecha_font',
	 '$fecha_bold',
	 '$fecha_x',
	 '$fecha_y',
	 '$fecha_alcance',
	 '$fecha_renglon',
	 '$fecha_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_fecha);



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
	 ('" . $cloneto . "',
	 'ingredientes',
	 '$ingredientes_font',
	 '$ingredientes_bold',
	 '$ingredientes_x',
	 '$ingredientes_y',
	 '$ingredientes_alcance',
	 '$ingredientes_renglon',
	 '$ingredientes_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_ingredientes);


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
	 ('" . $cloneto . "',
	 'especif',
	 '$especif_font',
	 '$especif_bold',
	 '$especif_x',
	 '$especif_y',
	 '$especif_alcance',
	 '$especif_renglon',
	 '$especif_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_especif);


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
	 ('" . $cloneto . "',
	 'reg_sanitario',
	 '$reg_sanitario_font',
	 '$reg_sanitario_bold',
	 '$reg_sanitario_x',
	 '$reg_sanitario_y',
	 '$reg_sanitario_alcance',
	 '$reg_sanitario_renglon',
	 '$reg_sanitario_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_reg_sanitario);


				$query_lbl_config_insert_obs = "
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
	 ('" . $cloneto . "',
	 'obs',
	 '$obs_font',
	 '$obs_bold',
	 '$obs_x',
	 '$obs_y',
	 '$obs_alcance',
	 '$obs_renglon',
	 '$obs_titulo')";

				$result_lbl_config_insert = mysqli_query($link, $query_lbl_config_insert_obs);
			}
		}


$query_id_items = "
SELECT 
*
FROM
items
WHERE 
codigo = '" . mysqli_real_escape_string($link, (string)$temp_id) . "'";

		if ($temp_codigo2 !== '') {
			$query_id_items .= "
OR 
codigo2 = '" . mysqli_real_escape_string($link, (string)$temp_codigo2) . "'";
		}

		$result_item = mysqli_query($link, $query_id_items);
		$row = mysqli_fetch_array($result_item);


		$query_lbl_items = "
SELECT 
*
FROM
lbls
WHERE 
id = '" . $temp_id . "'";


		$result_lbl_items = mysqli_query($link, $query_lbl_items);

		$row_lbl_items = mysqli_fetch_array($result_lbl_items);

		$dias_exp = isset($row['exp']) && $row['exp'] !== '' && $row['exp'] !== null ? (int)$row['exp'] : 0;
		$elab_today = date('Y-m-d');
		$exp_editable = "";


		?>
		<html>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

			<body bgcolor="#FFFFFF" onload="check_elab();">
				<SCRIPT language=JavaScript>
					<!-- 
					function win() {
						window.opener.location.href = "../facturacion_index.php";
						self.close();
						//
					-->
					}
				</SCRIPT>
				<form name="form1" method="post" action="exportarimg.php" target="_self">
					<table width="445" height="136" border="1" cellspacing="1">
						<tr>
							<td height="27" colspan="2" bgcolor="#FFFFFF">  <h3><?php echo ($row['descrip'] .' '.$row['descrip2']); ?></td>
						</tr>
						<tr>
							<td height="26" colspan="2" bgcolor="#FFFFFF"><strong> Codigo: </strong><?php echo $row['codigo'] ?>
									<input type="text" hidden="hidden" name="txt_descrip" id="textfield3" value="<?php echo ($row['descrip']); ?>" />
									<input type="text" hidden="hidden" name="txt_codigo" id="txt_codigo" value="<?php echo $row['codigo'] ?>" />
								</td>
						</tr>
						<tr>
							<td height="24" colspan="2" bgcolor="#FFFFFF"><strong>
									<input type="text" hidden="hidden" name="txt_descrip2" id="textfield" value="<?php echo  $row['descrip2'] ?> " />
							
							    <strong>Precio: B/. <?php echo $row['precio2'] ?></strong></h3> </td>

 <input type="text"  hidden="hidden" name="txt_precio" id="textfield4" value="<?php  echo $row['precio1'] ?>" />

					 
					 
							   
						</tr>
					</table>
					<table width="574" border="0">
						<tr>
						  <td align="left">	<div class="txt-heading"><a id="btnEmpty" href="index.php">[Nueva Busqueda]</a></div></td>
						  <td></td>
						  <td>&nbsp;</td>
					  </tr>
						<tr>
							<td align="left">&nbsp; </td>
							<td></td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td width="191" align="left"><strong> Cantidad:
								</strong></td>
							<td width="348"><strong>
									<input type="number" min="0" align="left" name="cant" id="caja_busqueda" value="" autocomplete="off" required />
								</strong></td>
							<td width="21">&nbsp;</td>
						</tr>
						<tr>
							<td height="29" align="left"><strong>Fecha Elaboración:</strong></td>
							<td><input type="date" name="elab_day" id="elab_day" value="<?php echo htmlspecialchars($elab_today, ENT_QUOTES, 'UTF-8'); ?>" required onChange="calcular()" /></td>
							<td></td>
						</tr>
						<tr>
							<td height="28"><strong> Dias de vencimiento: </strong></td>
							<td colspan="2"><strong>
									<input type="number" min="0" align="left" name="caducidad1" id="caducidad1" value="" autocomplete="off"  required onKeyUp="calcular()" onChange="calcular()" />
									<em>Caduca:&nbsp;<span id="resultado"></span></em></strong></td>
						</tr>
						<tr>
							<td height="28">&nbsp;</td>
							<td colspan="2"><strong>
									<input hidden type="number" min="0" align="left" name="caducidad" id="caja_busqueda1" value="" autocomplete="off" />
							    <input name="Submit" type=submit onClick="if(!document.getElementById('caja_busqueda1').value || document.getElementById('caja_busqueda1').value === '') document.getElementById('caja_busqueda1').value = document.getElementById('caducidad1').value;" value="Imprimir" />
							</strong></td>
						</tr>
						<tr>
							<td height="28" valign="top">&nbsp;</td>
							<td colspan="2" style="color:#666; font-size:13px; font-style:italic;">
								<?php if ($dias_exp > 0) { ?>
									cant. sugerida: <?php echo (int)$dias_exp; ?> días
								<?php } ?>
							</td>
						</tr>
				  </table>
</form>
                         
<form     method="post" action="export_code13.php" target="_self">
									<div>
										<p>&nbsp;</p>
										<table bgcolor="#999999" width="929" height="44" border="2">
										  <tr>
										    <td width="160">GTIN (chica SoftShop):</td>
										    <td width="167"><input type="text" name="txt_codigo2" id="txt_codigo2_gtin" value="<?php echo htmlspecialchars($row['codigo2']); ?>" /></td>
										    <td width="60">Cantidad:</td>
										    <td width="80"><input type="number" min="1" name="cant2" id="cant2" value="1" size="4" required /></td>
										    <td width="291"><input type="Submit" name="gtin13" id="gtin13" value="Imprimir GTIN" title="GTIN chica — distinto del formato #13 por IP" /></td>
									      </tr>
									  </table>
										<p style="margin:4px 0 0 0;font-size:11px;">Imprimir GTIN = etiqueta chica SoftShop (cola <code>gtin</code> → GK420t_chica). El formato <strong>#13</strong> del producto es otra etiqueta (ZPL rotado por IP).</p>
										<p>&nbsp;</p>
  </div>
				</form>
              		
                    
                    <table width="1000" border="0">  
				<form>
			 
              		
                    
                    <table width="1000" border="0">  
				<form>
			
<tr>			<td align="left"><strong>Cod. Proveedor/GTIN13:</strong></td><td>
                        <strong><input 	type="text"  name="txt_codigo2" id="txt_codigo2" value="<?php echo $row['codigo2'] ?>" />
                        
               
</strong></td></tr>
                        <tr>			<td align="left"><strong>Precio:</strong></td><td><input type="text"  name="txt_precio" id="txt_precio" value="<?php echo $row['precio2'] ?>" /></td></tr>
                        <tr>			<td align="left"><strong>Días de vencimiento (exp):</strong></td><td><input type="number" min="0" name="exp" id="exp" value="<?php echo (int)$row['exp'] ?>" /></td></tr>
                    
						<tr> 
							<td align="left"><strong>Descripción:</strong></td>
							<td colspan="8"><strong>
									<input type="text" name="descrip" id="descrip" min="0" autocomplete="off" value="<?php echo ($row['descrip3']); ?>" size="100" align="left" />
								</strong></td>
						</tr>
						<tr>
							<td width="146" align="left"><strong> Ingredientes:</strong></td>
							<td colspan="8"><strong>
									<input type="text" name="ingre" id="ingre" min="0" autocomplete="off" value="<?php echo ($row['ingredientes']); ?>" size="100" align="left" />
								</strong></td>
						</tr>
						<tr>
							<td height="26" align="left"><strong> Instrucciones: </strong></td>
							<td colspan="8"><strong>
									<input type="text" name="instruc" id="instruc" min="0" autocomplete="off" value="<?php echo ($row['especif']); ?>" size="100" align="left" />
								</strong></td>
						</tr>
						<tr>
							<td height="41"><strong>alérgenos:</strong></td>
							<td colspan="8"><strong>
									<input type="text" name="obs" id="obs" min="0" autocomplete="off" value="<?php echo ($row['obs']); ?>" size="100" align="left" />
								</strong></td>
						</tr>
						<tr>
							<td height="40"><strong>Registro Sanitario:</strong></td>
							<td width="212"><strong>
									<input type="text" name="reg_sanitario" id="reg_sanitario" min="0" autocomplete="off" value="<?php echo ($row['reg_sanitario']); ?>" size="10" align="left" />
								</strong></td>
							<td colspan="2" bgcolor="#CCCCCC">Codigo de Barra:</td>
							<td colspan="2" bgcolor="#CCCCCC">FM Logo:</td>
							<td colspan="2" bgcolor="#CCCCCC">Diseño</td>
							<td width="324">&nbsp;</td>
						</tr>
						<tr>
							<td height="27">Tipo Etiqueta:</td>
							<td><strong>
									<input type="type" name="etiqueta" id="etiqueta" min="0" autocomplete="off" value="<?php echo ($row['etiqueta']); ?>" size="5" align="left" />
								</strong></td>
							<td width="48" bgcolor="#CCCCCC">X:</td>
							<td width="35" bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_x" id="bar_x" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['bar_x']); ?>" size="5" align="left" />
								</strong></td>
							<td width="47" bgcolor="#CCCCCC">X: </td>
							<td width="31" bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_x2" id="bar_x2" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['FM_bar_x']); ?>" size="5" align="left" />
								</strong></td>
							<td width="59" bgcolor="#CCCCCC">X: </td>
							<td width="60" bgcolor="#CCCCCC"><strong>
									<input type="text" name="design_x" id="design_x" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['design_x']); ?>" size="5" align="left" />
								</strong></td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td height="27">Impresora:</td>
							<td><strong>
									<input type="text" name="printer" id="printer" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['printer']); ?>" size="15" align="left" />
								</strong></td>

							<td bgcolor="#CCCCCC">Y:</td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_y" id="bar_y" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['bar_y']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Y: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_y2" id="bar_y2" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['FM_bar_y']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Y: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="design_y" id="design_y" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['design_y']); ?>" size="5" align="left" />
								</strong></td>
							<td><strong>
									<label for="checkbox"></label>
								</strong></td>
						</tr>
						<tr>
							<td height="30">Ancho:</td>
							<td><strong>
									<input type="text" name="width" id="width" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['width']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Ancho:</td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_width" id="bar_width" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['bar_width']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Ancho: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_width2" id="bar_width2" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['FM_bar_width']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Ancho: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="design_width" id="design_width" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['design_width']); ?>" size="5" align="left" />
								</strong></td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td height="25">Alto:</td>
							<td><strong>
									<input type="text" name="height" id="height" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['height']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Alto: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_height" id="bar_height" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['bar_height']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Alto: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="bar_height2" id="bar_height2" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['FM_bar_height']); ?>" size="5" align="left" />
								</strong></td>
							<td bgcolor="#CCCCCC">Alto: </td>
							<td bgcolor="#CCCCCC"><strong>
									<input type="text" name="design_height" id="design_height" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['design_height']); ?>" size="5" align="left" />
								</strong></td>
							<td>&nbsp;</td>

						</tr>
						<tr>
							<td height="27">Orientación:</td>
							<td><strong>
									<input type="text" name="orientacion" id="orientacion" min="0" autocomplete="off" value="<?php echo ($row_lbl_items['orientacion']); ?>" size="5" align="left" />
								</strong></td>
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
									<input type="text" name="code_clone" id="code_clone" min="0" autocomplete="off" value=" " size="30" align="left" />
									<input name="clone_botton" type="submit" value="Clonar" id="clone_botton" onClick="search_results.php?code=document.getElementById('txt_codigo').value&amp;especif=document.getElementById('instruc').value&amp;instruct=document.getElementById('ingre').value" disabled="disabled" />
									<input type="checkbox" name="checkbox2" id="checkbox2" onChange="document.getElementById('clone_botton').disabled = !this.checked;" />
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
								<td align="center">Font</td>
								<td align="center">Bold</td>
								<td align="center">X</td>
								<td align="center">Y</td>
								<td align="center">Alcance</td>
								<td align="center">Renglon</td>
								<td align="center">Titulo</td>

								<?php


								$query_lbl_config = "
SELECT 
*
FROM
lbl_lines
WHERE 
lblid = '" . $temp_id . "'";


								$result_lbl_config = mysqli_query($link, $query_lbl_config);


								while ($row_lbl_config = mysqli_fetch_array($result_lbl_config)) {

									print '  <tr><td ">' . $row_lbl_config['descrip'] . '</td>
     
	 
	  <td>
	  <input  type="text" name="' . $row_lbl_config['descrip'] . '_font" id="' . $row_lbl_config['descrip'] . '_font" min="0" autocomplete="off" value="' . $row_lbl_config['font'] . '" size="5" align="left"/></td>
	  <td   align="right"><input  type="text" name="' . $row_lbl_config['descrip'] . '_bold" id="' . $row_lbl_config['descrip'] . '_bold" min="0" autocomplete="off" value="' . $row_lbl_config['bold'] . '" size="5" align="left"   /></td>
      <td><input  type="text" name="' . $row_lbl_config['descrip'] . '_x" id="' . $row_lbl_config['descrip'] . '_x" min="0" autocomplete="off" value="' . $row_lbl_config['x'] . '" size="5" align="left"   /></td>
	   <td><input  type="text" name="' . $row_lbl_config['descrip'] . '_y" id="' . $row_lbl_config['descrip'] . '_y" min="0" autocomplete="off" value="' . $row_lbl_config['y'] . '" size="5" align="left"   /></td>
	     <td><input  type="text" name="' . $row_lbl_config['descrip'] . '_alcance" id="' . $row_lbl_config['descrip'] . '_alcance" min="0" autocomplete="off" value="' . $row_lbl_config['alcance'] . '" size="5" align="left"   /></td>
		   <td><input  type="text" name="' . $row_lbl_config['descrip'] . '_renglon" id="' . $row_lbl_config['descrip'] . '_renglon" min="0" autocomplete="off" value="' . $row_lbl_config['renglon'] . '" size="5" align="left"   /></td>
		      <td><input  type="text" name="' . $row_lbl_config['descrip'] . '_titulo" id="' . $row_lbl_config['descrip'] . '_titulo" min="0" autocomplete="off" value="' . $row_lbl_config['titulo'] . '" size="20" align="left"   /></td>
	 
	 
		   ';
								}


								?>


							</table>
						</tr>
						<tr>
							<td colspan="9">
								<table width="100%" border="1" cellspacing="1" cellpadding="4">
									<tr>
										<td colspan="2" bgcolor="#CCCCCC"><strong>Vista previa / editor (JSON local)</strong>
											— no modifica MySQL; Guardar escribe en label_layouts/
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<input type="button" value="Ver etiqueta" onclick="preview_zpl_label();" />
											<input type="button" value="Guardar layout" onclick="layout_save_json();" />
											<span id="zpl_preview_status" style="color:#666;font-size:12px;margin-left:8px;"></span>
										</td>
									</tr>
									<tr id="layout_editor_row">
										<td width="55%" valign="top" style="background:#f7f7f7;">
											<img id="zpl_preview_img" alt="Vista ZPL" border="1" style="max-width:100%;height:auto;display:none;" />
											<div id="zpl_preview_placeholder" style="color:#999;font-size:12px;padding:12px;">Pulse &quot;Ver etiqueta&quot; para ver la vista.</div>
										</td>
										<td width="45%" valign="top" id="layout_side_panel">
											<div style="font-weight:bold;margin-bottom:6px;">Datos de campo</div>
											<div style="margin-bottom:6px;">
												Campo:
												<select id="layout_field_sel" onchange="layout_on_field_change();" style="max-width:200px;"></select>
											</div>
											<div id="layout_coords_box" style="display:none;">
												<table border="0" cellpadding="2" cellspacing="0">
													<tr>
														<td>X</td><td><input type="text" id="layout_x" size="7" /></td>
														<td>Y</td><td><input type="text" id="layout_y" size="7" /></td>
													</tr>
													<tr>
														<td>Font</td><td><input type="text" id="layout_font" size="5" /></td>
														<td>Negrita</td>
														<td>
															<select id="layout_bold" title="true = negrita, false = normal">
																<option value="false">false (normal)</option>
																<option value="true">true (negrita)</option>
															</select>
														</td>
													</tr>
													<tr>
														<td>Alcance</td><td><input type="text" id="layout_alcance" size="5" /></td>
														<td>Renglon</td><td><input type="text" id="layout_renglon" size="5" /></td>
													</tr>
													<tr>
														<td>Titulo</td>
														<td colspan="3"><input type="text" id="layout_titulo" size="22" /></td>
													</tr>
													<tr>
														<td>Paso</td><td><input type="text" id="layout_step" size="5" value="20" /></td>
														<td colspan="2">
															<input type="button" value="&larr;" onclick="layout_nudge(-1,0);" />
															<input type="button" value="&rarr;" onclick="layout_nudge(1,0);" />
															<input type="button" value="&uarr;" onclick="layout_nudge(0,-1);" />
															<input type="button" value="&darr;" onclick="layout_nudge(0,1);" />
															<input type="button" value="Aplicar" onclick="layout_apply_inputs();" />
														</td>
													</tr>
												</table>
											</div>
											<div style="margin-top:8px;font-size:11px;color:#666;">Solo textos (sin reg_sanitario). JSON: label_layouts/</div>
										</td>
									</tr>
									<tr>
										<td colspan="2" bgcolor="#E8E8E8"><strong>Codigo de barras</strong>
											— bar_x / bar_y / bar_width / bar_height (aparte de campos y de la imagen)
										</td>
									</tr>
									<tr id="layout_barcode_row">
										<td colspan="2">
											X:<input type="text" id="layout_bar_x" size="7" />
											Y:<input type="text" id="layout_bar_y" size="7" />
											Ancho:<input type="text" id="layout_bar_w" size="7" />
											Alto:<input type="text" id="layout_bar_h" size="7" />
											Paso:<input type="text" id="layout_bar_step" size="4" value="20" />
											<input type="button" value="&larr;" onclick="layout_bar_nudge(-1,0);" />
											<input type="button" value="&rarr;" onclick="layout_bar_nudge(1,0);" />
											<input type="button" value="&uarr;" onclick="layout_bar_nudge(0,-1);" />
											<input type="button" value="&darr;" onclick="layout_bar_nudge(0,1);" />
											<input type="button" value="Aplicar barra" onclick="layout_bar_apply();" />
										</td>
									</tr>
									<tr>
										<td colspan="2" bgcolor="#E8E8E8"><strong>Diseño (imagen)</strong>
											— design_x / design_y / design_width / design_height (posicion de la BMP, distinto al codigo de barras)
										</td>
									</tr>
									<tr id="layout_design_row">
										<td colspan="2">
											X:<input type="text" id="layout_des_x" size="7" />
											Y:<input type="text" id="layout_des_y" size="7" />
											Ancho:<input type="text" id="layout_des_w" size="7" />
											Alto:<input type="text" id="layout_des_h" size="7" />
											Paso:<input type="text" id="layout_des_step" size="4" value="20" />
											<input type="button" value="&larr;" onclick="layout_des_nudge(-1,0);" />
											<input type="button" value="&rarr;" onclick="layout_des_nudge(1,0);" />
											<input type="button" value="&uarr;" onclick="layout_des_nudge(0,-1);" />
											<input type="button" value="&darr;" onclick="layout_des_nudge(0,1);" />
											<input type="button" value="Aplicar diseño" onclick="layout_des_apply();" />
										</td>
									</tr>
								</table>
								<script type="text/javascript">
								var LAYOUT_STATE = { layout: null, dirty: false };
								var LAYOUT_SKIP_FIELDS = { reg_sanitario: 1 };

								function layout_codigo() {
									var el = document.getElementById('code') || document.getElementById('txt_codigo');
									return el ? el.value : '';
								}
								function layout_cad_elab() {
									var cadEl = document.getElementById('caducidad1');
									var elabEl = document.getElementById('elab_day');
									return {
										cad: cadEl && cadEl.value !== '' ? cadEl.value : '0',
										elab: elabEl ? elabEl.value : ''
									};
								}
								function layout_set_status(msg) {
									var st = document.getElementById('zpl_preview_status');
									if (st) st.innerHTML = msg || '';
								}
								function layout_num(id, defv) {
									var el = document.getElementById(id);
									if (!el) return defv;
									var n = parseFloat(el.value);
									return isNaN(n) ? defv : n;
								}
								function layout_set_form(id, val) {
									var el = document.getElementById(id);
									if (el && val != null && typeof val !== 'undefined') el.value = val;
								}
								function layout_load_json(cb) {
									var code = layout_codigo();
									if (!code) { layout_set_status('Falta codigo'); return; }
									layout_set_status('Cargando layout JSON...');
									var xhr = new XMLHttpRequest();
									xhr.open('GET', 'api_label_layout.php?action=get&codigo=' + encodeURIComponent(code) + '&_=' + (new Date().getTime()), true);
									xhr.onreadystatechange = function () {
										if (xhr.readyState !== 4) return;
										var r = null;
										try { r = JSON.parse(xhr.responseText); } catch (e) { r = null; }
										if (!r || !r.ok) {
											layout_set_status((r && r.error) ? r.error : 'No se cargo layout');
											return;
										}
										LAYOUT_STATE.layout = r.layout;
										LAYOUT_STATE.dirty = false;
										layout_fill_barcode_inputs();
										layout_sync_form_from_layout();
										layout_fill_elements();
										layout_set_status('Layout JSON listo' + (r.layout.seeded ? ' (sembrado desde MySQL)' : ''));
										if (typeof cb === 'function') cb(r.layout);
									};
									xhr.send(null);
								}
								function layout_sync_form_from_layout() {
									if (!LAYOUT_STATE.layout || !LAYOUT_STATE.layout.lbls) return;
									var L = LAYOUT_STATE.layout.lbls;
									layout_set_form('bar_x', L.bar_x);
									layout_set_form('bar_y', L.bar_y);
									layout_set_form('bar_width', L.bar_width);
									layout_set_form('bar_height', L.bar_height);
									layout_set_form('design_x', L.design_x);
									layout_set_form('design_y', L.design_y);
									layout_set_form('design_width', L.design_width);
									layout_set_form('design_height', L.design_height);
								}
								function layout_fill_barcode_inputs() {
									if (!LAYOUT_STATE.layout) return;
									if (LAYOUT_STATE.layout.shared) {
										var fb = (LAYOUT_STATE.layout.fields || {}).barcode || {};
										document.getElementById('layout_bar_x').value = fb.x != null ? fb.x : '';
										document.getElementById('layout_bar_y').value = fb.y != null ? fb.y : '';
										document.getElementById('layout_bar_w').value = fb.w != null ? fb.w : '';
										document.getElementById('layout_bar_h').value = fb.h != null ? fb.h : '';
										var desRow = document.getElementById('layout_design_row');
										if (desRow) desRow.style.display = 'none';
										return;
									}
									var L = LAYOUT_STATE.layout.lbls || {};
									document.getElementById('layout_bar_x').value = L.bar_x != null ? L.bar_x : '';
									document.getElementById('layout_bar_y').value = L.bar_y != null ? L.bar_y : '';
									document.getElementById('layout_bar_w').value = L.bar_width != null ? L.bar_width : '';
									document.getElementById('layout_bar_h').value = L.bar_height != null ? L.bar_height : '';
									layout_fill_design_inputs();
								}
								function layout_fill_design_inputs() {
									if (!LAYOUT_STATE.layout || LAYOUT_STATE.layout.shared) return;
									var L = LAYOUT_STATE.layout.lbls || {};
									var dx = document.getElementById('layout_des_x');
									if (!dx) return;
									dx.value = L.design_x != null ? L.design_x : '';
									document.getElementById('layout_des_y').value = L.design_y != null ? L.design_y : '';
									document.getElementById('layout_des_w').value = L.design_width != null ? L.design_width : '';
									document.getElementById('layout_des_h').value = L.design_height != null ? L.design_height : '';
								}
								function layout_read_barcode_to_state() {
									if (!LAYOUT_STATE.layout) return;
									var x = parseInt(document.getElementById('layout_bar_x').value, 10);
									var y = parseInt(document.getElementById('layout_bar_y').value, 10);
									var w = parseInt(document.getElementById('layout_bar_w').value, 10);
									var h = parseInt(document.getElementById('layout_bar_h').value, 10);
									if (isNaN(x)) x = 0; if (isNaN(y)) y = 0;
									if (isNaN(w)) w = 0; if (isNaN(h)) h = 0;
									if (LAYOUT_STATE.layout.shared) {
										if (!LAYOUT_STATE.layout.fields) LAYOUT_STATE.layout.fields = {};
										if (!LAYOUT_STATE.layout.fields.barcode) LAYOUT_STATE.layout.fields.barcode = {};
										LAYOUT_STATE.layout.fields.barcode.x = x;
										LAYOUT_STATE.layout.fields.barcode.y = y;
										LAYOUT_STATE.layout.fields.barcode.w = w;
										LAYOUT_STATE.layout.fields.barcode.h = h;
									} else {
										if (!LAYOUT_STATE.layout.lbls) LAYOUT_STATE.layout.lbls = {};
										LAYOUT_STATE.layout.lbls.bar_x = x;
										LAYOUT_STATE.layout.lbls.bar_y = y;
										LAYOUT_STATE.layout.lbls.bar_width = w;
										LAYOUT_STATE.layout.lbls.bar_height = h;
										layout_set_form('bar_x', x);
										layout_set_form('bar_y', y);
										layout_set_form('bar_width', w);
										layout_set_form('bar_height', h);
									}
									LAYOUT_STATE.dirty = true;
								}
								function layout_read_design_to_state() {
									if (!LAYOUT_STATE.layout || LAYOUT_STATE.layout.shared) return;
									var dx = document.getElementById('layout_des_x');
									if (!dx) return;
									var x = parseInt(dx.value, 10);
									var y = parseInt(document.getElementById('layout_des_y').value, 10);
									var w = parseInt(document.getElementById('layout_des_w').value, 10);
									var h = parseInt(document.getElementById('layout_des_h').value, 10);
									if (isNaN(x)) x = 0; if (isNaN(y)) y = 0;
									if (isNaN(w)) w = 0; if (isNaN(h)) h = 0;
									if (!LAYOUT_STATE.layout.lbls) LAYOUT_STATE.layout.lbls = {};
									LAYOUT_STATE.layout.lbls.design_x = x;
									LAYOUT_STATE.layout.lbls.design_y = y;
									LAYOUT_STATE.layout.lbls.design_width = w;
									LAYOUT_STATE.layout.lbls.design_height = h;
									layout_set_form('design_x', x);
									layout_set_form('design_y', y);
									layout_set_form('design_width', w);
									layout_set_form('design_height', h);
									LAYOUT_STATE.dirty = true;
								}
								function layout_bar_nudge(dx, dy) {
									var step = parseInt(document.getElementById('layout_bar_step').value, 10);
									if (isNaN(step) || step < 1) step = 20;
									var xi = document.getElementById('layout_bar_x');
									var yi = document.getElementById('layout_bar_y');
									var x = parseInt(xi.value, 10); if (isNaN(x)) x = 0;
									var y = parseInt(yi.value, 10); if (isNaN(y)) y = 0;
									xi.value = x + dx * step;
									yi.value = y + dy * step;
									layout_read_barcode_to_state();
									preview_zpl_label();
								}
								function layout_bar_apply() {
									layout_read_barcode_to_state();
									layout_save_json(function () { preview_zpl_label(); });
								}
								function layout_des_nudge(dx, dy) {
									var step = parseInt(document.getElementById('layout_des_step').value, 10);
									if (isNaN(step) || step < 1) step = 20;
									var xi = document.getElementById('layout_des_x');
									var yi = document.getElementById('layout_des_y');
									var x = parseInt(xi.value, 10); if (isNaN(x)) x = 0;
									var y = parseInt(yi.value, 10); if (isNaN(y)) y = 0;
									xi.value = x + dx * step;
									yi.value = y + dy * step;
									layout_read_design_to_state();
									preview_zpl_label();
								}
								function layout_des_apply() {
									layout_read_design_to_state();
									layout_save_json(function () { preview_zpl_label(); });
								}
								function layout_fill_elements() {
									var sel = document.getElementById('layout_field_sel');
									if (!sel || !LAYOUT_STATE.layout) return;
									var fields = LAYOUT_STATE.layout.fields || {};
									var keys = [];
									for (var k in fields) {
										if (!fields.hasOwnProperty(k)) continue;
										if (LAYOUT_SKIP_FIELDS[k]) continue;
										if (k === 'barcode') continue;
										keys.push(k);
									}
									keys.sort();
									sel.innerHTML = '<option value="">— seleccionar —</option>';
									for (var i = 0; i < keys.length; i++) {
										var o = document.createElement('option');
										o.value = 'campo:' + keys[i];
										o.text = keys[i];
										sel.appendChild(o);
									}
									layout_on_field_change();
								}
								function layout_on_field_change() {
									var key = document.getElementById('layout_field_sel').value;
									var box = document.getElementById('layout_coords_box');
									if (!key || key.indexOf('campo:') !== 0) {
										box.style.display = 'none';
										return;
									}
									box.style.display = '';
									var fname = key.substring(6);
									var f = (LAYOUT_STATE.layout.fields || {})[fname] || {};
									document.getElementById('layout_x').value = f.x != null ? f.x : 0;
									document.getElementById('layout_y').value = f.y != null ? f.y : 0;
									document.getElementById('layout_font').value = f.font != null ? f.font : 8;
									document.getElementById('layout_bold').value = f.bold ? 'true' : 'false';
									document.getElementById('layout_alcance').value = f.alcance != null ? f.alcance : 0;
									document.getElementById('layout_renglon').value = f.renglon != null ? f.renglon : 0;
									document.getElementById('layout_titulo').value = f.titulo != null ? f.titulo : '';
								}
								function layout_read_inputs_to_state() {
									if (!LAYOUT_STATE.layout) return;
									layout_read_barcode_to_state();
									layout_read_design_to_state();
									var key = document.getElementById('layout_field_sel').value;
									if (!key || key.indexOf('campo:') !== 0) return;
									var fname = key.substring(6);
									var x = parseInt(document.getElementById('layout_x').value, 10);
									var y = parseInt(document.getElementById('layout_y').value, 10);
									if (isNaN(x)) x = 0;
									if (isNaN(y)) y = 0;
									if (!LAYOUT_STATE.layout.fields) LAYOUT_STATE.layout.fields = {};
									if (!LAYOUT_STATE.layout.fields[fname]) LAYOUT_STATE.layout.fields[fname] = {};
									var f = LAYOUT_STATE.layout.fields[fname];
									f.x = x;
									f.y = y;
									f.font = layout_num('layout_font', 8);
									f.bold = document.getElementById('layout_bold').value === 'true';
									f.alcance = parseInt(document.getElementById('layout_alcance').value, 10) || 0;
									f.renglon = parseInt(document.getElementById('layout_renglon').value, 10) || 0;
									f.titulo = document.getElementById('layout_titulo').value || '';
									LAYOUT_STATE.dirty = true;
								}
								function layout_apply_inputs() {
									layout_read_inputs_to_state();
									layout_save_json(function () { preview_zpl_label(); });
								}
								function layout_nudge(dx, dy) {
									var step = parseInt(document.getElementById('layout_step').value, 10);
									if (isNaN(step) || step < 1) step = 20;
									var xi = document.getElementById('layout_x');
									var yi = document.getElementById('layout_y');
									var x = parseInt(xi.value, 10); if (isNaN(x)) x = 0;
									var y = parseInt(yi.value, 10); if (isNaN(y)) y = 0;
									xi.value = x + dx * step;
									yi.value = y + dy * step;
									layout_read_inputs_to_state();
									preview_zpl_label();
								}
								function layout_save_json(cb) {
									if (!LAYOUT_STATE.layout) {
										layout_load_json(function () { layout_save_json(cb); });
										return;
									}
									layout_read_inputs_to_state();
									LAYOUT_STATE.layout.codigo = layout_codigo();
									layout_set_status('Guardando JSON...');
									var xhr = new XMLHttpRequest();
									xhr.open('POST', 'api_label_layout.php?action=save', true);
									xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
									xhr.onreadystatechange = function () {
										if (xhr.readyState !== 4) return;
										var r = null;
										try { r = JSON.parse(xhr.responseText); } catch (e) { r = null; }
										if (!r || !r.ok) {
											layout_set_status((r && r.error) ? r.error : 'Error al guardar');
											return;
										}
										LAYOUT_STATE.dirty = false;
										layout_set_status('Guardado en ' + (r.path || 'JSON local'));
										if (typeof cb === 'function') cb();
									};
									xhr.send(JSON.stringify(LAYOUT_STATE.layout));
								}
								function preview_zpl_label() {
									var code = layout_codigo();
									if (!code) { layout_set_status('Falta codigo'); return; }
									if (!LAYOUT_STATE.layout) {
										layout_load_json(function () { preview_zpl_label(); });
										return;
									}
									layout_read_inputs_to_state();
									if (LAYOUT_STATE.dirty) {
										layout_save_json(function () { preview_zpl_label_go(); });
										return;
									}
									preview_zpl_label_go();
								}
								function preview_zpl_label_go() {
									var code = layout_codigo();
									var ce = layout_cad_elab();
									var img = document.getElementById('zpl_preview_img');
									var ph = document.getElementById('zpl_preview_placeholder');
									layout_set_status('Generando vista (JSON local)...');
									if (img) { img.style.display = 'none'; img.removeAttribute('src'); }
									var base = 'codigo=' + encodeURIComponent(code)
										+ '&caducidad=' + encodeURIComponent(ce.cad)
										+ '&elab_day=' + encodeURIComponent(ce.elab)
										+ '&use_json=1'
										+ '&_=' + (new Date().getTime());
									var xhr = new XMLHttpRequest();
									xhr.open('GET', 'preview_zpl.php?fmt=json&' + base, true);
									xhr.timeout = 60000;
									xhr.onreadystatechange = function () {
										if (xhr.readyState !== 4) return;
										if (xhr.status < 200 || xhr.status >= 300) {
											layout_set_status('Error vista (' + xhr.status + ')');
											return;
										}
										var r = null;
										try { r = JSON.parse(xhr.responseText); } catch (e1) { r = null; }
										if (!r || !r.ok) {
											layout_set_status((r && r.error) ? r.error : 'No se pudo generar');
											return;
										}
										layout_set_status('Tipo ' + r.etiqueta + ' — cargando imagen...');
										if (img) {
											img.onload = function () {
												img.style.display = 'inline';
												if (ph) ph.style.display = 'none';
												layout_set_status('Tipo ' + r.etiqueta + ' — ' + r.pw + 'x' + r.ll
													+ ' dots (' + r.width_in + '\" x ' + r.height_in + '\")');
											};
											img.onerror = function () { layout_set_status('Imagen fallo'); };
											img.src = 'preview_zpl.php?fmt=png&' + base;
										}
									};
									xhr.ontimeout = function () { layout_set_status('Tiempo agotado'); };
									xhr.send(null);
								}
								function layout_upload_image() {
									var code = layout_codigo();
									var fileEl = document.getElementById('imagen_diseno');
									if (!code) { alert('Falta codigo'); return; }
									if (!fileEl || !fileEl.files || !fileEl.files[0]) { alert('Seleccione un archivo'); return; }
									var fd = new FormData();
									fd.append('action', 'upload_image');
									fd.append('codigo', code);
									fd.append('imagen', fileEl.files[0]);
									layout_set_status('Subiendo imagen a printserver...');
									var xhr = new XMLHttpRequest();
									xhr.open('POST', 'api_label_layout.php', true);
									xhr.onreadystatechange = function () {
										if (xhr.readyState !== 4) return;
										var r = null;
										try { r = JSON.parse(xhr.responseText); } catch (e) { r = null; }
										if (!r || !r.ok) {
											layout_set_status((r && r.error) ? r.error : 'Error al subir imagen');
											return;
										}
										layout_set_status('Imagen guardada: printserver/' + r.file);
										var prev = document.getElementById('imagen_diseno_preview');
										if (prev) {
											prev.src = 'printserver/' + r.file + '?_=' + (new Date().getTime());
											prev.style.display = 'inline';
										}
										preview_zpl_label();
									};
									xhr.send(fd);
								}
								if (layout_codigo()) { layout_load_json(null); }
								</script>
							</td>
						</tr>
<tr>
							<td height="28" align="right"><strong>Imagen diseno (printserver)</strong></td>
							<td colspan="8" style="font-size:11px;color:#666;">
								Se guarda como <code>printserver/{codigo}.bmp|png|jpg</code> (misma ruta que usa etiqueta 10/14). No toca img_prd de MySQL.
							</td>
						</tr>
						<tr>
							<td height="28"><strong>
									<input type="text" hidden="hidden" name="code" id="code" value="<?php echo $row['codigo'] ?>" />
								</strong></td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td><strong>
									<input name="bttn_actualizar" type="submit" value="Actualizar" id="update_button" onClick="search_results.php?code=document.getElementById('txt_codigo').value&especif=document.getElementById('instruc').value&instruct=document.getElementById('ingre').value" disabled="disabled" />
									<input type="checkbox" name="checkbox" id="checkbox" onChange="document.getElementById('update_button').disabled = !this.checked;" />
								</strong></td>
						</tr>
						<tr>
							<td colspan="9">
								<div>
									<p><strong>Imagen diseno:</strong>
										<input type="file" name="imagen_diseno" id="imagen_diseno" accept=".bmp,.png,.jpg,.jpeg,.gif,image/*" />
										<input type="button" value="Guardar imagen" onclick="layout_upload_image();" />
									</p>
									<?php
									$__code_img = preg_replace('/\D/', '', (string)$row['codigo']);
									$__code_img = str_pad($__code_img, 6, '0', STR_PAD_LEFT);
									if (strlen($__code_img) > 6) {
										$__code_img = substr($__code_img, -6);
									}
									$__img_src = '';
									foreach (array('bmp', 'png', 'jpg', 'jpeg', 'gif') as $__ext) {
										$__p = __DIR__ . DIRECTORY_SEPARATOR . 'printserver' . DIRECTORY_SEPARATOR . $__code_img . '.' . $__ext;
										if (is_file($__p)) {
											$__img_src = 'printserver/' . $__code_img . '.' . $__ext;
											break;
										}
									}
									?>
									<img id="imagen_diseno_preview" alt="Diseno printserver" border="1" width="200"
										style="<?php echo $__img_src ? '' : 'display:none;'; ?>"
										src="<?php echo $__img_src ? htmlspecialchars($__img_src) . '?_=' . time() : ''; ?>" />
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="9"></td>
						</tr>
						<tr>
							<td colspan="9"></td>
						</tr>
					</table>

				</form>
			</body>

	</html>
	<SCRIPT language=JavaScript>
		window.opener.location.reload();
		self.close();
	</SCRIPT>

</html>