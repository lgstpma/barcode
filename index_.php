<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>


<script rel="stylesheet" href=" https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" type="text/css" />
</script>

<head>
  <style type="text/css">
    #caja_busqueda

    /*estilos para la caja principal de busqueda*/
      {
      width: 100px;
      height: 25px;
      border: solid 2px #979DAE;
      font-size: 25px;
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

  <?php

  include("conections.php");
  $link = conec_mysql();
  ?>

  <link href="/default.css" rel="stylesheet" type="text/css" />

<body>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" type="text/css" href="css/default.css" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Buscar Items</title>
  </head>

  <body>
    <form action="search_results.php" method="POST">
      <p><strong> Sistema de impresion de Etiquetas.</strong></p>
      <p> Codigo<strong>:</strong>
        <input type="number" min="0" class="busca" id="caja_busqueda" name="clave" autocomplete="off" required onKeyDown="settab()" />
        <input type="submit" name="button" id="button" value="Buscar">
    </form>
    <div>
      <red>Información de Cambios</red>

    </div>
    <div style="background-color:#03D65C">

    </div>

    <div class="tab">
      <button class="tablinks" onClick="openCity(event, 'London')">Distribucion</button>
      <button class="tablinks" onClick="openCity(event, 'Paris')">Articulos No Consumibles</button>

      <button class="tablinks" onClick="openCity(event, 'Tokyo')">Categorias</button>
      </p>
    </div>

    <div id="London" class="tabcontent">

      <?php


      $fm_criter =  "SELECT *, mypastelito.items.descrip as descripI   FROM mypastelito.items, mypastelito.lbls where LOCATE('FM',mypastelito.items.descrip) AND  not  LOCATE('_',mypastelito.items.descrip)    and  mypastelito.items.codigo = mypastelito.lbls.id  ORDER BY  mypastelito.items.descrip asc";

      // echo $fm_criter;
      $fm_sql_res = mysqli_query($link, $fm_criter);

      $fm_table = '<div>
      
      <table border="0"  id="distrib"  class="display" style="width:100%">
  
    <tr   > <thead>
      <td width="90" ># codigo</td>
      <td width="398"  >Descripción</td>
           <td width="90"  >Printer</td>
      <td width="75"  ># Etiqueta</td> 
    </thead>';


      $cont_linea = 1;

      while ($fm_row = mysqli_fetch_array($fm_sql_res)) {
        if ($cont_linea % 2 == 0) {
          $bgcolor =  'bgcolor="#C0C0C0"';
        } else {
          $bgcolor =  'bgcolor="#FFFFFF"';
        }


        $cont_linea = $cont_linea + 1;

        $fm_table = $fm_table . '<tr ' . $bgcolor . '><td align=center><a  href="search_results.php?code=' . $fm_row["codigo"] . '&bttn_actualizar=FM ">' . $fm_row["codigo"] . '</a></td>
      <td>' . $fm_row["descripI"] . $fm_row["descrip2"] . '</span></td><td>'   . $fm_row["printer"] . '</span><td>'   . $fm_row["etiqueta"] . '</span></td></tr>';
      }

      $fm_table = $fm_table . '</table></div>';

      echo $fm_table;
      ?>
    </div>

    <div id="Paris" class="tabcontent">
      <h3>Velas</h3>
      <link rel="stylesheet" href="jqwidgets/styles/jqx.base.css" type="text/css" />
      <script type="text/javascript" src="scripts/jquery-1.11.1.min.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxcore.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxdata.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxbuttons.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxscrollbar.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxmenu.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxgrid.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxgrid.selection.js"></script>
      <script type="text/javascript" src="jqwidgets/jqxgrid.columnsresize.js"></script>
      <script type="text/javascript" src="scripts/demos.js"></script>

      <script>
        function popupwindow(url, title, w, h) {
          var left = Math.round((screen.width / 2) - (w / 2));
          var top = Math.round((screen.height / 2) - (h / 2));
          return window.open(url, title, "toolbar=no, location=no, directories=no, status=no, " +
            "menubar=no, scrollbars=yes, resizable=no, copyhistory=no, width=" + w +
            ", height=" + h + ", top=" + top + ", left=" + left);
        }
      </script>

      <head>

      <body class="default">
        <?php




        function base64_to_jpeg($base64_string, $output_file)
        {
          $ifp = fopen($output_file, "wb");
          fwrite($ifp, base64_decode($base64_string));
          fclose($ifp);
          return ($output_file);
        }






        $criter_mysql = "
  SELECT *
	FROM
	items
	WHERE
	
  img_prd <> 'NULL' 
 
  and descrip  like   '%Vela%'
  order by descrip3";




        $sql_res_64bits = mysqli_query($link, $criter_mysql);


        while ($row_64bits = mysqli_fetch_array($sql_res_64bits)) {
          // $imagen = base64_encode(file_get_contents('barcode.php?text='.$row_64bits["codigo"]));

          echo (' <table border="1" cellspacing="0" cellpadding="0" width="900" style="font-size:30px">
    
    <tr>
      <td width="60" rowspan="3" valign="top"> <div class="product-image"> <img  src="' . base64_to_jpeg(base64_encode($row_64bits["img_prd"]), $row_64bits["codigo"] . '.jpg') . '" width="94" height="135" ></div><strong> </strong></p></td><h1>
      <td width="504" valign="top"><p><strong>Código:</strong><strong><a  href="search_results.php?code=' . $row_64bits["codigo"] . '&bttn_actualizar=FM ">' . $row_64bits["codigo"] . '</a></strong></p>
	  
	  <p>Descripción<strong>: </strong><strong>' . $row_64bits["descrip3"] . '</strong><strong> </strong></p>
	  <p><strong>Precio </strong>' . $row_64bits["precio1"] . '</p>
	  </h1>
	  </td> 
    
  </table> ');
        }





        $criter_mysql = "
  SELECT *
	FROM
	items
	WHERE
  img_prd <> 'NULL' 
  and descrip   like   '%FM%'";



        $sql_res_64bits = mysqli_query($link_64bits, $criter_mysql);


        while ($row_64bits = mysqli_fetch_array($sql_res_64bits)) {
          // $imagen = base64_encode(file_get_contents('barcode.php?text='.$row_64bits["codigo"]));

          echo (' <table border="1" cellspacing="0" cellpadding="0" width="735">
    
    <tr>
      <td width="230" rowspan="3" valign="top"> <div class="product-image"> <img  src="' . base64_to_jpeg(base64_encode($row_64bits["img_prd"]), $row_64bits["codigo"] . '.jpg') . '" width="194" height="235" ></div><strong> </strong></p></td>
      <td width="504" colspan="3" valign="top"><p>Descripción<strong>: </strong><strong>' . $row_64bits["descrip3"] . '</strong><strong> </strong></p></td>   <td width="504" valign="top"><p><strong>Código:</strong><strong>' . $row_64bits["codigo"] . '</strong></p></td>
      <td width="126" valign="top"><p><strong>Precio </strong>' . $row_64bits["precio1"] . '</p></td>
    </tr>
    <tr>
   
      <td width="170" valign="top"><p><strong>FM</strong></p></td>
    </tr>
    <tr>
      <td width="504" valign="top"> <img  src="' . base64_to_jpeg(base64_encode(file_get_contents('http://192.168.1.100/syncro_items/barcode.php?text=' . $row_64bits["codigo"])), $row_64bits["codigo"] . 'cod.jpg')    . '"   width="194" height="75" ><strong> </strong></p></td>
      <td width="126" valign="top"><p><strong>&nbsp;</strong></p></td>
      <td width="170" valign="top"><p><strong>&nbsp;</strong></p></td>
    </tr> s
  </table> ');
        }
        ?>
        </p>


        <p>


          </form>




        <div id="jqxgrid"></div>
      </body>
    </div>

    <div id="Tokyo" class="tabcontent">
      <h3>Categorias</h3>
      <p> </p>
    </div>


    <blockquote>&nbsp;</blockquote>

    <script language="JavaScript">
      window.onload = init;

      function init() {
        document.getElementById("caja_busqueda").focus();
      }
    </script>


  </body>

</html>