# Servicio UNICO de cola ZPL (PowerShell 2.0+)
# NO genera formatos: solo toma ZPL de la API (PC Servicios) e imprime RAW.
# Formatos: 1,5,9,10,14,21,gtin,gti13,cajas (y 13 por IP en el web).
# Impresoras: print_migrate.cfg (todas las GK420t_* migradas).
#
# Multi-PC:
#   - API claim atomico (estado 0→3)
#   - Si esta PC no tiene la impresora del job, libera el job
#   - Claims viejos vuelven a pendientes
#
# PC impresoras: configurar_api_servicios.bat + arrancar_worker.bat
# Prueba: probar.bat

# API de ESTA copia (start.bat en 8080). Alternativa remota en config.local.ps1:
# $ApiUrl = "http://IP-SERVICIOS:8080/api_print_21.php"
$ApiUrl          = "http://127.0.0.1:8080/api_print_21.php"
$LocalQueueDir   = ""
$PrinterDefault  = "GK420t_chica"
$PrinterForce    = ""
$PrinterFilter   = ""
# Fallback si no hay print_migrate.cfg. El cfg (y luego config.local.ps1) lo pisan.
$AcceptPrinters  = "GK420t_chica,GK420t_grande,GK420t_3x1.25,GK420t_2x3"
$PrinterAliases  = @{}
$SkipUncPrinters = $true
$ShowPrinterList = $true
$ApiKey          = "barcode21"
$SleepSec        = 3
$WorkerId        = $env:COMPUTERNAME + "-zpl"
$TempZpl         = Join-Path $env:TEMP ("etiqueta_zpl_" + $PID + ".zpl")

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$LogFile = Join-Path $scriptDir "print_service.log"

function Write-Log($msg) {
    $line = ("{0} {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $msg)
    Write-Host $line
    try { Add-Content -Path $script:LogFile -Value $line -ErrorAction SilentlyContinue } catch {}
}

Write-Log ("Worker arrancando PID=" + $PID + " script=" + $MyInvocation.MyCommand.Path)

function Normalize-AcceptPrinters([string]$raw) {
    if ([string]::IsNullOrEmpty($raw)) { return "" }
    # Quita saltos de linea / espacios rotos (ej. "GK420t_2`nx3" -> "GK420t_2x3")
    $s = $raw -replace "[\r\n\t ]+", ""
    return $s
}

function Read-MigrateAcceptPrinters {
    $migrateCfg = Join-Path $scriptDir "print_migrate.cfg"
    if (-not (Test-Path $migrateCfg)) {
        $parentDir = Split-Path $scriptDir -Parent
        if ($parentDir) {
            $migrateCfg = Join-Path $parentDir "print_migrate.cfg"
        }
    }
    if (-not (Test-Path $migrateCfg)) { return $null }
    $migNames = @()
    foreach ($line in (Get-Content $migrateCfg -ErrorAction SilentlyContinue)) {
        $t = ([string]$line).Trim()
        if ($t -eq "") { continue }
        if ($t.StartsWith("#")) { continue }
        $migNames += $t
    }
    if ($migNames.Count -eq 0) { return $null }
    return [string]::Join(",", $migNames)
}

$localCfg = Join-Path $scriptDir "config.local.ps1"
if (Test-Path $localCfg) {
    try {
        . $localCfg
        Write-Log "Usando config.local.ps1"
    } catch {
        Write-Log ("ERROR cargando config.local.ps1: " + $_.Exception.Message)
        Write-Log "Revise sintaxis. Use print_service_21\reparar_config_local.bat"
        Write-Log "AcceptPrinters debe ir en UNA sola linea."
        exit 1
    }
}
# print_migrate.cfg MANDA sobre AcceptPrinters de config.local (evita lista rota).
$fromMigrate = Read-MigrateAcceptPrinters
if ($fromMigrate) {
    $AcceptPrinters = $fromMigrate
    Write-Log ("AcceptPrinters desde print_migrate.cfg: " + $AcceptPrinters)
}
$AcceptPrinters = Normalize-AcceptPrinters $AcceptPrinters
if ([string]::IsNullOrEmpty($WorkerId)) {
    $WorkerId = $env:COMPUTERNAME + "-zpl"
}
if ($null -eq $PrinterAliases) {
    $PrinterAliases = @{}
}

$rawType = @"
using System;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.drv", EntryPoint="OpenPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.drv", EntryPoint="ClosePrinter", SetLastError=true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="StartDocPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.drv", EntryPoint="EndDocPrinter", SetLastError=true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="StartPagePrinter", SetLastError=true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="EndPagePrinter", SetLastError=true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="WritePrinter", SetLastError=true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);

    public static string SendString(string printerName, string zpl) {
        IntPtr hPrinter = IntPtr.Zero;
        if (!OpenPrinter(printerName, out hPrinter, IntPtr.Zero)) {
            return "OpenPrinter failed err=" + Marshal.GetLastWin32Error() + " name=" + printerName;
        }
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "zpl_queue";
        di.pDataType = "RAW";
        if (!StartDocPrinter(hPrinter, 1, di)) {
            int e = Marshal.GetLastWin32Error();
            ClosePrinter(hPrinter);
            return "StartDocPrinter failed err=" + e;
        }
        StartPagePrinter(hPrinter);
        byte[] bytes = System.Text.Encoding.ASCII.GetBytes(zpl);
        IntPtr p = Marshal.AllocCoTaskMem(bytes.Length);
        Marshal.Copy(bytes, 0, p, bytes.Length);
        int written = 0;
        bool ok = WritePrinter(hPrinter, p, bytes.Length, out written);
        int we = Marshal.GetLastWin32Error();
        Marshal.FreeCoTaskMem(p);
        EndPagePrinter(hPrinter);
        EndDocPrinter(hPrinter);
        ClosePrinter(hPrinter);
        if (!ok) {
            return "WritePrinter failed err=" + we;
        }
        return "OK written=" + written;
    }
}
"@

