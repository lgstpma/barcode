<?php
/**
 * Chequeo de impresoras migradas (print_migrate.cfg) + formatos.
 * Exit 0 = OK (o solo avisos). Exit 2 = falta impresora requerida.
 * Uso: php tools/check_migrate_printers.php
 */
$root = dirname(__DIR__);
$cfg = $root . DIRECTORY_SEPARATOR . 'print_migrate.cfg';

$formatosPorPrinter = array(
	'GK420t_chica' => 'chica: formatos 1, 9, 21 y GTIN',
	'GK420t_grande' => 'mediana SoftShop: formato 5',
	'GK420t_3x1.25' => 'BMP / lbls (suele 10 o 14)',
	'GK420t_3x2' => 'BMP / lbls (suele 10 o 14)',
	'VirtualZPLPrinter_Sistemas' => 'prueba virtual ZPL',
);

function migrate_printers_from_cfg($path)
{
	$list = array();
	if (!is_readable($path)) {
		return $list;
	}
	$lines = file($path, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines)) {
		return $list;
	}
	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		$list[] = $line;
	}
	return $list;
}

function windows_wmic_bin()
{
	$candidates = array(
		(isset($_SERVER['SystemRoot']) ? $_SERVER['SystemRoot'] : 'C:\\Windows') . '\\System32\\wbem\\wmic.exe',
		'C:\\Windows\\System32\\wbem\\wmic.exe',
		'wmic',
	);
	foreach ($candidates as $c) {
		if ($c === 'wmic') {
			return $c;
		}
		if (is_file($c)) {
			return $c;
		}
	}
	return 'wmic';
}

/** Lista impresoras via COM WMI (no necesita wmic en PATH). */
function windows_printers_com()
{
	$out = array();
	if (!class_exists('COM')) {
		return $out;
	}
	try {
		$locator = new COM('WbemScripting.SWbemLocator');
		$svc = $locator->ConnectServer('.', 'root\\cimv2');
		$printers = $svc->ExecQuery('SELECT Name,PortName,PrinterStatus,WorkOffline FROM Win32_Printer');
		foreach ($printers as $p) {
			$name = trim((string)$p->Name);
			if ($name === '' || strpos($name, '\\\\') === 0) {
				continue;
			}
			$out[$name] = array(
				'port' => trim((string)$p->PortName),
				'status' => (string)$p->PrinterStatus,
				'offline' => ((bool)$p->WorkOffline) ? 'TRUE' : 'FALSE',
			);
		}
	} catch (Exception $e) {
		return array();
	}
	return $out;
}

function windows_printers_wmic()
{
	$out = array();
	$wmic = windows_wmic_bin();
	$cmd = '"' . $wmic . '" printer get Name,PortName,PrinterStatus,WorkOffline /FORMAT:CSV';
	$lines = array();
	exec($cmd, $lines);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || stripos($line, 'Node,Name') === 0 || stripos($line, 'Name,') === 0) {
			continue;
		}
		$parts = str_getcsv($line);
		if (count($parts) < 4) {
			continue;
		}
		$name = '';
		$port = '';
		$status = '';
		$offline = '';
		if (count($parts) >= 5) {
			$name = trim($parts[1]);
			$port = trim($parts[2]);
			$status = trim($parts[3]);
			$offline = trim($parts[4]);
		} else {
			$name = trim($parts[0]);
			$port = trim($parts[1]);
			$status = trim($parts[2]);
			$offline = isset($parts[3]) ? trim($parts[3]) : '';
		}
		if ($name === '' || strcasecmp($name, 'Name') === 0) {
			continue;
		}
		$out[$name] = array(
			'port' => $port,
			'status' => $status,
			'offline' => $offline,
		);
	}
	return $out;
}

function windows_printers()
{
	$out = windows_printers_com();
	if (count($out) > 0) {
		return $out;
	}
	return windows_printers_wmic();
}

function status_label($code)
{
	$map = array(
		'1' => 'Other',
		'2' => 'Unknown',
		'3' => 'Idle (lista)',
		'4' => 'Printing',
		'5' => 'Warmup',
		'6' => 'Stopped',
		'7' => 'Offline',
	);
	$c = (string)$code;
	return isset($map[$c]) ? $map[$c] : ("codigo $c");
}

function find_printer($wanted, $installed)
{
	foreach ($installed as $name => $info) {
		if (strcasecmp($name, $wanted) === 0) {
			return array($name, $info);
		}
	}
	foreach ($installed as $name => $info) {
		if (stripos($name, $wanted) !== false || stripos($wanted, $name) !== false) {
			return array($name, $info);
		}
	}
	return null;
}

echo "============================================\n";
echo " Impresoras migradas (print_migrate.cfg)\n";
echo "============================================\n";

$wanted = migrate_printers_from_cfg($cfg);
if (count($wanted) === 0) {
	echo "AVISO: print_migrate.cfg vacio o no legible.\n";
	echo "Nada se encola a isabel_zpl_queue; todo iria al legacy VB6.\n";
	exit(0);
}

$installed = windows_printers();
if (count($installed) === 0) {
	echo "\nAVISO: no se pudo listar impresoras Windows (COM/wmic).\n";
	echo "Revise que la Zebra este instalada como GK420t_chica.\n";
}

$fail = 0;

foreach ($wanted as $prn) {
	$fmt = isset($formatosPorPrinter[$prn])
		? $formatosPorPrinter[$prn]
		: 'formatos segun lbls / cola (revisar producto)';
	echo "\n>> $prn\n";
	echo "   Formatos: $fmt\n";
	$hit = find_printer($prn, $installed);
	if ($hit === null) {
		echo "   Estado: NO ENCONTRADA en Windows\n";
		echo "   Accion: conecte la Zebra e instalela con ese nombre exacto.\n";
		$fail++;
		continue;
	}
	list($realName, $info) = $hit;
	$off = strtoupper((string)$info['offline']);
	$st = status_label($info['status']);
	echo "   Windows: $realName\n";
	echo "   Puerto:  " . $info['port'] . "\n";
	echo "   Estado:  $st\n";
	if ($off === 'TRUE' || $off === '1') {
		echo "   AVISO: WorkOffline=TRUE (Windows la marca offline)\n";
		$fail++;
	} elseif ((string)$info['status'] === '7') {
		echo "   AVISO: PrinterStatus=Offline\n";
		$fail++;
	} else {
		echo "   OK: lista para imprimir en esta PC\n";
	}
}

echo "\n============================================\n";
if ($fail > 0) {
	echo "RESULTADO: $fail problema(s). Corrija impresora y vuelva a start.bat\n";
	echo "============================================\n";
	exit(2);
}
echo "RESULTADO: impresoras migradas OK\n";
echo "============================================\n";
exit(0);
