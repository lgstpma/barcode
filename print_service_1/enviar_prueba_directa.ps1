# Envia el ZPL real de etiqueta 1 DIRECTO a GK420t_chica virtual.
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $here "zpl_etiqueta1.ps1")
$scriptDir = $here
$cfg = Join-Path $here "config.local.ps1"
if (Test-Path $cfg) { . $cfg }
$PrinterName = "GK420t_chica"
if ($PrinterForce -ne "") { $PrinterName = $PrinterForce }

$zpl = Build-ZplEtiqueta1 -Codigo "025127" -Descrip "Galletas de mantequilla con chispas de chocolate" -Precio 8.5 -Cant 1 -Expir 15 -FechaManufact (Get-Date)

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
        di.pDocName = "etiqueta1-prueba";
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

Write-Host "Impresora: $PrinterName"
$result = [RawPrinterHelper]::SendString($PrinterName, $zpl)
Write-Host $result
if ($result -notlike "OK*") { exit 1 }
