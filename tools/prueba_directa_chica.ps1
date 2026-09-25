# Imprime dos ZPL directamente por RAW para aislar plantilla vs. cola/worker.
param(
    [string]$PrinterName = "ZDesigner GK420t",
    [switch]$RestoredLabel1Only,
    [switch]$OriginalOnly,
    [switch]$CalibrateOnly,
    [switch]$CalibrateGapOnly,
    [switch]$PrintConfigOnly,
    [switch]$SetOneByHalfAndTest,
    [switch]$CurrentLabel1Only,
    [switch]$RecoverOnly,
    [switch]$AllChicaFormats
)

$ErrorActionPreference = "Stop"

$rawType = @"
using System;
using System.Runtime.InteropServices;

public class BarcodeRawPrinter {
    [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.drv", EntryPoint="OpenPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string name, out IntPtr printer, IntPtr defaults);
    [DllImport("winspool.drv", SetLastError=true)]
    public static extern bool ClosePrinter(IntPtr printer);
    [DllImport("winspool.drv", EntryPoint="StartDocPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern bool StartDocPrinter(IntPtr printer, int level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA info);
    [DllImport("winspool.drv", SetLastError=true)]
    public static extern bool EndDocPrinter(IntPtr printer);
    [DllImport("winspool.drv", SetLastError=true)]
    public static extern bool StartPagePrinter(IntPtr printer);
    [DllImport("winspool.drv", SetLastError=true)]
    public static extern bool EndPagePrinter(IntPtr printer);
    [DllImport("winspool.drv", SetLastError=true)]
    public static extern bool WritePrinter(IntPtr printer, IntPtr bytes, int count, out int written);

    public static string Send(string printerName, string documentName, string zpl) {
        IntPtr printer = IntPtr.Zero;
        if (!OpenPrinter(printerName, out printer, IntPtr.Zero))
            return "OpenPrinter error=" + Marshal.GetLastWin32Error();

        DOCINFOA info = new DOCINFOA();
        info.pDocName = documentName;
        info.pDataType = "RAW";
        if (!StartDocPrinter(printer, 1, info)) {
            int error = Marshal.GetLastWin32Error();
            ClosePrinter(printer);
            return "StartDocPrinter error=" + error;
        }

        StartPagePrinter(printer);
        byte[] data = System.Text.Encoding.ASCII.GetBytes(zpl);
        IntPtr pointer = Marshal.AllocCoTaskMem(data.Length);
        Marshal.Copy(data, 0, pointer, data.Length);
        int written;
        bool ok = WritePrinter(printer, pointer, data.Length, out written);
        int writeError = Marshal.GetLastWin32Error();
        Marshal.FreeCoTaskMem(pointer);
        EndPagePrinter(printer);
        EndDocPrinter(printer);
        ClosePrinter(printer);
        return ok ? "OK written=" + written : "WritePrinter error=" + writeError;
    }
}
"@

try {
    Add-Type -TypeDefinition $rawType -Language CSharp -ErrorAction Stop
} catch {
    if (-not ("BarcodeRawPrinter" -as [type])) { throw }
}

if ($CalibrateOnly) {
    Write-Host "Calibrando sensor de medios en: $PrinterName"
    Write-Host "La impresora avanzara varias etiquetas..."
    $result = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE calibracion GK420t", "~JC`r`n")
    Write-Host "Calibracion enviada: $result"
    if ($result -notlike "OK*") { exit 5 }
    exit 0
}

if ($CalibrateGapOnly) {
    Write-Host "Configurando medio con separacion (web/gap) y calibrando: $PrinterName"
    Write-Host "La impresora avanzara varias etiquetas..."
    # ^MNY: medio no continuo detectado por espacio. ~JC: calibracion del sensor.
    # No se envia ^JUS, por lo que no se guarda como configuracion permanente.
    $result = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE calibracion GAP", "^XA^MNY^XZ`r`n~JC`r`n")
    Write-Host "Calibracion GAP enviada: $result"
    if ($result -notlike "OK*") { exit 7 }
    exit 0
}

if ($PrintConfigOnly) {
    Write-Host "Imprimiendo configuracion interna en: $PrinterName"
    $result = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE configuracion GK420t", "~WC`r`n")
    Write-Host "Configuracion enviada: $result"
    if ($result -notlike "OK*") { exit 8 }
    exit 0
}

if ($RecoverOnly) {
    Write-Host "Cancelando trabajos pendientes en: $PrinterName"
    $cancelResult = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE cancelar", "~JA`r`n")
    Write-Host "Cancelar: $cancelResult"
    Start-Sleep -Milliseconds 500
    Write-Host "Reiniciando impresora..."
    $resetResult = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE reiniciar", "~JR`r`n")
    Write-Host "Reinicio: $resetResult"
    if ($cancelResult -notlike "OK*" -or $resetResult -notlike "OK*") { exit 13 }
    exit 0
}

if ($AllChicaFormats) {
    $tests = @(
        @{
            Name = "FORMATO 1"
            Zpl = @"
^XA
^AD,54
^CFA,12
^LT10
^CWZ,E:LEXENDDECA.TTF
^FO180,45^BY1^BCN,40,N,N,N^FD000001^FS
^CFA,14
^FO20,20^FDPrecio:1.00^FS
^FO20,0^FDFORMATO 1^FS
^FO20,20^FD^FS
^FO20,40^FD#Lote:PR01^FS
^FO20,58^FDExp.:17/09/26^FS
^PQ1
^XZ
"@
        },
        @{
            Name = "FORMATO 9"
            Zpl = @"
^XA
^AD,54
^CFA,12
^LT10
^CWZ,E:LEXENDDECA.TTF
^FO180,45^BY1^BCN,40,N,N,N^FD000009^FS
^CFA,14
^FO20,20^FDPrecio:9.00^FS
^FO20,0^FDFORMATO 9 SIN FECHA^FS
^FO20,20^FD^FS
^PQ1
^XZ
"@
        },
        @{
            Name = "FORMATO 15"
            Zpl = @"
^XA
^AD,54
^CFA,12
^LT10
^CWZ,E:LEXENDDECA.TTF
^FO180,45^BY1^BCN,40,N,N,N^FD000015^FS
^CFA,14
^FO20,20^FDPrecio:15.00^FS
^FO20,0^FDFORMATO 15^FS
^FO20,20^FD^FS
^FO20,40^FD#Lote:PR15^FS
^FO20,58^FDExp.:17/09/26^FS
^FO20,75^FDReg.:PRUEBA^FS
^PQ1
^XZ
"@
        },
        @{
            Name = "FORMATO 21"
            Zpl = @"
^XA
^AD,54
^CFA,12
^LT10
^CWZ,E:LEXENDDECA.TTF
^CFA,20
^FO180,65^AZN,25,10^FD`$21.00^FS
^CFA,14
^FO25,5^FDFORMATO 21^FS
^FO15,25^FDPRUEBA CHICA^FS
^FO25,85^FD#Lote: PR21^FS
^FO15,65^FDExp.:17/09/26^FS
^FO10,80^FD-^FS
^PQ1
^XZ
"@
        },
        @{
            Name = "GTIN"
            Zpl = @"
^XA
^PW494
^LL183
^LH0,0
^FO121,3^BEN,40,N,N,N^FD7501234567893^FS
^FO172,36^GB150,18,18,W,0^FS
^FO172,36^A0N,16,16^FD7501234567893^FS
^FO28,62^A0N,12,12^FDFORMATO GTIN^FS
^FO28,76^A0N,12,12^FDPRUEBA CHICA^FS
^PQ1
^XZ
"@
        }
    )

    Write-Host "Impresora: $PrinterName"
    foreach ($test in $tests) {
        $result = [BarcodeRawPrinter]::Send($PrinterName, ("BARCODE " + $test.Name), $test.Zpl)
        Write-Host ($test.Name + ": " + $result)
        if ($result -notlike "OK*") { exit 14 }
        Start-Sleep -Milliseconds 900
    }
    exit 0
}

if ($SetOneByHalfAndTest) {
    Write-Host "Definiendo job 1.00 x 0.50 pulgadas (203 x 102 dots): $PrinterName"
    # PW/LL: tamaño; MNY: sensor gap; MTT: ribbon; MMT: tear-off.
    # Sin ^JUS: no guardar permanentemente en la impresora.
    $setup = "^XA^PW203^LL102^MNY^MTT^MMT^LH0,0^LT0^LS0^XZ`r`n"
    $setupResult = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE setup 1x0.5", $setup)
    Write-Host "Tamanio/modo: $setupResult"
    if ($setupResult -notlike "OK*") { exit 9 }

    Start-Sleep -Milliseconds 500
    $calResult = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE calibracion 1x0.5", "~JC`r`n")
    Write-Host "Calibracion: $calResult"
    if ($calResult -notlike "OK*") { exit 10 }

    Start-Sleep -Seconds 3
    $test = @"
^XA
^PW203
^LL102
^MNY
^MTT
^MMT
^LH0,0
^LT0
^LS0
^FO8,5^A0N,18,18^FDPRUEBA 1x0.5^FS
^FO8,30^BY1^BCN,35,N,N,N^FD026072^FS
^FO8,70^A0N,16,16^FD203 x 102 dots^FS
^PQ1
^XZ
"@
    $testResult = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE prueba 1x0.5", $test)
    Write-Host "Prueba: $testResult"
    if ($testResult -notlike "OK*") { exit 11 }
    exit 0
}

if ($CurrentLabel1Only) {
    $current = @"
^XA
^FX etiqueta-1 perfil 1x0.5 (203x102) solo este job
^PW203
^LL102
^MNY
^MTT
^MMT
^LH0,0
^LS0
^AD,54
^CFA,12
^LT0
^CWZ,E:LEXENDDECA.TTF
^FO180,45
^BY1
^BCN,40,N,N,N
^FD026326^FS
^CFA,10
^CFA,14
^FO20,20^FDPrecio:29.95^FS
^CFA,14
^FO20,0^FDDia de la Madre - Caja rectangular blanca^FS
^FO20,20^FD^FS
^CFA,14
^FO20,40^FD#Lote:SE14^FS
^CFA,14
^FO20,58^FDExp.:15/09/26^FS
^PQ1
^XZ
"@
    Write-Host "Impresora: $PrinterName"
    $result = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE formato 1 integrado", $current)
    Write-Host "Formato 1 integrado: $result"
    if ($result -notlike "OK*") { exit 12 }
    exit 0
}

if ($RestoredLabel1Only) {
    $restored = @"
^XA
^FWN
^PON
^CI28
^PW203
^LL102
^LH0,0
^FO14,1^BY1,2,43
^BCN,43,N,N,N
^FD026326^FS
^FO145,4^A0N,14,14^FD[026326]^FS
^FO131,19^A0N,25,21^FDB/.29.95^FS
^FO11,46^A0N,17,9^FB188,2,0,L,0^FDDia de la Madre - Caja rectangular blanca^FS
^FO133,83^A0N,14,14^FDLote:SE14^FS
^FO14,83^A0N,14,14^FDEXP:15/09/2026^FS
^PQ1
^XZ
"@
    Write-Host "Impresora: $PrinterName"
    $result = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE formato 1 restaurado", $restored)
    Write-Host "Formato 1 restaurado: $result"
    if ($result -notlike "OK*") { exit 4 }
    exit 0
}

# 1) Copia literal del ZPL compartido por el usuario.
$original = @"
^XA
^AD,54
^CFA,12
^LT10
^CWZ,E:LEXENDDECA.TTF
^FO180,45
^BY1
^BCN,40,N,N,N
^FD026072^FS
^CFA,10
^CFA,14
^FO20,20^FDPrecio:11.90^FS
^CFA,14
^FO20,0^FDCafe Catuai Molido LCS^FS
^FO20,20^FD^FS
^CFA,14
^FO20,40^FD#Lote:SE14^FS
^CFA,14
^FO20,58^FDExp.:15/09/26^FS
^CFA,14
^FO20,75^FDReg.:N004186^FS
^PQ1
^XZ
"@

if ($OriginalOnly) {
    Write-Host "Impresora: $PrinterName"
    $result = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE ZPL original", $original)
    Write-Host "ZPL original: $result"
    if ($result -notlike "OK*") { exit 6 }
    exit 0
}

# 2) Misma plantilla pasando por los offsets actuales del sistema.
$cfg = Join-Path (Split-Path $PSScriptRoot -Parent) "print_chica_offset.cfg"
$offset = @{ lt = 10; ls = 0; lh_x = 0; lh_y = 0 }
if (Test-Path $cfg) {
    foreach ($line in [IO.File]::ReadAllLines($cfg)) {
        if ($line -match '^\s*(lt|ls|lh_x|lh_y)\s*=\s*(-?\d+)\s*$') {
            $offset[$matches[1]] = [int]$matches[2]
        }
    }
}
$actual = @"
^XA
^FX PRUEBA-ACTUAL OFFSET
^LH$($offset.lh_x),$($offset.lh_y)
^LS$($offset.ls)
^AD,54
^CFA,12
^LT$($offset.lt)
^CWZ,E:LEXENDDECA.TTF
^FO180,45
^BY1
^BCN,40,N,N,N
^FD026072^FS
^CFA,10
^CFA,14
^FO20,20^FDPrecio:11.90^FS
^CFA,14
^FO20,0^FDCafe Catuai Molido LCS^FS
^FO20,20^FD^FS
^CFA,14
^FO20,40^FD#Lote:SE14^FS
^CFA,14
^FO20,58^FDExp.:15/09/26^FS
^CFA,14
^FO20,75^FDReg.:N004186^FS
^PQ1
^XZ
"@

Write-Host "Impresora: $PrinterName"
$r1 = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE prueba 1 original", $original)
Write-Host "1 original: $r1"
if ($r1 -notlike "OK*") { exit 2 }

Start-Sleep -Milliseconds 700
$r2 = [BarcodeRawPrinter]::Send($PrinterName, "BARCODE prueba 2 actual", $actual)
Write-Host "2 actual: $r2"
if ($r2 -notlike "OK*") { exit 3 }

Write-Host ("Offsets prueba 2: LT={0}, LS={1}, LH={2},{3}" -f $offset.lt, $offset.ls, $offset.lh_x, $offset.lh_y)
exit 0
