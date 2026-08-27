# Etiqueta 10: cualquier codigo. Lee lbls (impresora + tamano) y el BMP de printserver.
# No llama al servidor de produccion. No uses install_tarea.bat en esta PC.
param(
    [Parameter(Position = 0)]
    [string]$Codigo = "",
    [int]$Cant = 1
)

$here = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $here "zpl_etiqueta10.ps1")
$scriptDir = $here
$PrinterMap = @{}
$PrinterForce = ""
$PrinterDefault = "VirtualZPLPrinter_Sistemas"
$SkipUncPrinters = $true
$cfg = Join-Path $here "config.local.ps1"
if (Test-Path $cfg) { . $cfg }

if ([string]::IsNullOrEmpty($Codigo)) {
    Write-Host "Uso: enviar_prueba_directa.bat 026015"
    throw "Falta codigo de barras"
}

$code = Etiqueta10-PadCodigo $Codigo
$root = Split-Path -Parent $here
$images = Join-Path $root "printserver"
$indexPath = Join-Path $here "lbls_index.txt"
$lbls = Get-LblsLayout -Codigo $code -IndexPath $indexPath

$printerLbls = ""
$labelW = 0; $labelH = 0
$dx = 0; $dy = 0; $dw = 0; $dh = 0
$bx = -1; $by = -1; $bw = 0; $bh = 0
if ($lbls) {
    $printerLbls = [string]$lbls["printer"]
    $labelW = Etiqueta10-ParseInt $lbls["width"]
    $labelH = Etiqueta10-ParseInt $lbls["height"]
    $dx = Etiqueta10-ParseInt $lbls["design_x"]
    $dy = Etiqueta10-ParseInt $lbls["design_y"]
    $dw = Etiqueta10-ParseInt $lbls["design_width"]
    $dh = Etiqueta10-ParseInt $lbls["design_height"]
    $bx = Etiqueta10-ParseInt $lbls["bar_x"]
    $by = Etiqueta10-ParseInt $lbls["bar_y"]
    $bw = Etiqueta10-ParseInt $lbls["bar_width"]
    $bh = Etiqueta10-ParseInt $lbls["bar_height"]
}

$wanted = $printerLbls
if ([string]::IsNullOrEmpty($wanted)) { $wanted = $PrinterDefault }
if ($PrinterForce -ne "") {
    $PrinterName = $PrinterForce
} else {
    $PrinterName = Resolve-Etiqueta10Printer -Wanted $wanted -PrinterMap $PrinterMap -Fallback $PrinterDefault -SkipUnc $SkipUncPrinters
}

$built = Build-ZplEtiqueta10 -Codigo $code -Cant $Cant -ImagesDir $images `
    -Printer $printerLbls `
    -LabelW $labelW -LabelH $labelH `
    -DesignX $dx -DesignY $dy -DesignW $dw -DesignH $dh `
    -BarX $bx -BarY $by -BarW $bw -BarH $bh

$qdir = Join-Path $here "queue"
if (-not (Test-Path $qdir)) { New-Item -ItemType Directory -Path $qdir | Out-Null }
$outZpl = Join-Path $qdir ("prueba_" + $code + ".zpl")
[System.IO.File]::WriteAllText($outZpl, $built.Zpl, [System.Text.Encoding]::ASCII)

$inchW = [Math]::Round($built.Pw / 203.0, 2)
$inchH = [Math]::Round($built.Ll / 203.0, 2)

$rawType = @"
using System;
using System.Runtime.InteropServices;
public class RawPrinterHelper10 {
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
        di.pDocName = "etiqueta10-prueba";
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
        if (!ok) { return "WritePrinter failed err=" + we; }
        return "OK written=" + written;
    }
}
"@
try { Add-Type -TypeDefinition $rawType -Language CSharpVersion3 -ErrorAction Stop } catch { Add-Type -TypeDefinition $rawType }

Write-Host ("Codigo:          {0}" -f $code)
if ($lbls) {
    Write-Host ("lbls.printer:    {0}" -f $printerLbls)
} else {
    Write-Host "lbls:            (sin ficha: se usa el tamano del BMP)"
}
Write-Host ("Imagen:          {0} ({1}x{2} px)" -f $built.Path, $built.ImgW, $built.ImgH)
Write-Host ("Etiqueta ZPL:    {0} x {1} dots  ({2} x {3} pulgadas)" -f $built.Pw, $built.Ll, $inchW, $inchH)
Write-Host ("Enviar a:        {0}" -f $PrinterName)
if ($printerLbls -ne "" -and $PrinterName -ne $printerLbls) {
    Write-Host ("Nota: en esta PC no esta '{0}', se usa '{1}'." -f $printerLbls, $PrinterName)
}
Write-Host ("Virtual ZPL:     {0} pulg. ancho x {1} pulg. alto, 203 dpi, sin rotar" -f $inchW, $inchH)
Write-Host ("Archivo:         {0}" -f $outZpl)

$result = [RawPrinterHelper10]::SendString($PrinterName, $built.Zpl)
Write-Host $result
if ($result -notlike "OK*") { exit 1 }
