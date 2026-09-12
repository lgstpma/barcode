# -*- coding: utf-8 -*-
"""
Print Viewer para Windows 7 / Zebra / VB6
Monitorea la carpeta del spool de Windows y convierte archivos .SPL ZPL a PNG.

Ejecutar:
    python print_viewer.py
Abrir:
    http://localhost:8088
"""
from __future__ import print_function

import os
import re
import json
import time
import zlib
import base64
import shutil
import threading
from datetime import datetime

try:
    from flask import Flask, send_from_directory, jsonify, request, Response
except Exception:
    raise SystemExit("Falta Flask. Instalar con: pip install flask pillow")

try:
    from PIL import Image
except Exception:
    raise SystemExit("Falta Pillow. Instalar con: pip install flask pillow")

# Ajusta esta ruta si el spool esta en otro equipo compartido.
# En Windows normalmente es: C:\\Windows\\System32\\spool\\PRINTERS
SPOOL_DIR = os.environ.get("SPOOL_DIR", r"C:\Windows\System32\spool\PRINTERS")
ARCHIVE_DIR = os.environ.get("ARCHIVE_DIR", os.path.join(os.getcwd(), "archivo_impresiones"))
PORT = int(os.environ.get("PORT", "8088"))
POLL_SECONDS = float(os.environ.get("POLL_SECONDS", "1.5"))

app = Flask(__name__)
seen = set()
lock = threading.Lock()


def ensure_dirs():
    if not os.path.exists(ARCHIVE_DIR):
        os.makedirs(ARCHIVE_DIR)
    thumbs = os.path.join(ARCHIVE_DIR, "png")
    if not os.path.exists(thumbs):
        os.makedirs(thumbs)


def read_text(path):
    data = open(path, "rb").read()
    # Muchos SPL comienzan con BOM o bytes no texto; latin1 preserva datos.
    return data.decode("latin1", "ignore")


def file_is_stable(path, wait=0.7):
    try:
        s1 = os.path.getsize(path)
        time.sleep(wait)
        s2 = os.path.getsize(path)
        return s1 == s2 and s2 > 0
    except Exception:
        return False


def extract_shd_info(shd_path):
    info = {"printer": "", "document": "", "user": ""}
    if not shd_path or not os.path.exists(shd_path):
        return info
    try:
        raw = open(shd_path, "rb").read()
        # Extrae cadenas unicode y ascii legibles del SHD.
        uni = re.findall(rb'(?:[\x20-\x7E]\x00){3,}', raw)
        asc = re.findall(rb'[\x20-\x7E]{4,}', raw)
        strings = []
        for s in uni:
            try:
                strings.append(s.decode('utf-16le', 'ignore').strip('\x00').strip())
            except Exception:
                pass
        for s in asc:
            try:
                strings.append(s.decode('latin1', 'ignore').strip())
            except Exception:
                pass
        strings = [s for s in strings if s]
        # Heuristica: buscar Zebra / ZDesigner / NICELbl / usuarios.
        for s in strings:
            if "ZDesigner" in s or "Zebra" in s or "GK420" in s:
                info["printer"] = s
            if "NICEL" in s.upper() or "LABEL" in s.upper() or "LBL" in s.upper():
                info["document"] = s
        if not info["document"] and strings:
            info["document"] = strings[0]
        return info
    except Exception:
        return info


def parse_zpl_params(zpl):
    def get_int(pattern, default=0):
        m = re.search(pattern, zpl)
        return int(m.group(1)) if m else default
    qty = 1
    m = re.search(r'\^PQ(\d+)', zpl)
    if m:
        qty = int(m.group(1))
    return {
        "pw": get_int(r'\^PW(\d+)', 0),
        "ll": get_int(r'\^LL0*(\d+)', 0),
        "lh": (0, 0),
        "ls": get_int(r'\^LS(-?\d+)', 0),
        "qty": qty,
        "darkness": (re.search(r'~SD(\d+)', zpl).group(1) if re.search(r'~SD(\d+)', zpl) else ""),
        "speed": (re.search(r'\^PR([^\^~\r\n]+)', zpl).group(1) if re.search(r'\^PR([^\^~\r\n]+)', zpl) else ""),
    }


