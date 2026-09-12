# Configura Zebras locales: sondea tamaño de etiqueta, imprime prueba y renombra
# a los nombres BARCODE (GK420t_chica, GK420t_grande, GK420t_3x1.25, GK420t_3x2).
# PowerShell 2.0+ / Windows 7+.

$ErrorActionPreference = "Continue"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = Split-Path $scriptDir -Parent

$Profiles = @(
    @{ Name = "GK420t_chica";   Formats = "1,9,21,GTIN"; W = 203; L = 102; Tol = 50; Alt = @(@{W=494;L=183},@{W=183;L=495},@{W=183;L=102}) },
    @{ Name = "GK420t_grande";  Formats = "5";           W = 479; L = 319; Tol = 80; Alt = @() },
    @{ Name = "GK420t_3x1.25";  Formats = "10/14 3x1.25"; W = 609; L = 254; Tol = 50; Alt = @() },
    @{ Name = "GK420t_3x2";     Formats = "10/14 3x2";   W = 609; L = 406; Tol = 50; Alt = @() }
)

$rawType = @"
using System;
using System.Runtime.InteropServices;
public class ZebraRaw {
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
    public static string Send(string printerName, string zpl) {
        IntPtr h = IntPtr.Zero;
        if (!OpenPrinter(printerName, out h, IntPtr.Zero)) return "OpenPrinter failed";
        var di = new DOCINFOA();
        di.pDocName = "BARCODE-setup";
        di.pDataType = "RAW";
        if (!StartDocPrinter(h, 1, di)) { ClosePrinter(h); return "StartDoc failed"; }
        StartPagePrinter(h);
        IntPtr p = Marshal.StringToCoTaskMemAnsi(zpl);
        int written = 0;
        bool ok = WritePrinter(h, p, zpl.Length, out written);
        Marshal.FreeCoTaskMem(p);
        EndPagePrinter(h);
        EndDocPrinter(h);
        ClosePrinter(h);
        return ok ? ("OK written=" + written) : "WritePrinter failed";
    }
}
"@
try { Add-Type -TypeDefinition $rawType -Language CSharp -ErrorAction Stop } catch {}

function Get-LocalPrinters {
    $list = @()
    try {
        $wmi = @(Get-WmiObject -Class Win32_Printer -ErrorAction Stop)
        foreach ($p in $wmi) {
            $n = [string]$p.Name
            if ($n.StartsWith("\\")) { continue }
            $list += @{
                Name = $n
                Port = [string]$p.PortName
                Driver = [string]$p.DriverName
                Offline = [bool]$p.WorkOffline
            }
        }
    } catch {}
    return $list
}

function Test-IsZebraLike($p) {
    $blob = ($p.Name + " " + $p.Driver + " " + $p.Port).ToLower()
    if ($blob -match "zebra|zdesigner|gk420|zd4|zt4|virtualzpl|zpl") { return $true }
    if ($p.Name -match "^GK420t_") { return $true }
    return $false
}

function Get-TcpEndpoint([string]$printerName, [string]$portName) {
    # USB001 etc: no TCP
    if ($portName -match "^(USB|LPT|FILE|PORTPROMPT|SHR)") { return $null }
    if ($portName -match "(\d+\.\d+\.\d+\.\d+)") {
        return @{ Host = $Matches[1]; Port = 9100 }
    }
    try {
        $ports = @(Get-WmiObject -Query "SELECT * FROM Win32_TCPIPPrinterPort" -ErrorAction SilentlyContinue)
        foreach ($pt in $ports) {
            if ([string]$pt.Name -ne $portName) { continue }
            $h = [string]$pt.HostAddress
            if (-not $h) { try { $h = [string]$pt.PrinterHostAddress } catch {} }
            $pn = 9100
            try { if ($pt.PortNumber) { $pn = [int]$pt.PortNumber } } catch {}
            if ($h) { return @{ Host = $h; Port = $pn } }
        }
    } catch {}
    return $null
}

function Invoke-ZebraSgd([string]$hostAddr, [int]$port, [string]$cmd) {
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $client.ReceiveTimeout = 2500
        $client.SendTimeout = 2500
        $client.Connect($hostAddr, $port)
        $stream = $client.GetStream()
        $payload = $cmd + "`r`n"
        $bytes = [System.Text.Encoding]::ASCII.GetBytes($payload)
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Flush()
        Start-Sleep -Milliseconds 400
        $buf = New-Object byte[] 4096
        $read = 0
        if ($stream.DataAvailable) {
            $read = $stream.Read($buf, 0, $buf.Length)
        } else {
            Start-Sleep -Milliseconds 600
            if ($stream.DataAvailable) { $read = $stream.Read($buf, 0, $buf.Length) }
        }
        $client.Close()
        if ($read -le 0) { return "" }
        return [System.Text.Encoding]::ASCII.GetString($buf, 0, $read)
    } catch {
        return ""
    }
}

