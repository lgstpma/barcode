<?php
/**
 * Etiqueta #15 — misma plantilla ZPL chica (archivo único: zpl_chica_plantilla.php).
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'zpl_chica_plantilla.php';

function build_zpl_etiqueta_15($codigo, $descrip, $descrip2, $precio, $cant, $expir, $fecha_manufact, $reg_sanitario = '')
{
	return build_zpl_chica_plantilla(
		$codigo,
		$descrip,
		$descrip2,
		$precio,
		$cant,
		$expir,
		$fecha_manufact,
		$reg_sanitario,
		true,
		true
	);
}
