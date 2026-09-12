<?php
/**
 * Confirmación de impresión (vista operativa) + detalle técnico plegable.
 */
function print_report_estado_txt($est)
{
	$est = (int)$est;
	$map = array(
		0 => 'En cola',
		1 => 'Impreso',
		2 => 'Error',
		3 => 'Enviando a impresora',
	);
	return isset($map[$est]) ? $map[$est] : ('Estado ' . $est);
}

/**
 * @param array $ctx keys: codigo, descrip, tipo, cant, printer, qid, path (mysql|legacy|ip), notes, zpl, raw_log
 */
function print_report_render($ctx)
{
	$codigo = isset($ctx['codigo']) ? (string)$ctx['codigo'] : '';
	$descrip = isset($ctx['descrip']) ? (string)$ctx['descrip'] : '';
	$tipo = isset($ctx['tipo']) ? (string)$ctx['tipo'] : '';
	$cant = isset($ctx['cant']) ? (string)$ctx['cant'] : '';
	$printer = isset($ctx['printer']) ? (string)$ctx['printer'] : '';
	$qid = isset($ctx['qid']) ? (int)$ctx['qid'] : 0;
	$path = isset($ctx['path']) ? (string)$ctx['path'] : '';
	$notes = isset($ctx['notes']) ? (string)$ctx['notes'] : '';
	$zpl = isset($ctx['zpl']) ? (string)$ctx['zpl'] : '';
	$raw = isset($ctx['raw_log']) ? (string)$ctx['raw_log'] : '';

	$h = function ($s) {
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	};

	$pathLabel = 'Cola';
	if ($path === 'mysql') {
		$pathLabel = 'Cola MySQL (servicio nuevo)';
	} elseif ($path === 'legacy') {
		$pathLabel = 'Legacy isabel_label_print';
	} elseif ($path === 'ip') {
		$pathLabel = 'Envío directo por IP';
	}

	$initialStatus = $qid > 0 ? 'Enviando…' : ($path === 'ip' ? 'Enviado' : 'Enviado');
	$badgeClass = $qid > 0 ? 'pr-badge pr-badge-wait' : 'pr-badge pr-badge-ok';

	echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<title>Impresión ' . $h($codigo) . '</title>';
	echo '<link rel="stylesheet" href="css/ui_modern.css">';
	echo '<link rel="stylesheet" href="css/print_report.css">';
	echo '</head><body class="pr-body">';

	echo '<div class="pr-wrap pr-wrap-simple">';
	echo '<header class="pr-header pr-header-simple">';
	echo '<div><p class="pr-eyebrow">Etiqueta enviada</p>';
	echo '<h1>' . $h($descrip !== '' ? $descrip : ('Código ' . $codigo)) . '</h1>';
	echo '<p class="pr-sub">#' . $h($codigo) . ' · Formato ' . $h($tipo) . ' · Cant. ' . $h($cant) . '</p></div>';
	echo '<div class="' . $badgeClass . '" id="prStatusBadge">' . $h($initialStatus) . '</div>';
	echo '</header>';

	echo '<form class="pr-actions pr-actions-main" method="post" action="index.php">';
	echo '<button type="submit" class="pr-btn">Nueva búsqueda</button>';
	echo '<a class="pr-btn pr-btn-ghost" href="index.php">Inicio</a>';
	echo '</form>';

	echo '<details class="pr-card pr-tech pr-tech-block">';
	echo '<summary>Detalle técnico</summary>';
	echo '<div class="pr-tech-inner">';

	echo '<section class="pr-grid">';
	echo '<article class="pr-subcard"><h2>Destino</h2>';
	echo '<dl class="pr-dl">';
	echo '<div><dt>Impresora</dt><dd>' . $h($printer !== '' ? $printer : '—') . '</dd></div>';
	echo '<div><dt>Ruta</dt><dd>' . $h($pathLabel) . '</dd></div>';
	if ($qid > 0) {
		echo '<div><dt>Job cola</dt><dd>#' . (int)$qid . '</dd></div>';
	}
	echo '</dl></article>';

	echo '<article class="pr-subcard"><h2>Spooler</h2>';
	echo '<p class="pr-spool-line" id="prSpoolLine">';
	if ($qid > 0) {
		echo 'Seguimiento en vivo de <code>isabel_zpl_queue</code>.';
	} else {
		echo 'Este trabajo no pasó por la cola ZPL unificada.';
	}
	echo '</p>';
	echo '<ul class="pr-steps" id="prSteps">';
	echo '<li data-s="0">En cola</li><li data-s="3">Enviando</li><li data-s="1">Impreso</li>';
	echo '</ul>';
	if ($notes !== '') {
		echo '<p class="pr-notes">' . $h($notes) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	echo '<section class="pr-subcard pr-preview-card">';
	echo '<h2>Vista previa / spooler Windows</h2>';
	echo '<div class="pr-preview-grid">';
	if ($qid > 0) {
		echo '<div class="pr-preview-box"><p class="pr-preview-cap">ZPL encolado (Labelary)</p>';
		echo '<img class="pr-preview-img" id="prPreviewImg" alt="Vista previa" src="preview_job_zpl.php?key=barcode21&amp;id=' . (int)$qid . '&amp;t=' . time() . '" onerror="this.style.display=\'none\';var e=document.getElementById(\'prPrevErr\');if(e)e.style.display=\'block\';">';
		echo '<p id="prPrevErr" class="pr-notes" style="display:none">Vista previa online no disponible (esta PC no llega a Labelary). <b>No afecta la impresión</b> — revise el estado del job o Print Viewer.</p></div>';
	} elseif ($zpl !== '') {
		echo '<div class="pr-preview-box"><p class="pr-preview-cap">ZPL generado</p>';
		echo '<p class="pr-notes">Sin id de cola; vea ZPL abajo o Print Viewer.</p></div>';
	}
	echo '<div class="pr-preview-box"><p class="pr-preview-cap">Print Viewer</p>';
	echo '<p class="pr-notes">Spool Windows: <code>print_viewer_zebra</code> → <a href="http://127.0.0.1:8088/" target="_blank" rel="noopener">:8088</a></p>';
	echo '</div></div></section>';

	echo '<section class="pr-subcard pr-recent">';
	echo '<h2>Últimos jobs' . ($printer !== '' ? (' · ' . $h($printer)) : '') . '</h2>';
	echo '<div class="pr-table-wrap"><table class="pr-table" id="prRecent"><thead><tr>';
	echo '<th>ID</th><th>Item</th><th>Fmt</th><th>Estado</th><th>Hora</th>';
	echo '</tr></thead><tbody><tr><td colspan="5">Cargando…</td></tr></tbody></table></div>';
	echo '</section>';

	if ($zpl !== '') {
		echo '<details class="pr-nested"><summary>ZPL generado</summary>';
		echo '<pre class="pr-pre">' . $h($zpl) . '</pre></details>';
	}
	if (trim($raw) !== '') {
		echo '<details class="pr-nested"><summary>Log técnico</summary>';
		echo '<pre class="pr-pre">' . $h($raw) . '</pre></details>';
	}

	echo '</div></details>';
	echo '</div>';

	$qidJs = (int)$qid;
	$prnJs = json_encode($printer);
	echo '<script>
(function(){
  var qid = ' . $qidJs . ';
  var printer = ' . $prnJs . ';
  var badge = document.getElementById("prStatusBadge");
  var line = document.getElementById("prSpoolLine");
  var steps = document.getElementById("prSteps");
  var tbody = document.querySelector("#prRecent tbody");
  var done = false;
  function esc(s){ return String(s==null?"":s).replace(/[&<>]/g,function(c){return {"&":"&amp;","<":"&lt;",">":"&gt;"}[c];}); }
  function setSteps(est){
    if(!steps) return;
    var lis = steps.querySelectorAll("li");
    for(var i=0;i<lis.length;i++){
      var s = parseInt(lis[i].getAttribute("data-s"),10);
      lis[i].className = "";
      if(est===2){ lis[i].className = "err"; continue; }
      if(est===1 && s===1) lis[i].className = "on";
      else if(est===3 && (s===0||s===3)) lis[i].className = (s===3?"on":"done");
      else if(est===0 && s===0) lis[i].className = "on";
      else if(est===1 && (s===0||s===3)) lis[i].className = "done";
      else if(est===3 && s===0) lis[i].className = "done";
    }
  }
  function paintJob(job){
    if(!job) return;
    var txt = job.estado_txt || "";
    var map = {pendiente:"En cola",en_spooler:"Enviando…",impreso:"Impreso",error:"Error"};
    var label = map[txt] || txt;
    badge.className = "pr-badge";
    if(job.estado===1){ badge.className += " pr-badge-ok"; done = true; }
    else if(job.estado===2){ badge.className += " pr-badge-err"; done = true; }
    else if(job.estado===3){ badge.className += " pr-badge-run"; }
    else { badge.className += " pr-badge-wait"; }
    badge.textContent = label;
    setSteps(job.estado);
    if(!line) return;
    var extra = "";
    if(job.locked_by) extra = " · worker " + job.locked_by;
    if(job.printed_at) extra += " · " + job.printed_at;
    line.innerHTML = "Job <strong>#" + job.id + "</strong> → <strong>" + esc(job.printer) + "</strong>" + esc(extra);
  }
  function paintRecent(jobs){
    if(!tbody) return;
    if(!jobs || !jobs.length){ tbody.innerHTML = "<tr><td colspan=5>Sin jobs recientes</td></tr>"; return; }
    var html = "";
    for(var i=0;i<jobs.length;i++){
      var j = jobs[i];
      var cls = j.estado===1?"ok":(j.estado===2?"err":(j.estado===3?"run":""));
      html += "<tr class=\\""+cls+"\\"><td>#"+j.id+"</td><td>"+esc(j.itemid)+"</td><td>"+esc(j.etiqueta)+"</td><td>"+esc(j.estado_txt)+"</td><td>"+esc(j.printed_at||j.created_at)+"</td></tr>";
    }
    tbody.innerHTML = html;
  }
  function tick(){
    var urlRecent = "api_spool_status.php?key=barcode21&limit=10";
    if(printer) urlRecent += "&printer=" + encodeURIComponent(printer);
    var xhr2 = new XMLHttpRequest();
    xhr2.open("GET", urlRecent, true);
    xhr2.onreadystatechange = function(){
      if(xhr2.readyState!==4) return;
      try {
        var data = JSON.parse(xhr2.responseText);
        if(data && data.ok) paintRecent(data.jobs);
      } catch(e){}
    };
    xhr2.send();
    if(!qid || done) return;
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "api_spool_status.php?key=barcode21&id=" + qid, true);
    xhr.onreadystatechange = function(){
      if(xhr.readyState!==4) return;
      try {
        var data = JSON.parse(xhr.responseText);
        if(data && data.ok && data.job) paintJob(data.job);
      } catch(e){}
    };
    xhr.send();
  }
  tick();
  setInterval(tick, 2000);
})();
</script>';
	echo '</body></html>';
}