function Get-MediaSize($ep) {
    if ($ep -eq $null) { return $null }
    $wRaw = Invoke-ZebraSgd $ep.Host $ep.Port '! U1 getvar "ezpl.print_width"'
    $lRaw = Invoke-ZebraSgd $ep.Host $ep.Port '! U1 getvar "zpl.label_length"'
    $w = 0; $l = 0
    if ($wRaw -match "(\d{2,4})") { $w = [int]$Matches[1] }
    if ($lRaw -match "(\d{2,4})") { $l = [int]$Matches[1] }
    # a veces viene en mm*10 o inches; si es muy chico, ignorar
    if ($w -lt 50 -or $l -lt 40) { return $null }
    return @{ W = $w; L = $l; RawW = $wRaw; RawL = $lRaw }
}

function Match-Profile($size) {
    if ($size -eq $null) { return $null }
    $w = [int]$size.W; $l = [int]$size.L
    foreach ($pr in $Profiles) {
        $dw = [Math]::Abs($w - [int]$pr.W)
        $dl = [Math]::Abs($l - [int]$pr.L)
        if ($dw -le [int]$pr.Tol -and $dl -le [int]$pr.Tol) { return $pr }
        foreach ($a in $pr.Alt) {
            $dw2 = [Math]::Abs($w - [int]$a.W)
            $dl2 = [Math]::Abs($l - [int]$a.L)
            if ($dw2 -le [int]$pr.Tol -and $dl2 -le [int]$pr.Tol) { return $pr }
        }
        # tambien ancho/largo invertidos
        $dw3 = [Math]::Abs($w - [int]$pr.L)
        $dl3 = [Math]::Abs($l - [int]$pr.W)
        if ($dw3 -le [int]$pr.Tol -and $dl3 -le [int]$pr.Tol) { return $pr }
    }
    return $null
}

function Match-ProfileByName([string]$name) {
    foreach ($pr in $Profiles) {
        if ($name -eq $pr.Name) { return $pr }
        if ($name.ToLower().IndexOf($pr.Name.ToLower()) -ge 0) { return $pr }
    }
    $n = $name.ToLower()
    if ($n -match "chica|softshop.*1|mini") { return $Profiles[0] }
    if ($n -match "grande|3x2|mediana") {
        if ($n -match "3x1") { return $Profiles[2] }
        if ($n -match "3x2") { return $Profiles[3] }
        return $Profiles[1]
    }
    if ($n -match "3x1") { return $Profiles[2] }
    return $null
}

function Send-TestLabel([string]$printerName, [string]$targetName, [string]$formats, $size) {
    $sz = "?"
    if ($size -ne $null) { $sz = ("{0}x{1}dots" -f $size.W, $size.L) }
    $safeOld = $printerName
    if ($safeOld.Length -gt 40) { $safeOld = $safeOld.Substring(0, 40) }
    $zpl = "^XA^CI28^PW406^LL203"
    $zpl += "^FO20,20^A0N,28,28^FDBARCODE SETUP^FS"
    $zpl += "^FO20,55^A0N,22,22^FD" + $safeOld + "^FS"
    $zpl += "^FO20,90^A0N,24,24^FD=> " + $targetName + "^FS"
    $zpl += "^FO20,125^A0N,20,20^FD" + $formats + "^FS"
    $zpl += "^FO20,155^A0N,18,18^FD" + $sz + "^FS"
    $zpl += "^XZ"
    try {
        return [ZebraRaw]::Send($printerName, $zpl)
    } catch {
        return ("ERR " + $_.Exception.Message)
    }
}

function Rename-WinPrinter([string]$oldName, [string]$newName) {
    if ($oldName -eq $newName) { return $true }
    # si el destino ya existe, no pisar
    $exists = $false
    foreach ($p in (Get-LocalPrinters)) {
        if ($p.Name -eq $newName) { $exists = $true; break }
    }
    if ($exists) {
        Write-Host ("  Destino ya existe: {0} (no se renombra {1})" -f $newName, $oldName)
        return $false
    }
    try {
        $pr = Get-WmiObject -Query ("SELECT * FROM Win32_Printer WHERE Name='" + $oldName.Replace("'","\'") + "'")
        if ($pr) {
            $r = $pr.RenamePrinter($newName)
            if ($r.ReturnValue -eq 0) { return $true }
        }
    } catch {}
    $cmd = 'rundll32 printui.dll,PrintUIEntry /Xs /n "' + $oldName + '" Name "' + $newName + '"'
    cmd /c $cmd | Out-Null
    Start-Sleep -Milliseconds 800
    foreach ($p in (Get-LocalPrinters)) {
        if ($p.Name -eq $newName) { return $true }
    }
    return $false
}