try {
    Add-Type -TypeDefinition $rawType -Language CSharpVersion3 -ErrorAction Stop
} catch {
    try {
        Add-Type -TypeDefinition $rawType -ErrorAction Stop
    } catch {
        Write-Log ("No se pudo cargar RawPrinterHelper: " + $_.Exception.Message)
    }
}

function ConvertFrom-JsonLegacy([string]$json) {
    if ($null -eq $json) { throw "Respuesta API vacia (null)" }
    $trim = $json.Trim()
    if ($trim -eq "" -or $trim -eq "." -or $trim.StartsWith("<")) {
        throw ("Respuesta API no-JSON (len=" + $json.Length + "): " + $trim.Substring(0, [Math]::Min(80, $trim.Length)))
    }
    $asm = [System.Reflection.Assembly]::LoadWithPartialName("System.Web.Extensions")
    if (-not $asm) {
        throw "No se pudo cargar System.Web.Extensions (JSON)."
    }
    $ser = New-Object System.Web.Script.Serialization.JavaScriptSerializer
    $ser.MaxJsonLength = 67108864
    return $ser.DeserializeObject($json)
}

function Invoke-PrintApiLegacy {
    param(
        [string]$Url,
        [string]$Method = "GET",
        [string]$Body = $null
    )
    # Timeout corto: el PHP -S es single-thread; un claim largo cuelga la web.
    $timeoutMs = 5000
    $req = [System.Net.HttpWebRequest]::Create($Url)
    $req.Method = $Method
    $req.Timeout = $timeoutMs
    $req.ReadWriteTimeout = $timeoutMs
    $req.AutomaticDecompression = [System.Net.DecompressionMethods]::GZip -bor [System.Net.DecompressionMethods]::Deflate
    $req.Headers.Add("X-Api-Key", $script:ApiKey)
    if ($Method -eq "POST") {
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($(if ($null -eq $Body) { "" } else { $Body }))
        $req.ContentType = "application/json; charset=utf-8"
        $req.ContentLength = $bytes.Length
        $stream = $req.GetRequestStream()
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Close()
    }
    $resp = $req.GetResponse()
    try {
        $sr = New-Object System.IO.StreamReader($resp.GetResponseStream(), [System.Text.Encoding]::UTF8)
        try {
            $raw = $sr.ReadToEnd()
        } finally {
            $sr.Close()
        }
    } finally {
        $resp.Close()
    }
    return (ConvertFrom-JsonLegacy $raw)
}

