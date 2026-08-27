# Servicio local etiqueta 1 (PowerShell 2.0+)
# Envia ZPL RAW a la cola de Windows (no copy a \\localhost\share).

$ApiUrl         = ""
$LocalQueueDir  = ""
$PrinterDefault = "GK420t_chica"
$PrinterForce   = ""
$PrinterFilter  = ""
$SkipUncPrinters = $true
$ShowPrinterList = $true
$ApiKey         = "barcode1"
$SleepSec       = 3
$TempZpl        = Join-Path $env:TEMP "etiqueta1_job.zpl"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$localCfg = Join-Path $scriptDir "config.local.ps1"
if (Test-Path $localCfg) {
    . $localCfg
}

function Write-Log($msg) {
    Write-Host ("{0} {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $msg)
}

# Impresion RAW via winspool (Zebra necesita RAW, no el driver grafico)
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
        di.pDocName = "etiqueta1";
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
    $list = @(Get-WmiObject -Class Win32_Printer)
    $usable = @()
    foreach ($p in $list) {
        $isUnc = $false
        if ($p.Name -and ($p.Name.IndexOf("\\") -eq 0)) { $isUnc = $true }
        if ($script:SkipUncPrinters -and $isUnc) { continue }
        $usable += $p
    }
    foreach ($p in $usable) {
        if ($p.Name -eq $hint) { return $p.Name }
    }
    foreach ($p in $usable) {
        if ($p.ShareName -and ($p.ShareName -eq $hint)) { return $p.Name }
    }
    foreach ($p in $usable) {
        if ($p.Name -and ($p.Name.ToLower().IndexOf($hint.ToLower()) -ge 0)) { return $p.Name }
        if ($p.ShareName -and ($p.ShareName.ToLower().IndexOf($hint.ToLower()) -ge 0)) { return $p.Name }
    }
    return $hint
}

function Send-ZplRaw([string]$printerName, [string]$zpl) {
    return [RawPrinterHelper]::SendString($printerName, $zpl)
}

function Get-TargetPrinter([string]$jobPrinter) {
    if ($script:PrinterForce -ne "") { return $script:PrinterForce }
    return (Find-WindowsPrinterName $jobPrinter)
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

function Process-RemoteApi {
    $pendingUrl = $script:ApiUrl + "?key=" + $script:ApiKey
    if ($script:PrinterFilter -ne "") {
        $pendingUrl = $pendingUrl + "&printer=" + [System.Uri]::EscapeDataString($script:PrinterFilter)
    }
    $data = Invoke-PrintApiLegacy -Url $pendingUrl -Method "GET"
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
        $printer = [string](Get-DictValue $job "printer")
        $zpl = [string](Get-DictValue $job "zpl")
        $winName = Get-TargetPrinter $printer
        Write-Log ("Pendiente id={0} item={1} jsonPrinter={2} winPrinter={3}" -f $jobId, $itemId, $printer, $winName)
        [System.IO.File]::WriteAllText($script:TempZpl, $zpl, [System.Text.Encoding]::ASCII)
        $result = Send-ZplRaw $winName $zpl
        Write-Log ("RAW result: " + $result)
        if ($result -notlike "OK*") {
            Write-Log "ERROR de impresora RAW, no se marca como impreso"
            $errBody = "{`"id`":$jobId,`"estado`":`"error`"}"
            try { Invoke-PrintApiLegacy -Url $pendingUrl -Method "POST" -Body $errBody | Out-Null } catch {}
            continue
        }
        $okBody = "{`"id`":$jobId,`"estado`":`"impreso`"}"
        $mark = Invoke-PrintApiLegacy -Url $pendingUrl -Method "POST" -Body $okBody
        $markOk = [bool](Get-DictValue $mark "ok")
        if ($markOk) {
            Write-Log ("Marcado impreso id={0}" -f $jobId)
        } else {
            Write-Log ("No se pudo marcar impreso id={0}" -f $jobId)
        }
    }
}

if ($LocalQueueDir -ne "") {
    Write-Log "Modo LOCAL (sin servidor): cola de archivos"
    Write-Log ("Carpeta cola: " + $LocalQueueDir)
} elseif ($ApiUrl -ne "") {
    Write-Log "API etiqueta 1: $ApiUrl"
} else {
    Write-Log "ERROR: configura LocalQueueDir (prueba) o ApiUrl (produccion)."
    exit 1
}
Write-Log "Impresora por defecto: $PrinterDefault"
if ($PrinterForce -ne "") { Write-Log "Forzar impresora local: $PrinterForce" }
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