function Ask-Profile([string]$printerName) {
    Write-Host ""
    Write-Host ("No se pudo auto-detectar media de: {0}" -f $printerName)
    Write-Host "  1 = GK420t_chica   (formatos 1,9,21,GTIN)"
    Write-Host "  2 = GK420t_grande  (formato 5)"
    Write-Host "  3 = GK420t_3x1.25  (BMP 10/14)"
    Write-Host "  4 = GK420t_3x2     (BMP 10/14)"
    Write-Host "  0 = saltar"
    $c = Read-Host "Elija formato para esta impresora"
    switch ($c) {
        "1" { return $Profiles[0] }
        "2" { return $Profiles[1] }
        "3" { return $Profiles[2] }
        "4" { return $Profiles[3] }
        default { return $null }
    }
}

Write-Host "============================================"
Write-Host " Zebras: sondeo + prueba + renombre BARCODE"
Write-Host "============================================"

$all = @(Get-LocalPrinters)
$zebras = @()
foreach ($p in $all) {
    if (Test-IsZebraLike $p) { $zebras += $p }
}

if ($zebras.Count -eq 0) {
    Write-Host "No se encontraron impresoras tipo Zebra/ZPL en esta PC."
    exit 1
}

Write-Host ("Encontradas {0} candidata(s):" -f $zebras.Count)
foreach ($z in $zebras) {
    Write-Host ("  - {0}  port={1}  driver={2}" -f $z.Name, $z.Port, $z.Driver)
}
Write-Host ""

$usedTargets = @{}
foreach ($z in $zebras) {
    Write-Host (">> {0}" -f $z.Name)
    $ep = Get-TcpEndpoint $z.Name $z.Port
    $size = $null
    if ($ep -ne $null) {
        Write-Host ("   TCP {0}:{1} - consultando media..." -f $ep.Host, $ep.Port)
        $size = Get-MediaSize $ep
        if ($size -ne $null) {
            Write-Host ("   Media detectada: {0} x {1} dots" -f $size.W, $size.L)
        } else {
            Write-Host "   Sin respuesta SGD (USB o firewall). Se usara nombre/manual."
        }
    } else {
        Write-Host "   Puerto local (USB/spooler) - sin SGD."
    }

    $profile = Match-Profile $size
    if ($profile -eq $null) {
        $profile = Match-ProfileByName $z.Name
        if ($profile -ne $null) {
            Write-Host ("   Inferido por nombre Windows -> {0}" -f $profile.Name)
        }
    } else {
        Write-Host ("   Formato inferido por media -> {0} ({1})" -f $profile.Name, $profile.Formats)
    }

    if ($profile -eq $null) {
        # imprime etiqueta generica de identificacion y pregunta
        $tmp = "IDENTIFICAR"
        $r0 = Send-TestLabel $z.Name $tmp "elija en consola" $size
        Write-Host ("   Print ID: {0}" -f $r0)
        $profile = Ask-Profile $z.Name
        if ($profile -eq $null) {
            Write-Host "   Saltada."
            continue
        }
    }

    if ($usedTargets.ContainsKey($profile.Name)) {
        Write-Host ("   AVISO: {0} ya se asigno a otra impresora. Elija otra." -f $profile.Name)
        $profile = Ask-Profile $z.Name
        if ($profile -eq $null) { continue }
        if ($usedTargets.ContainsKey($profile.Name)) {
            Write-Host "   Conflicto: se salta."
            continue
        }
    }

    $r = Send-TestLabel $z.Name $profile.Name $profile.Formats $size
    Write-Host ("   Prueba ZPL: {0}" -f $r)

    if ($z.Name -eq $profile.Name) {
        Write-Host ("   Ya tiene el nombre correcto: {0}" -f $profile.Name)
        $usedTargets[$profile.Name] = $z.Name
        continue
    }

    Write-Host ("   Renombrando '{0}' -> '{1}' ..." -f $z.Name, $profile.Name)
    if (Rename-WinPrinter $z.Name $profile.Name) {
        Write-Host "   OK renombrada."
        $usedTargets[$profile.Name] = $profile.Name
    } else {
        Write-Host "   FALLO al renombrar. Renombre manual en Dispositivos e impresoras."
        Write-Host ("   Nombre esperado: {0}" -f $profile.Name)
    }
}

Write-Host ""
Write-Host "Listo. Verifique que salio la etiqueta de prueba en cada Zebra."
Write-Host "Nombres esperados: GK420t_chica / GK420t_grande / GK420t_3x1.25 / GK420t_3x2"
exit 0