function Get-DictValue($obj, $key) {
    if ($null -eq $obj) { return $null }
    try { return $obj.get_Item($key) } catch {}
    try { return $obj[$key] } catch {}
    return $null
}

function Show-LocalPrinters {
    Write-Log "Impresoras de print_migrate.cfg en esta PC (nombre exacto):"
    $accept = @()
    if ($script:AcceptPrinters -ne "") {
        foreach ($x in $script:AcceptPrinters.Split(",")) {
            $t = $x.Trim()
            if ($t -ne "") { $accept += $t }
        }
    }
    if ($accept.Count -eq 0) {
        Write-Log "  (lista vacia)"
        return
    }
    foreach ($wanted in $accept) {
        if (Test-LocalPrinterExists $wanted) {
            Write-Log ("  OK  '" + $wanted + "'")
        } else {
            Write-Log ("  --  '" + $wanted + "' NO instalada (no se usara)")
        }
    }
}

function Get-PrinterPortName($p) {
    try { return ([string]$p.PortName).Trim() } catch { return "" }
}
function Get-PrinterDriverName($p) {
    try { return ([string]$p.DriverName).Trim() } catch { return "" }
}
function Test-IsRedirectedPrinter($p) {
    $name = ""
    try { $name = ([string]$p.Name) } catch {}
    $port = Get-PrinterPortName $p
    $drv = Get-PrinterDriverName $p
    if ($name -match 'redireccionado|redirected') { return $true }
    if ($port -match '^TS\d') { return $true }
    if ($drv -match 'Generic\s*/\s*Text') { return $true }
    return $false
}
function Get-PrinterPreferenceScore($p) {
    # Mayor = mejor (USB Zebra fisico gana a RDP redirigido)
    $score = 0
    $port = Get-PrinterPortName $p
    $drv = Get-PrinterDriverName $p
    if (Test-IsRedirectedPrinter $p) { return -100 }
    if ($port -match '^USB') { $score += 50 }
    if ($drv -match 'ZDesigner|Zebra') { $score += 30 }
    if ($port -match '^\d+\.\d+\.\d+\.\d+') { $score += 20 }
    return $score
}

function Find-WindowsPrinterName([string]$wanted) {
    if ([string]::IsNullOrEmpty($wanted)) { return $null }
    $wanted = $wanted.Trim()
    $candidates = @()
    try {
        $list = @(Get-Printer -ErrorAction Stop)
        foreach ($p in $list) {
            $name = ([string]$p.Name).Trim()
            if ($script:SkipUncPrinters -and ($name.IndexOf("\\") -eq 0)) { continue }
            if ([string]::Equals($name, $wanted, [System.StringComparison]::OrdinalIgnoreCase)) {
                $candidates += $p
            }
        }
    } catch {
        $wmi = @(Get-WmiObject -Class Win32_Printer -ErrorAction SilentlyContinue)
        foreach ($p in $wmi) {
            $name = ([string]$p.Name).Trim()
            if ($script:SkipUncPrinters -and ($name.IndexOf("\\") -eq 0)) { continue }
            if ([string]::Equals($name, $wanted, [System.StringComparison]::OrdinalIgnoreCase)) {
                $candidates += $p
            }
        }
    }
    if ($candidates.Count -eq 0) { return $null }
    $best = $null
    $bestScore = -99999
    foreach ($p in $candidates) {
        $s = Get-PrinterPreferenceScore $p
        if ($s -gt $bestScore) {
            $bestScore = $s
            $best = $p
        }
    }
    if ($best -eq $null) { return $null }
    if ($bestScore -lt 0) {
        Write-Log ("AVISO: '" + $wanted + "' solo aparece como redirigida/RDP (score=" + $bestScore + ")")
    }
    return ([string]$best.Name).Trim()
}

function Test-LocalPrinterExists([string]$name) {
    if ([string]::IsNullOrEmpty($name)) { return $false }
    return ($null -ne (Find-WindowsPrinterName $name))
}