def decode_gfa_to_image(zpl, out_png):
    """Decodifica ^GFA con :Z64: o hex normal. Soporta imagen única renderizada desde driver."""
    # Busca ^FOx,y^GFA,total,bytes_per_graphic,row_bytes,data^FS
    m = re.search(r'\^FO(-?\d+),(-?\d+)\^GFA,(\d+),(\d+),(\d+),(.+?)\^FS', zpl, re.S)
    if not m:
        # Alternativa sin FO antes
        m = re.search(r'\^GFA,(\d+),(\d+),(\d+),(.+?)\^FS', zpl, re.S)
        if not m:
            raise ValueError("No encontre bloque ^GFA en el SPL")
        x = y = 0
        total, used, row_bytes, data = int(m.group(1)), int(m.group(2)), int(m.group(3)), m.group(4)
    else:
        x, y = int(m.group(1)), int(m.group(2))
        total, used, row_bytes, data = int(m.group(3)), int(m.group(4)), int(m.group(5)), m.group(6)

    data = data.strip().replace('\r', '').replace('\n', '')
    if data.startswith(':Z64:'):
        payload = data[5:]
        # Puede terminar con :CRC; dejar solo base64.
        payload = payload.split(':')[0]
        raw = zlib.decompress(base64.b64decode(payload))
    else:
        # Limpia caracteres no hex y convierte.
        payload = re.sub(r'[^0-9A-Fa-f]', '', data)
        raw = bytes.fromhex(payload)

    height = int(len(raw) / row_bytes) if row_bytes else 0
    width = row_bytes * 8
    if height <= 0:
        raise ValueError("Altura de imagen invalida")

    img = Image.new('1', (width, height), 1)  # 1=blanco
    px = img.load()
    idx = 0
    for yy in range(height):
        for xb in range(row_bytes):
            b = raw[idx]
            idx += 1
            for bit in range(8):
                if b & (0x80 >> bit):
                    px[xb * 8 + bit, yy] = 0  # negro

    # Si ^PW/^LL indican canvas mayor, montar ahi.
    params = parse_zpl_params(zpl)
    canvas_w = max(params.get('pw') or 0, width + max(x, 0))
    canvas_h = max(params.get('ll') or 0, height + max(y, 0))
    canvas = Image.new('1', (canvas_w, canvas_h), 1)
    canvas.paste(img, (max(x, 0), max(y, 0)))
    canvas = canvas.convert('L')
    canvas.save(out_png)
    return {"width": canvas_w, "height": canvas_h, "gfa_width": width, "gfa_height": height}


def convert_spl(spl_path, shd_path=None):
    zpl = read_text(spl_path)
    params = parse_zpl_params(zpl)
    base = os.path.splitext(os.path.basename(spl_path))[0]
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    name = "%s_%s" % (stamp, base)
    png_dir = os.path.join(ARCHIVE_DIR, "png")
    png_path = os.path.join(png_dir, name + ".png")
    spl_copy = os.path.join(ARCHIVE_DIR, name + ".SPL")
    shd_copy = os.path.join(ARCHIVE_DIR, name + ".SHD")

    shutil.copy2(spl_path, spl_copy)
    if shd_path and os.path.exists(shd_path):
        shutil.copy2(shd_path, shd_copy)
    else:
        shd_copy = ""

    preview_info = {}
    error = ""
    try:
        preview_info = decode_gfa_to_image(zpl, png_path)
    except Exception as e:
        error = str(e)
        png_path = ""

    shd_info = extract_shd_info(shd_path)
    rec = {
        "id": name,
        "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "source_spl": spl_path,
        "spl": os.path.basename(spl_copy),
        "shd": os.path.basename(shd_copy) if shd_copy else "",
        "png": os.path.basename(png_path) if png_path else "",
        "printer": shd_info.get("printer", ""),
        "document": shd_info.get("document", ""),
        "params": params,
        "preview": preview_info,
        "error": error,
    }
    append_record(rec)
    return rec


def index_path():
    return os.path.join(ARCHIVE_DIR, "index.json")


def load_records():
    p = index_path()
    if not os.path.exists(p):
        return []
    try:
        return json.load(open(p, "r"))
    except Exception:
        return []


def append_record(rec):
    with lock:
        records = load_records()
        records.insert(0, rec)
        records = records[:500]
        with open(index_path(), "w") as f:
            json.dump(records, f, indent=2)


