<?php
/**
 * Comandos operativos Zebra enviados por la cola MySQL.
 * El worker remoto no cambia: recibe el texto y lo manda RAW.
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . 'print_queue_21.php';

$link = conec_mysql();
$printers = print_migrate_printers();
$message = '';
$messageOk = false;
$qid = 0;

function ci_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ci_command($action)
{
	switch ($action) {
		case 'calibrate_gap_ribbon':
			// Etiquetas troqueladas (gap), transferencia térmica y tear-off.
			// Sin ^JUS: no guarda parámetros como configuración permanente.
			return array(
				'name' => 'Calibrar etiquetas con separación + ribbon',
				'zpl' => "~JA\r\n^XA^MNY^MTT^MMT^XZ\r\n~JC\r\n",
			);
		case 'recover':
			return array(
				'name' => 'Cancelar trabajos y reiniciar',
				'zpl' => "~JA\r\n~JR\r\n",
			);
		case 'feed_test':
			// Formato vacío: avanza hasta el siguiente inicio detectado.
			return array(
				'name' => 'Avanzar una etiqueta',
				'zpl' => "^XA^MNY^XZ\r\n^XA^XZ\r\n",
			);
	}
	return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$printer = isset($_POST['printer']) ? trim((string)$_POST['printer']) : '';
	$action = isset($_POST['command']) ? trim((string)$_POST['command']) : '';
	$confirm = isset($_POST['confirm']) && (string)$_POST['confirm'] === '1';
	$command = ci_command($action);

	if (!$link) {
		$message = 'No hay conexión con MySQL.';
	} elseif (!in_array($printer, $printers, true)) {
		$message = 'Impresora no permitida.';
	} elseif (!$command) {
		$message = 'Comando no permitido.';
	} elseif (!$confirm) {
		$message = 'Debe confirmar el envío.';
	} else {
		$itemid = 'CMD-' . date('Ymd-His');
		$qid = enqueue_zpl_mysql($link, 'cmd', $itemid, $command['zpl'], $printer);
		if ($qid) {
			$messageOk = true;
			$message = $command['name'] . ' encolado para ' . $printer . ' (job #' . (int)$qid . ').';
		} else {
			$message = 'No se pudo encolar el comando: ' . mysqli_error($link);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<title>Control de impresoras Zebra</title>
	<link rel="stylesheet" href="css/ui_modern.css?v=m4">
	<style>
		.ci-wrap{max-width:760px;margin:0 auto;padding:16px 12px 40px}
		.ci-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}
		.ci-head h1{margin:0 0 5px;color:#0f2744;font-size:24px}
		.ci-head p{margin:0;color:#64748b}
		.ci-back,.ci-btn{display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box;min-height:44px;border-radius:9px;padding:10px 14px;font-weight:650;text-decoration:none}
		.ci-back{border:1px solid #c5d0de;background:#fff;color:#1d4f8c;white-space:nowrap}
		.ci-card{background:#fff;border:1px solid #d7dee8;border-radius:14px;padding:16px;margin-bottom:12px;box-shadow:0 1px 2px rgba(16,24,40,.05)}
		.ci-card h2{margin:0 0 10px;color:#0f2744;font-size:17px}
		.ci-select{width:100%;box-sizing:border-box;min-height:46px;border:1px solid #c5d0de;border-radius:9px;padding:8px;font-size:16px;background:#fff}
		.ci-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
		.ci-action{border:1px solid #d7dee8;border-radius:11px;padding:12px;background:#f8fafc}
		.ci-action strong{display:block;color:#0f2744;margin-bottom:4px}
		.ci-action span{display:block;min-height:38px;color:#64748b;font-size:12px;margin-bottom:10px}
		.ci-btn{width:100%;border:0;background:#1d4f8c;color:#fff;cursor:pointer;font-size:15px}
		.ci-btn-warn{background:#b45309}
		.ci-check{display:flex;align-items:flex-start;gap:8px;margin-top:14px;color:#475569;font-size:13px}
		.ci-check input{width:20px;height:20px;flex:0 0 auto}
		.ci-msg{border-radius:10px;padding:12px 14px;margin-bottom:12px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
		.ci-msg.ok{background:#ecfdf5;border-color:#a7f3d0;color:#166534}
		.ci-note{font-size:12px;color:#64748b;margin:12px 0 0}
		@media(max-width:620px){
			.ci-head{display:block}.ci-back{width:100%;margin-top:12px}.ci-actions{grid-template-columns:1fr}
			.ci-action span{min-height:0}.ci-head h1{font-size:21px}
		}
	</style>
</head>
<body>
	<main class="ci-wrap">
		<header class="ci-head">
			<div>
				<h1>Control de impresoras</h1>
				<p>Comandos Zebra enviados por la cola al worker RAW.</p>
			</div>
			<a class="ci-back" href="index.php">← Inicio</a>
		</header>

		<?php if ($message !== '') { ?>
			<div class="ci-msg<?php echo $messageOk ? ' ok' : ''; ?>"><?php echo ci_h($message); ?></div>
		<?php } ?>

		<form method="post" class="ci-card">
			<h2>1. Seleccione la impresora</h2>
			<select class="ci-select" name="printer" required>
				<option value="">— Seleccionar —</option>
				<?php foreach ($printers as $printer) { ?>
					<option value="<?php echo ci_h($printer); ?>"<?php echo isset($_POST['printer']) && $_POST['printer'] === $printer ? ' selected' : ''; ?>>
						<?php echo ci_h($printer); ?>
					</option>
				<?php } ?>
			</select>

			<label class="ci-check">
				<input type="checkbox" name="confirm" value="1" required>
				<span>Confirmo que elegí la impresora correcta y que tiene etiquetas/ribbon instalados.</span>
			</label>

			<h2 style="margin-top:18px">2. Elija una acción</h2>
			<div class="ci-actions">
				<div class="ci-action">
					<strong>Calibrar separación + ribbon</strong>
					<span>Cancela pendientes, selecciona gap/ribbon/tear-off y ejecuta <code>~JC</code>. Avanzará varias etiquetas.</span>
					<button class="ci-btn" type="submit" name="command" value="calibrate_gap_ribbon"
						onclick="return confirm('¿Calibrar la impresora seleccionada? Avanzará varias etiquetas.');">Calibrar</button>
				</div>
				<div class="ci-action">
					<strong>Avanzar una etiqueta</strong>
					<span>Comprueba si el sensor se detiene correctamente en la siguiente separación.</span>
					<button class="ci-btn" type="submit" name="command" value="feed_test">Avanzar</button>
				</div>
				<div class="ci-action">
					<strong>Recuperar LED rojo</strong>
					<span>Cancela el búfer (<code>~JA</code>) y reinicia la Zebra (<code>~JR</code>).</span>
					<button class="ci-btn ci-btn-warn" type="submit" name="command" value="recover"
						onclick="return confirm('¿Cancelar trabajos y reiniciar la impresora seleccionada?');">Cancelar y reiniciar</button>
				</div>
			</div>
			<p class="ci-note">No se usa <code>^JUS</code>; los ajustes de calibración no se guardan como perfil permanente. Se omitió <code>~WC</code> porque su reporte es demasiado largo para rollos pequeños y puede dejar la Zebra en rojo.</p>
		</form>
	</main>
</body>
</html>