function Get-TargetPrinter([string]$jobPrinter) {
    # Sin redireccion: el job debe pedir el nombre exacto instalado en Windows.
    if ([string]::IsNullOrEmpty($jobPrinter)) {
        Write-Log "Job sin impresora: se libera (no hay fallback)"
        return $null
    }
    $found = Find-WindowsPrinterName $jobPrinter
    if ($found) { return $found }
    return $null
}

function Send-ZplTcp([string]$hostAddr, [int]$port, [string]$zpl) {
    $client = $null
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $ar = $client.BeginConnect($hostAddr, $port, $null, $null)
        if (-not $ar.AsyncWaitHandle.WaitOne(3000, $false)) {
            try { $client.Close() } catch {}
            return ("TCP failed {0}:{1} err=connect timeout" -f $hostAddr, $port)
        }
        $client.EndConnect($ar)
        $client.ReceiveTimeout = 3000
        $client.SendTimeout = 3000
        $stream = $client.GetStream()
        $bytes = [System.Text.Encoding]::ASCII.GetBytes($zpl)
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Flush()
        Start-Sleep -Milliseconds 100
        $client.Close()
        return ("OK tcp={0}:{1} written={2}" -f $hostAddr, $port, $bytes.Length)
    } catch {
        if ($client) { try { $client.Close() } catch {} }
        return ("TCP failed {0}:{1} err={2}" -f $hostAddr, $port, $_.Exception.Message)
    }
}

function Get-PrinterTcpEndpoint([string]$printerName) {
    try {
        $pr = Get-Printer -Name $printerName -ErrorAction Stop
        $portName = [string]$pr.PortName
        $ports = @(Get-PrinterPort -ErrorAction SilentlyContinue)
        foreach ($pt in $ports) {
            if ([string]$pt.Name -ne $portName) { continue }
            $hostAddr = $null
            $portNum = 0
            try { $hostAddr = [string]$pt.PrinterHostAddress } catch {}
            try { $portNum = [int]$pt.PortNumber } catch {}
            if ([string]::IsNullOrEmpty($hostAddr)) {
                try { $hostAddr = [string]$pt.PrinterHostIP } catch {}
            }
            if (-not [string]::IsNullOrEmpty($hostAddr) -and $portNum -gt 0) {
                return @{ Host = $hostAddr; Port = $portNum; PortName = $portName }
            }
        }
    } catch {}
    return $null
}

function Send-ZplRaw([string]$printerName, [string]$zpl) {
    # Virtual ZPL Printer (Daniel Porrey): mejor TCP al puerto 9100/53819 que RAW via Generic Text
    $ep = Get-PrinterTcpEndpoint $printerName
    if ($ep -ne $null) {
        $tcpResult = Send-ZplTcp $ep.Host $ep.Port $zpl
        if ($tcpResult -like "OK*") {
            return $tcpResult
        }
        Write-Log ("TCP fallo, intento RAW spooler: " + $tcpResult)
    }
    return [RawPrinterHelper]::SendString($printerName, $zpl)
}

function Get-ApiBaseUrl {
    $u = $script:ApiUrl
    if ($u.IndexOf("?") -ge 0) {
        return $u.Substring(0, $u.IndexOf("?"))
    }
    return $u
}

function Process-LocalQueue {
    $dir = $script:LocalQueueDir
    $doneDir = Join-Path $dir "impreso"
    $errDir = Join-Path $dir "error"
    if (-not (Test-Path $doneDir)) { New-Item -ItemType Directory -Path $doneDir | Out-Null }
    if (-not (Test-Path $errDir)) { New-Item -ItemType Directory -Path $errDir | Out-Null }
    $files = @(Get-ChildItem -Path $dir -Filter "*.zpl" -File)
    foreach ($f in $files) {
        $zpl = [System.IO.File]::ReadAllText($f.FullName)
        $winName = Get-TargetPrinter $script:PrinterDefault
        if (-not $winName) {
            Write-Log ("Sin impresora local para archivo " + $f.Name)
            continue
        }
        Write-Log ("Cola local archivo={0} impresora={1}" -f $f.Name, $winName)
        $result = Send-ZplRaw $winName $zpl
        Write-Log ("RAW result: " + $result)
        $stamp = Get-Date -Format "yyyyMMdd_HHmmss"
        if ($result -notlike "OK*") {
            Move-Item -Force $f.FullName (Join-Path $errDir ($stamp + "_" + $f.Name))
        } else {
            Move-Item -Force $f.FullName (Join-Path $doneDir ($stamp + "_" + $f.Name))
        }
    }
}

