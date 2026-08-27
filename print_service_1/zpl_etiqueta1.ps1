# Mismo layout que zpl_etiqueta_1.php (coordenadas VB twips -> ZPL 203 dpi).

function Etiqueta1-VbX([int]$twips) {
    return [int][Math]::Round($twips * 203.0 / 2160.0)
}
function Etiqueta1-VbY([int]$twips) {
    return [int][Math]::Round($twips * 203.0 / 1440.0)
}
function Etiqueta1-VbFont([int]$pt) {
    $h = [int][Math]::Round($pt * 203.0 / 72.0)
    if ($h -lt 10) { return 10 }
    return $h
}
function Etiqueta1-Escape([string]$s) {
    if ($null -eq $s) { return "" }
    $s = $s.Replace("`r", " ").Replace("`n", " ")
    return $s.Replace("^", "").Replace("~", "")
}
function Etiqueta1-Wrap([string]$descripcion, [int]$alcance) {
    $descripcion = ($descripcion.Trim() -replace '\s+', ' ')
    if ($descripcion.Length -le $alcance) {
        return @($descripcion, "")
    }
    $chunk = $descripcion.Substring(0, [Math]::Min($alcance, $descripcion.Length))
    $space = $chunk.LastIndexOf(" ")
    if ($space -lt 1) { $space = $alcance }
    if ($space -gt $descripcion.Length) { $space = $descripcion.Length }
    $line1 = $descripcion.Substring(0, $space).Trim()
    $line2 = ""
    if ($space -lt $descripcion.Length) {
        $line2 = $descripcion.Substring($space).Trim()
    }
    if ($line2.Length -gt $alcance) {
        $chunk2 = $line2.Substring(0, [Math]::Min($alcance, $line2.Length))
        $sp2 = $chunk2.LastIndexOf(" ")
        if ($sp2 -lt 1) { $sp2 = $alcance }
        if ($sp2 -gt $line2.Length) { $sp2 = $line2.Length }
        $line2 = $line2.Substring(0, $sp2).Trim()
    }
    return @($line1, $line2)
}

function Etiqueta1-LoteCode([DateTime]$fecha) {
    $mes = [int]$fecha.Month
    $dia = $fecha.ToString("dd")
    switch ($mes) {
        1  { $mescod = "E0" }
        2  { $mescod = "F0" }
        3  { $mescod = "M0" }
        4  { $mescod = "AL" }
        5  { $mescod = "MA" }
        6  { $mescod = "JO" }
        7  { $mescod = "JU" }
        8  { $mescod = "AO" }
        9  { $mescod = "SE" }
        10 { $mescod = "OC" }
        11 { $mescod = "NV" }
        12 { $mescod = "DI" }
        default { $mescod = "XX" }
    }
    return $mescod + $dia
}

function Build-ZplEtiqueta1 {
    param(
        [string]$Codigo = "025127",
        [string]$Descrip = "Galletas de mantequilla con chispas de chocolate",
        [double]$Precio = 8.5,
        [int]$Cant = 1,
        [int]$Expir = 15,
        [DateTime]$FechaManufact = (Get-Date),
        [bool]$ConFecha = $true
    )

    $codigo = ($Codigo -replace '\D', '').PadLeft(6, '0')
    if ($codigo.Length -gt 6) { $codigo = $codigo.Substring($codigo.Length - 6) }
    $codeTxt = "[" + $codigo + "]"

    $pw = 203
    $ll = 102
    $xBc = Etiqueta1-VbX 150
    $yBc = Etiqueta1-VbY 10
    $xCode = Etiqueta1-VbX 1700
    $yCode = Etiqueta1-VbY 30
    $hCode = Etiqueta1-VbFont 5
    $xDesc = Etiqueta1-VbX 120
    $yDesc = Etiqueta1-VbY 325
    $hDesc = Etiqueta1-VbFont 6
    $dyDesc = Etiqueta1-VbY 117
    $xExp = Etiqueta1-VbX 150
    $yExp = Etiqueta1-VbY 590
    $hExp = Etiqueta1-VbFont 5
    $xPrice = Etiqueta1-VbX 1495
    $yPrice = Etiqueta1-VbY 137
    $hPrice = Etiqueta1-VbFont 9

    $descripEsc = Etiqueta1-Escape (($Descrip.Trim() -replace '\s+', ' '))
    $wDesc = [int][Math]::Round($hDesc * 0.55)
    if ($wDesc -lt 8) { $wDesc = 8 }
    $fbW = $pw - $xDesc - 4
    if ($fbW -lt 80) { $fbW = 80 }
    $fbGap = $dyDesc - $hDesc
    if ($fbGap -lt 0) { $fbGap = 0 }

    $bcH = Etiqueta1-VbY 1000
    if ($bcH -gt ($yDesc - $yBc - 2)) { $bcH = $yDesc - $yBc - 2 }
    if ($bcH -lt 18) { $bcH = 18 }

    if (($xCode + 55) -gt $pw) { $xCode = $pw - 58 }
    if (($xPrice + 70) -gt $pw) { $xPrice = $pw - 72 }

    if ($Cant -lt 1) { $Cant = 1 }

    $nl = "`r`n"
    $zpl = "^XA" + $nl + "^FWN" + $nl + "^PON" + $nl + "^CI28" + $nl + "^PW" + $pw + $nl + "^LL" + $ll + $nl + "^LH0,0" + $nl
    $zpl += "^FO" + $xBc + "," + $yBc + "^BY1,2," + $bcH + $nl
    $zpl += "^BCN," + $bcH + ",N,N,N" + $nl + "^FD" + $codigo + "^FS" + $nl
    $zpl += "^FO" + $xCode + "," + $yCode + "^A0N," + $hCode + "," + $hCode + "^FD" + (Etiqueta1-Escape $codeTxt) + "^FS" + $nl

    if ($Precio -ne 0) {
        $priceTxt = "B/." + $Precio.ToString("#,##0.00", [System.Globalization.CultureInfo]::InvariantCulture)
        $hPriceW = [int][Math]::Round($hPrice * 0.85)
        $zpl += "^FO" + $xPrice + "," + $yPrice + "^A0N," + $hPrice + "," + $hPriceW + "^FD" + (Etiqueta1-Escape $priceTxt) + "^FS" + $nl
    }

    $zpl += "^FO" + $xDesc + "," + $yDesc + "^A0N," + $hDesc + "," + $wDesc + "^FB" + $fbW + ",2," + $fbGap + ",L,0^FD" + $descripEsc + "^FS" + $nl

    if ($ConFecha) {
        $loteTxt = "Lote:" + (Etiqueta1-LoteCode $FechaManufact)
        $xLote = $pw - 70
        $zpl += "^FO" + $xLote + "," + $yExp + "^A0N," + $hExp + "," + $hExp + "^FD" + (Etiqueta1-Escape $loteTxt) + "^FS" + $nl

        if ($Expir -ne 0) {
            $expDate = $FechaManufact.AddDays($Expir).ToString("dd/MM/yyyy")
            $zpl += "^FO" + $xExp + "," + $yExp + "^A0N," + $hExp + "," + $hExp + "^FDEXP:" + $expDate + "^FS" + $nl
        }
    }

    $zpl += "^PQ" + $Cant + $nl + "^XZ" + $nl
    return $zpl
}
