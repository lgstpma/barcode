# Servicio local cola ZPL UNIFICADA (PowerShell 2.0+)
# Formatos: 1, 9, 10, 13, 14, 21 via api_print_21.php
#
# Multi-usuario / multi-PC:
#   - La API hace CLAIM atómico (estado 0→3); dos workers no toman el mismo job
#   - Si esta PC no tiene la impresora del job, lo LIBERA para otro worker
#   - Claims viejos (>5 min) vuelven a pendientes solos
#
# Migracion hibrida: print_migrate.cfg (impresoras del servicio NUEVO).
# Prueba: config.local.ps1 + probar.bat
# Produccion: install_tarea.bat (copia print_migrate.cfg; no pisa config.local.ps1)

# API de ESTA copia (start.bat en 8080). Alternativa IIS/XAMPP:
# $ApiUrl = "http://winsrvr2012xamp/barcode4.0/api_print_21.php"
$ApiUrl          = "http://127.0.0.1:8080/api_print_21.php"
$LocalQueueDir   = ""
$PrinterDefault  = "GK420t_chica"
$PrinterForce    = ""
$PrinterFilter   = ""
# Fallback si no hay print_migrate.cfg. El cfg (y luego config.local.ps1) lo pisan.
$AcceptPrinters  = "GK420t_chica"
$PrinterAliases  = @{}
$SkipUncPrinters = $true
$ShowPrinterList = $true
$ApiKey          = "barcode21"
$SleepSec        = 3
$WorkerId        = $env:COMPUTERNAME + "-zpl"
$TempZpl         = Join-Path $env:TEMP ("etiqueta_zpl_" + $PID + ".zpl")

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$migrateCfg = Join-Path $scriptDir "print_migrate.cfg"
if (-not (Test-Path $migrateCfg)) {
    $parentDir = Split-Path $scriptDir -Parent
    if ($parentDir) {
        $migrateCfg = Join-Path $parentDir "print_migrate.cfg"
    }
}
if (Test-Path $migrateCfg) {
    $migNames = @()
    foreach ($line in (Get-Content $migrateCfg)) {
        $t = ([string]$line).Trim()
        if ($t -eq "") { continue }
        if ($t.StartsWith("#")) { continue }
        $migNames += $t
    }
    if ($migNames.Count -gt 0) {
        $AcceptPrinters = [string]::Join(",", $migNames)
    }
}
$localCfg = Join-Path $scriptDir "config.local.ps1"
if (Test-Path $localCfg) {
    . $localCfg
}
if ([string]::IsNullOrEmpty($WorkerId)) {
    $WorkerId = $env:COMPUTERNAME + "-zpl"
}
if ($null -eq $PrinterAliases) {
    $PrinterAliases = @{}
}

$LogFile = Join-Path $scriptDir "print_service.log"

function Write-Log($msg) {
    $line = ("{0} {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $msg)
    Write-Host $line
    try { Add-Content -Path $script:LogFile -Value $line -ErrorAction SilentlyContinue } catch {}
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
    $wc = New-Object System.Net.WebClient
    $wc.Encoding = [System.Text.Encoding]::UTF8
    $wc.Headers["X-Api-Key"] = $script:ApiKey
    try {
        if ($Method -eq "POST") {
            $wc.Headers["Content-Type"] = "application/json; charset=utf-8"
            $raw = $wc.UploadString($Url, "POST", $Body)
        } else {
            $raw = $wc.DownloadString($Url)
        }
    } finally {
        $wc.Dispose()
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
    Write-Log "Impresoras instaladas en esta PC:"
    $list = Get-WmiObject -Class Win32_Printer
    foreach ($p in $list) {
        Write-Log ("  Name='" + $p.Name + "' Share='" + $p.ShareName + "' Port='" + $p.PortName + "'")
    }
}

function Find-WindowsPrinterName([string]$hint) {
    if ([string]::IsNullOrEmpty($hint)) {
        $hint = $script:PrinterDefault
    }
    $hint = $hint.Trim()
    # Preferir Get-Printer (mas fiable que WMI en bucle)
    try {
        $list = @(Get-Printer -ErrorAction Stop)
    } catch {
        $list = @()
        $wmi = @(Get-WmiObject -Class Win32_Printer -ErrorAction SilentlyContinue)
        foreach ($p in $wmi) {
            $list += (New-Object PSObject -Property @{ Name = $p.Name; ShareName = $p.ShareName })
        }
    }
    $usable = @()
    foreach ($p in $list) {
        $name = [string]$p.Name
        $isUnc = ($name.IndexOf("\\") -eq 0)
        if ($script:SkipUncPrinters -and $isUnc) { continue }
        $usable += $p
    }
    foreach ($p in $usable) {
        if ([string]$p.Name -eq $hint) { return [string]$p.Name }
    }
    foreach ($p in $usable) {
        if ($p.ShareName -and ([string]$p.ShareName -eq $hint)) { return [string]$p.Name }
    }
    foreach ($p in $usable) {
        $n = [string]$p.Name
        if ($n.ToLower().IndexOf($hint.ToLower()) -ge 0) { return $n }
    }
    return $null
}

function Test-LocalPrinterExists([string]$name) {
    if ([string]::IsNullOrEmpty($name)) { return $false }
    # Get-Printer no existe en Win7 / PS2; usar WMI como respaldo.
    try {
        $null = Get-Printer -Name $name -ErrorAction Stop
        return $true
    } catch {}
    try {
        $p = Get-WmiObject -Class Win32_Printer -Filter ("Name='" + $name.Replace("'", "''") + "'") -ErrorAction SilentlyContinue
        if ($p) { return $true }
    } catch {}
    return $false
}

function Get-TargetPrinter([string]$jobPrinter) {
    if ($script:PrinterForce -ne "") { return $script:PrinterForce }
    if ($jobPrinter -ne "" -and $script:PrinterAliases -and $script:PrinterAliases.ContainsKey($jobPrinter)) {
        $jobPrinter = [string]$script:PrinterAliases[$jobPrinter]
        Write-Log ("Alias impresora -> " + $jobPrinter)
    }
    $found = Find-WindowsPrinterName $jobPrinter
    if ($found) { return $found }
    if ($jobPrinter -eq "" -or $jobPrinter -eq $null) {
        return (Find-WindowsPrinterName $script:PrinterDefault)
    }
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

function Process-RemoteApi {
    $base = Get-ApiBaseUrl
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
        } else {
            Write-Log ("No se pudo marcar impreso id={0} (reintento en siguiente ciclo)" -f $jobId)
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
Write-Log "Impresora por defecto: $PrinterDefault"
if ($PrinterForce -ne "") { Write-Log "Forzar impresora local: $PrinterForce" }
if ($AcceptPrinters -ne "") { Write-Log "Acepta impresoras: $AcceptPrinters" }
if (Test-Path $localCfg) { Write-Log "Usando config.local.ps1" }
if ($ShowPrinterList) {
    Show-LocalPrinters
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