function Post-JobEstado([string]$base, $jobId, [string]$estado) {
    $postUrl = $base + "?key=" + [System.Uri]::EscapeDataString($script:ApiKey)
    $body = "{`"id`":$jobId,`"estado`":`"$estado`",`"worker`":`"" + $script:WorkerId.Replace('"','') + "`"}"
    $attempt = 0
    while ($attempt -lt 3) {
        $attempt++
        try {
            $mark = Invoke-PrintApiLegacy -Url $postUrl -Method "POST" -Body $body
            if ([bool](Get-DictValue $mark "ok")) {
                return $true
            }
            Write-Log ("POST estado=$estado id=$jobId intento=$attempt ok=false")
        } catch {
            Write-Log ("POST estado=$estado id=$jobId intento=$attempt err=" + $_.Exception.Message)
        }
        Start-Sleep -Milliseconds 400
    }
    return $false
}

function Get-PendingAckPath {
    return (Join-Path $PSScriptRoot "pending_ack.txt")
}

function Save-PendingAck($jobId) {
    try {
        $path = Get-PendingAckPath
        $jobId = [string]$jobId
        $lines = @()
        if (Test-Path $path) {
            $lines = @(Get-Content $path -ErrorAction SilentlyContinue | Where-Object { $_ -and $_.Trim() -ne "" -and $_.Trim() -ne $jobId })
        }
        $lines += $jobId
        Set-Content -Path $path -Value $lines -Encoding ASCII
    } catch {}
}

function Remove-PendingAck($jobId) {
    try {
        $path = Get-PendingAckPath
        if (-not (Test-Path $path)) { return }
        $jobId = [string]$jobId
        $lines = @(Get-Content $path -ErrorAction SilentlyContinue | Where-Object { $_ -and $_.Trim() -ne "" -and $_.Trim() -ne $jobId })
        if ($lines.Count -eq 0) {
            Remove-Item -Force $path -ErrorAction SilentlyContinue
        } else {
            Set-Content -Path $path -Value $lines -Encoding ASCII
        }
    } catch {}
}

function Flush-PendingAcks([string]$base) {
    $path = Get-PendingAckPath
    if (-not (Test-Path $path)) { return }
    $ids = @(Get-Content $path -ErrorAction SilentlyContinue | Where-Object { $_ -match '^\d+$' })
    foreach ($id in $ids) {
        if (Post-JobEstado $base $id "impreso") {
            Write-Log ("Ack pendiente OK id={0}" -f $id)
            Remove-PendingAck $id
        } else {
            Write-Log ("Ack pendiente aún falla id={0}" -f $id)
        }
    }
}

