<?php
/**
 * Etiqueta 1 / 9 — plantilla ZPL chica.
 * Descripción con wrap SoftShop (alcance 24, máx. 2 renglones), como VB6.
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'zpl_chica_plantilla.php';

/**
 * Tipo 1 = con lote/exp (+ Reg. si hay reg_sanitario).
 * $layoutOverride se ignora (compat firma antigua).
 */
function build_zpl_etiqueta_1($codigo, $descrip, $precio, $cant, $expir, $fecha_manufact, $con_fecha = true, $layoutOverride = null, $descrip2 = '', $reg_sanitario = '')
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
		(bool)$con_fecha,
		true
	);
}

/** Tipo 9 = misma plantilla sin lote/exp/reg. */
function build_zpl_etiqueta_9($codigo, $descrip, $precio, $cant, $layoutOverride = null, $descrip2 = '')
{
	return build_zpl_chica_plantilla(
		$codigo,
		$descrip,
		$descrip2,
		$precio,
		$cant,
		0,
		'',
		'',
		false,
		false
	);
}
