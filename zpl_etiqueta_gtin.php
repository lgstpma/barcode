<?php
/**
 * ZPL GTIN chica (2.43" × 0.90" @ 203 dpi) — SoftShop horizontal.
 * Se dispara solo desde "Imprimir GTIN" → export_code13.php.
 * NO es items.etiqueta = 13 (ese es zpl_etiqueta_13.php, etiqueta rotada por IP).
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

function build_zpl_etiqueta_gtin($codigo2, $descrip, $descrip2, $cant)
{
	$cant = (int)$cant;
	if ($cant < 1) {
		$cant = 1;
	}
	$cantStr = sprintf('%03d', $cant);
	$codigo2_digits = preg_replace('/\D/', '', (string)$codigo2);
	$descripZ = zpl_escape_field($descrip);
	$descrip2Z = zpl_escape_field($descrip2);

	$barcode_h = 40;
	if (strlen($codigo2_digits) === 13) {
		$barcodeBlock = '^FO121,3^BEN,' . $barcode_h . ',N,N,N^FD' . $codigo2_digits . '^FS
^FO172,36^GB150,18,18,W,0^FS
^FO172,36^A0N,16,16^FD' . $codigo2_digits . '^FS';
	} else {
		$codigo2_disp = zpl_escape_field($codigo2);
		$barcodeBlock = '^FO102,3^BY1,2,' . $barcode_h . '
^BCN,' . $barcode_h . ',N,N,N
^FD>;' . $codigo2_digits . '^FS
^FO106,36^GB150,18,18,W,0^FS
^FO106,36^A0N,16,16^FD' . $codigo2_disp . '^FS';
	}

	return '^XA
^PW494
^LL183
^LH0,0


' . $barcodeBlock . '


^FO28,62^A0N,12,12^FD' . $descripZ . '^FS
^FO28,76^A0N,12,12^FD' . $descrip2Z . '^FS




^PQ' . $cantStr . '
^XZ';
}