function Process-RemoteApi {
    $base = Get-ApiBaseUrl
    Flush-PendingAcks $base
    $pendingUrl = $base + "?key=" + [System.Uri]::EscapeDataString($script:ApiKey)
    $pendingUrl = $pendingUrl + "&worker=" + [System.Uri]::EscapeDataString($script:WorkerId)
    $pendingUrl = $pendingUrl + "&claim=1"
    if ($script:PrinterFilter -ne "") {
        $pendingUrl = $pendingUrl + "&printer=" + [System.Uri]::EscapeDataString($script:PrinterFilter)
    } elseif ($script:AcceptPrinters -ne "") {
        $pendingUrl = $pendingUrl + "&printers=" + [System.Uri]::EscapeDataString($script:AcceptPrinters)
    }
    try {
        $data = Invoke-PrintApiLegacy -Url $pendingUrl -Method "GET"
    } catch {
        Write-Log ("API claim fallo: " + $_.Exception.Message)
        return
    }
    $ok = [bool](Get-DictValue $data "ok")
    $count = [int](Get-DictValue $data "count")
    if (-not $ok) {
        Write-Log "API ok=false"
        return
    }
    if ($count -le 0) { return }
    $jobs = Get-DictValue $data "jobs"
    foreach ($job in $jobs) {
        $jobId = Get-DictValue $job "id"
        $itemId = Get-DictValue $job "itemid"
        $etiq = [string](Get-DictValue $job "etiqueta")
        $printer = [string](Get-DictValue $job "printer")
        $zpl = [string](Get-DictValue $job "zpl")
        # Find-WindowsPrinterName ya valida (WMI en Win7). No exigir Get-Printer.
        $winName = Get-TargetPrinter $printer
        if (-not $winName) {
            Write-Log ("Liberar id={0} etiq={1}: impresora '{2}' no esta en esta PC" -f $jobId, $etiq, $printer)
            [void](Post-JobEstado $base $jobId "liberar")
            continue
        }
        Write-Log ("Tomado id={0} etiq={1} item={2} jsonPrinter={3} winPrinter={4}" -f $jobId, $etiq, $itemId, $printer, $winName)
        try {
            [System.IO.File]::WriteAllText($script:TempZpl, $zpl, [System.Text.Encoding]::ASCII)
        } catch {}
        $result = "ERROR"
        try {
            $result = Send-ZplRaw $winName $zpl
        } catch {
            $result = ("EXC " + $_.Exception.Message)
        }
        Write-Log ("RAW result: " + $result)
        if ($result -notlike "OK*") {
            Write-Log "ERROR de impresora RAW, se marca error"
            [void](Post-JobEstado $base $jobId "error")
            continue
        }
        if (Post-JobEstado $base $jobId "impreso") {
            Write-Log ("Marcado impreso id={0}" -f $jobId)
            Remove-PendingAck $jobId
        } else {
            Write-Log ("No se pudo marcar impreso id={0} — queda en pending_ack (no reimprime)" -f $jobId)
            Save-PendingAck $jobId
        }
    }
}

if ($LocalQueueDir -ne "") {
    Write-Log "Modo LOCAL (sin servidor): cola de archivos"
    Write-Log ("Carpeta cola: " + $LocalQueueDir)
} elseif ($ApiUrl -ne "") {
    Write-Log "Cola ZPL (migracion hibrida): $ApiUrl"
} else {
    Write-Log "ERROR: configura LocalQueueDir (prueba) o ApiUrl (API)."
    exit 1
}
Write-Log ("WorkerId: " + $WorkerId)
# Dejar AcceptPrinters solo con nombres instalados (sin fantasmas / sin redirigir).
if ($AcceptPrinters -ne "") {
    $alive = @()
    $missing = @()
    foreach ($x in $AcceptPrinters.Split(",")) {
        $t = ([string]$x).Trim()
        if ($t -eq "") { continue }
        if (Test-LocalPrinterExists $t) {
            $alive += $t
        } else {
            $missing += $t
            Write-Log ("Ignorada (no instalada con ese nombre exacto): " + $t)
        }
    }
    $AcceptPrinters = [string]::Join(",", $alive)
    if ($missing.Count -gt 0) {
        Write-Log "AVISO: renombre en Windows (Dispositivos e impresoras) al nombre exacto del cfg."
        Write-Log ("Faltan: " + [string]::Join(", ", $missing))
    }
}
if ($AcceptPrinters -ne "") {
    Write-Log "Acepta impresoras (exactas): $AcceptPrinters"
} else {
    Write-Log "ERROR: ninguna impresora de print_migrate.cfg esta instalada con el nombre exacto."
    Write-Log "Sin eso el worker NO tomara jobs de cola (quedan en pendiente)."
    Write-Log "Ejecute print_service_21\diagnostico.bat y compare nombres."
    exit 1
}
if ($ShowPrinterList) {
    try { Show-LocalPrinters } catch { Write-Log ("Show-LocalPrinters: " + $_.Exception.Message) }
}

if ($LocalQueueDir -ne "" -and -not (Test-Path $LocalQueueDir)) {
    New-Item -ItemType Directory -Path $LocalQueueDir | Out-Null
}

while ($true) {
    try {
        if ($LocalQueueDir -ne "") {
            Process-LocalQueue
        } else {
            Process-RemoteApi
        }
    } catch {
        Write-Log ("Error: {0}" -f $_.Exception.Message)
    }
    Start-Sleep -Seconds $SleepSec
}