def monitor_loop():
    ensure_dirs()
    while True:
        try:
            if os.path.exists(SPOOL_DIR):
                for fn in os.listdir(SPOOL_DIR):
                    if not fn.upper().endswith(".SPL"):
                        continue
                    spl_path = os.path.join(SPOOL_DIR, fn)
                    key = spl_path + ":" + str(os.path.getmtime(spl_path))
                    if key in seen:
                        continue
                    if not file_is_stable(spl_path):
                        continue
                    seen.add(key)
                    shd_path = os.path.splitext(spl_path)[0] + ".SHD"
                    convert_spl(spl_path, shd_path)
        except Exception as e:
            print("Monitor error:", e)
        time.sleep(POLL_SECONDS)


@app.route('/')
def home():
    html = """
<!doctype html><html lang='es'><head><meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>Print Viewer Zebra</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#f4f5f7;color:#222}.top{background:#1f2937;color:white;padding:14px 20px}.wrap{display:flex;gap:18px;padding:18px}.left{width:360px}.card{background:white;border-radius:10px;box-shadow:0 1px 5px #0002;margin-bottom:12px;padding:12px}.item{cursor:pointer;border-left:4px solid #ddd}.item:hover{background:#f8fafc}.small{font-size:12px;color:#666}.viewer{flex:1}.preview{background:white;border-radius:10px;box-shadow:0 1px 5px #0002;padding:18px;text-align:center}.preview img{max-width:100%;image-rendering:auto;border:1px solid #ddd}.badge{display:inline-block;background:#e5e7eb;border-radius:999px;padding:3px 8px;font-size:12px;margin:2px}button{padding:8px 12px;border:0;border-radius:7px;background:#2563eb;color:white;cursor:pointer}pre{text-align:left;background:#111827;color:#e5e7eb;padding:12px;border-radius:8px;overflow:auto}
</style></head><body>
<div class='top'><b>Print Viewer Zebra</b> <span class='small' style='color:#d1d5db'>Monitoreando: __SPOOL__</span></div>
<div class='wrap'><div class='left'><div class='card'><button onclick='loadData()'>Actualizar</button> <span class='small'>auto cada 3s</span></div><div id='list'></div></div><div class='viewer'><div id='detail' class='preview'><h2>Esperando impresiones...</h2></div></div></div>
<script>
let records=[];
async function loadData(){records=await (await fetch('/api/records')).json(); renderList(); if(records.length&&!window.selected){show(records[0].id)}}
function renderList(){let el=document.getElementById('list'); el.innerHTML=records.map(r=>`<div class='card item' onclick="show('${r.id}')"><b>${r.time}</b><br><span>${r.document||'Impresion'}</span><br><span class='small'>${r.printer||''}</span><br><span class='badge'>PW ${r.params.pw}</span><span class='badge'>LL ${r.params.ll}</span><span class='badge'>Cant. ${r.params.qty}</span>${r.error?'<br><span style="color:red">'+r.error+'</span>':''}</div>`).join('')}
function show(id){window.selected=id; let r=records.find(x=>x.id===id); if(!r)return; let img=r.png?`<img src='/png/${r.png}?t=${Date.now()}'>`:'<p>No se pudo generar vista previa.</p>'; document.getElementById('detail').innerHTML=`<h2>${r.document||'Impresion'}</h2><p>${r.time} - ${r.printer||''}</p>${img}<p><a href='/download/${r.spl}'>Descargar SPL</a> ${r.shd?" | <a href='/download/"+r.shd+"'>Descargar SHD</a>":''}</p><pre>${JSON.stringify(r.params,null,2)}</pre>`}
setInterval(loadData,3000); loadData();
</script></body></html>
""".replace("__SPOOL__", SPOOL_DIR.replace('\\','\\\\'))
    return Response(html, mimetype='text/html')

@app.route('/api/records')
def api_records():
    return jsonify(load_records())

@app.route('/png/<path:fn>')
def png(fn):
    return send_from_directory(os.path.join(ARCHIVE_DIR, "png"), fn)

@app.route('/download/<path:fn>')
def download(fn):
    return send_from_directory(ARCHIVE_DIR, fn, as_attachment=True)


if __name__ == '__main__':
    ensure_dirs()
    print("Monitoreando:", SPOOL_DIR)
    print("Archivo:", ARCHIVE_DIR)
    t = threading.Thread(target=monitor_loop)
    t.daemon = True
    t.start()
    app.run(host='0.0.0.0', port=PORT, debug=False)
