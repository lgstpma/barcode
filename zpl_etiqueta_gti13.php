<?php
/**
 * ZPL GTI 13 — etiqueta caja unidades 3" × 2" @ 203 dpi (Supermercado Rey).
 * Botón "Imprimir GTI 13" → export_gti13.php → cola gti13 → GK420t_3x2.
 * Independiente del GTIN SoftShop chica (export_code13.php).
 */
if (!function_exists('zpl_escape_field')) {
	function zpl_escape_field($s)
	{
		if ($s === null || $s === '') {
			return '';
		}
		$s = str_replace(array("\r", "\n"), ' ', (string)$s);
		return str_replace(array('^', '~', '\\'), '', $s);
	}
}

/**
 * @param string $codigo2 GTIN-13
 * @param string $descrip
 * @param string $descrip2
 * @param int    $unidades Unidades en la caja (texto "N unidades")
 * @param int    $cant     Copias a imprimir (^PQ)
 * @param string $elab_day Y-m-d → Lote: DD/MM
 * @param int    $expir    Días → Exp: DD/MM
 * @param string $descrip3 Nombre preferido
 */
function build_zpl_etiqueta_gti13($codigo2, $descrip, $descrip2, $unidades, $cant = 1, $elab_day = '', $expir = 0, $descrip3 = '')
{
	$unidades = (int)$unidades;
	if ($unidades < 1) {
		$unidades = 1;
	}
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$cantStr = sprintf('%03d', $cant);

	$codigo2_digits = preg_replace('/\D/', '', (string)$codigo2);
	$nombre = trim((string)$descrip3);
	if ($nombre === '') {
		$nombre = trim(trim((string)$descrip) . ' ' . trim((string)$descrip2));
	}
	if (function_exists('mb_strtoupper')) {
		$nombre = mb_strtoupper($nombre, 'UTF-8');
	} else {
		$nombre = strtoupper($nombre);
	}
	$nombreZ = zpl_escape_field($nombre);

	$elab_day = trim((string)$elab_day);
	if ($elab_day === '') {
		$elab_day = date('Y-m-d');
	}
	$base = strtotime($elab_day);
	if ($base === false) {
		$base = time();
	}
	$expir = (int)$expir;
	if ($expir < 0) {
		$expir = 0;
	}
	$loteTxt = date('d/m', $base);
	$expTxt = date('d/m', strtotime('+' . $expir . ' days', $base));

	$gtinDisp = $codigo2_digits !== '' ? $codigo2_digits : zpl_escape_field($codigo2);
	$unidadesLine = $unidades . ' unidades';
	$loteLine = 'Lote: ' . $loteTxt;
	$expLine = 'Exp: ' . $expTxt;
	$brand = 'La Cocina de Sofy';

	if (strlen($codigo2_digits) === 13) {
		$barcodeBlock = '^FO50,58^BY2^BEN,90,N,N,N^FD' . $codigo2_digits . '^FS
^FO130,158^A0N,28,28^FD' . $codigo2_digits . '^FS';
	} else {
		$barcodeBlock = '^FO40,58^BY2,2,90
^BCN,90,N,N,N
^FD>;' . $gtinDisp . '^FS
^FO100,158^A0N,28,28^FD' . $gtinDisp . '^FS';
	}

	$qrPayload = $brand . "\n"
		. $nombre . "\n"
		. 'GTIN: ' . $gtinDisp . "\n"
		. $unidadesLine . "\n"
		. $loteLine . "\n"
		. $expLine;
	$qrPayload = str_replace(array('^', '~'), '', $qrPayload);

	$nameH = strlen($nombreZ) > 28 ? 28 : 36;
	$nameW = strlen($nombreZ) > 28 ? 26 : 34;

	return '^XA
^PW609
^LL406
^LH0,0
^CI28
^FO20,12^A0N,' . $nameH . ',' . $nameW . '^FD' . $nombreZ . '^FS
' . $barcodeBlock . '
^FO20,200^A0N,32,32^FD' . zpl_escape_field($unidadesLine) . '^FS
^FO20,245^A0N,28,28^FD' . zpl_escape_field($loteLine) . '^FS
^FO20,285^A0N,28,28^FD' . zpl_escape_field($expLine) . '^FS
^FO20,340^A0N,24,24^FD' . zpl_escape_field($brand) . '^FS
^FO420,40^BQN,2,4^FDQA,' . $qrPayload . '^FS
^PQ' . $cantStr . '
^XZ';
}
