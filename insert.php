<?php
ini_set('memory_limit', '2500M'); // o

include("conections.php"); // si tu código de conexión está en otro archivo, inclúyelo aquí
include("data_array.php");
$link = conec_mysql();
  
 print_r($items);
?>
