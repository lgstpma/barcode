<?php
/**
 * ZPL etiqueta 14 — VB6 Imprimir_lbl14.
 * Igual que formato 10 (BMP + lbls + lbl_lines) pero el código de barras es itemid2/codigo2.
 */
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'zpl_etiqueta_10.php');

function build_zpl_etiqueta_14($codigo, $codigo2, $cant = 1, $lbls = array(), $ctx = array(), $link = null)
{
	return build_zpl_etiqueta_14_bmp($codigo, $codigo2, $cant, $lbls, $ctx, $link);
}
