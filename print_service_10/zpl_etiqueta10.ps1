# Convierte BMP/PNG de printserver a ZPL ^GFA (1 bit) para etiqueta 10.

Add-Type -AssemblyName System.Drawing | Out-Null

$script:Etiqueta10Threshold = 160

function Etiqueta10-PadCodigo([string]$Codigo) {
    $d = ($Codigo -replace '\D', '')
    if ([string]::IsNullOrEmpty($d)) { $d = "0" }
    $d = $d.PadLeft(6, '0')
    if ($d.Length -gt 6) { $d = $d.Substring($d.Length - 6) }
    return $d
}

function Etiqueta10-TwipsToDots([int]$twips) {
    return [int][Math]::Round($twips * 203.0 / 1440.0)
}

function Etiqueta10-InchesToDots([double]$inches) {
    return [int][Math]::Round($inches * 203.0)
}

function Etiqueta10-FindImage([string]$Codigo, [string]$ImagesDir) {
    $code = Etiqueta10-PadCodigo $Codigo
    $names = @($code, $code.TrimStart('0'))
    if ([string]::IsNullOrEmpty($names[1])) { $names[1] = "0" }
    $exts = @(".bmp", ".png", ".jpg", ".jpeg")
    foreach ($n in $names) {
        foreach ($e in $exts) {
            $p = Join-Path $ImagesDir ($n + $e)
            if (Test-Path $p) { return $p }
        }
    }
    return $null
}

function Etiqueta10-ParsePrinterSize([string]$Printer) {
    if ([string]::IsNullOrEmpty($Printer)) { return $null }
    $m = [regex]::Match($Printer, '(\d+(?:\.\d+)?)x(\d+(?:\.\d+)?)')
    if (-not $m.Success) { return $null }
    $a = [double]$m.Groups[1].Value
    $b = [double]$m.Groups[2].Value
    if ($a -le 0 -or $b -le 0 -or $a -gt 12 -or $b -gt 12) { return $null }
    return @{
        W = (Etiqueta10-InchesToDots $a)
        H = (Etiqueta10-InchesToDots $b)
    }
}

function Etiqueta10-BitmapToGfa([System.Drawing.Bitmap]$bmp, [int]$threshold) {
    $w = $bmp.Width
    $h = $bmp.Height
    $bpr = [int][Math]::Ceiling($w / 8.0)
    $bytes = New-Object byte[] ($bpr * $h)
    $rect = New-Object System.Drawing.Rectangle 0, 0, $w, $h
    $data = $bmp.LockBits($rect, [System.Drawing.Imaging.ImageLockMode]::ReadOnly, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    try {
        $stride = $data.Stride
        $ptr = $data.Scan0
        $buf = New-Object byte[] ($stride * $h)
        [System.Runtime.InteropServices.Marshal]::Copy($ptr, $buf, 0, $buf.Length)
        for ($y = 0; $y -lt $h; $y++) {
            $row = $y * $stride
            for ($x = 0; $x -lt $w; $x++) {
                $i = $row + ($x * 3)
                $b = [int]$buf[$i]
                $g = [int]$buf[$i + 1]
                $r = [int]$buf[$i + 2]
                $lum = (0.299 * $r) + (0.587 * $g) + (0.114 * $b)
                if ($lum -lt $threshold) {
                    $idx = ($y * $bpr) + [int][Math]::Floor($x / 8)
                    $shift = 7 - ($x % 8)
                    $bytes[$idx] = [byte]($bytes[$idx] -bor (1 -shl $shift))
                }
            }
        }
    } finally {
        $bmp.UnlockBits($data)
    }
    $sb = New-Object System.Text.StringBuilder ($bytes.Length * 2)
    foreach ($by in $bytes) {
        [void]$sb.Append($by.ToString("X2"))
    }
    return @{
        Hex   = $sb.ToString()
        Total = $bytes.Length
        Bpr   = $bpr
        W     = $w
        H     = $h
    }
}

function Etiqueta10-FitBitmap([System.Drawing.Bitmap]$src, [int]$dw, [int]$dh) {
    if ($dw -lt 1) { $dw = 1 }
    if ($dh -lt 1) { $dh = 1 }
    $scale = [Math]::Min($dw / [double]$src.Width, $dh / [double]$src.Height)
    $nw = [Math]::Max(1, [int][Math]::Round($src.Width * $scale))
    $nh = [Math]::Max(1, [int][Math]::Round($src.Height * $scale))
    $dst = New-Object System.Drawing.Bitmap $nw, $nh, ([System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    $g = [System.Drawing.Graphics]::FromImage($dst)
    $g.Clear([System.Drawing.Color]::White)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.DrawImage($src, 0, 0, $nw, $nh)
    $g.Dispose()
    return $dst
}

function Build-ZplEtiqueta10 {
    param(
        [string]$Codigo,
        [int]$Cant = 1,
        [string]$ImagesDir,
        [string]$Printer = "",
        [int]$LabelW = 0,
        [int]$LabelH = 0,
        [int]$DesignX = 0,
        [int]$DesignY = 0,
        [int]$DesignW = 0,
        [int]$DesignH = 0,
        [int]$BarX = -1,
        [int]$BarY = -1,
        [int]$BarW = 0,
        [int]$BarH = 0
    )
    if ($Cant -lt 1) { $Cant = 1 }
    $code = Etiqueta10-PadCodigo $Codigo
    $path = Etiqueta10-FindImage $code $ImagesDir
    if (-not $path) {
        throw "No hay imagen en printserver para $code"
    }
    $src = [System.Drawing.Bitmap]::FromFile($path)
    try {
        $size = Etiqueta10-ParsePrinterSize $Printer
        if ($size) {
            $pw = [int]$size.W
            $ll = [int]$size.H
        } elseif ($LabelW -gt 0 -and $LabelH -gt 0) {
            $pw = Etiqueta10-TwipsToDots $LabelW
            $ll = Etiqueta10-TwipsToDots $LabelH
            if ($pw -lt $ll -and ($ll / [double]$pw) -gt 1.6) {
                $tmp = $pw; $pw = $ll; $ll = $tmp
            }
        } else {
            $pw = $src.Width
            $ll = $src.Height
        }

        $dx = if ($DesignX -gt 0) { Etiqueta10-TwipsToDots $DesignX } else { 0 }
        $dy = if ($DesignY -gt 0) { Etiqueta10-TwipsToDots $DesignY } else { 0 }
        $dw = if ($DesignW -gt 0) { Etiqueta10-TwipsToDots $DesignW } else { $pw }
        $dh = if ($DesignH -gt 0) { Etiqueta10-TwipsToDots $DesignH } else { $ll }
        $useBar = ($BarX -ge 0 -and $BarY -ge 0 -and $BarW -gt 20)
        $bx = if ($BarX -ge 0) { Etiqueta10-TwipsToDots $BarX } else { -1 }
        $by = if ($BarY -ge 0) { Etiqueta10-TwipsToDots $BarY } else { -1 }
        $bw = if ($BarW -gt 0) { Etiqueta10-TwipsToDots $BarW } else { 0 }
        $bh = if ($BarH -gt 0) { Etiqueta10-TwipsToDots $BarH } else { 0 }

        if ($useBar -and $by -gt 8) {
            $maxDh = $by - $dy - 4
            if ($maxDh -lt 20) { $maxDh = 20 }
            if ($dh -gt $maxDh) { $dh = $maxDh }
        }
        if ($dx -ge $pw) { $dx = 0 }
        if ($dy -ge $ll) { $dy = 0 }
        if ($dw -gt ($pw - $dx)) { $dw = $pw - $dx }
        if ($dh -gt ($ll - $dy)) { $dh = $ll - $dy }
        if ($dw -lt 8) { $dw = $pw; $dx = 0 }
        if ($dh -lt 8) { $dh = $ll; $dy = 0 }

        $fitted = Etiqueta10-FitBitmap $src $dw $dh
        try {
            $gfa = Etiqueta10-BitmapToGfa $fitted $script:Etiqueta10Threshold
            $ox = $dx + [int][Math]::Floor(($dw - $gfa.W) / 2)
            $oy = $dy
            if ($ox -lt 0) { $ox = 0 }

            $zpl = "^XA`n^FWN`n^PON`n^CI28`n^PW$pw`n^LL$ll`n^LH0,0`n"
            $zpl += "^FO$ox,$oy^GFA,$($gfa.Total),$($gfa.Total),$($gfa.Bpr),$($gfa.Hex)^FS`n"
            if ($useBar) {
                if (($bx + $bw) -gt $pw) { $bw = $pw - $bx }
                if (($by + $bh) -gt $ll) { $bh = $ll - $by - 2 }
                if ($bh -lt 18) { $bh = 18 }
                if ($bw -gt 30 -and $bh -gt 12) {
                    $zpl += "^FO$bx,$by^BY2,2,$bh`n"
                    $zpl += "^BCN,$bh,N,N,N`n^FD$code^FS`n"
                    $hTxt = 18
                    $yTxt = $by + $bh + 2
                    if (($yTxt + $hTxt) -lt $ll) {
                        $zpl += "^FO$bx,$yTxt^A0N,$hTxt,$hTxt^FD[$code]^FS`n"
                    }
                }
            }
            $zpl += "^PQ$Cant`n^XZ`n"
            return @{
                Zpl     = $zpl
                Path    = $path
                Pw      = $pw
                Ll      = $ll
                ImgW    = $src.Width
                ImgH    = $src.Height
                Printer = $Printer
            }
        } finally {
            $fitted.Dispose()
        }
    } finally {
        $src.Dispose()
    }
}

function Etiqueta10-ParseInt($v) {
    if ($null -eq $v) { return 0 }
    $n = 0
    [void][int]::TryParse([string]$v, [ref]$n)
    return $n
}

function Get-LblsLayout {
    param(
        [string]$Codigo,
        [string]$IndexPath
    )
    $code = Etiqueta10-PadCodigo $Codigo
    if (-not (Test-Path $IndexPath)) { return $null }
    $needle = "'id' => '$code'"
    $line = Select-String -Path $IndexPath -Pattern $needle -SimpleMatch | Select-Object -First 1
    if (-not $line) { return $null }
    $t = $line.Line
    $h = @{}
    $rx = [regex]"'([a-zA-Z_]+)' => '([^']*)'"
    foreach ($m in $rx.Matches($t)) {
        $h[$m.Groups[1].Value] = $m.Groups[2].Value
    }
    if (-not $h.ContainsKey("printer")) { return $null }
    return $h
}

function Get-LocalPrinterNames {
    $names = @()
    foreach ($p in @(Get-WmiObject -Class Win32_Printer)) {
        if ($p.Name) { $names += [string]$p.Name }
    }
    return $names
}

function Resolve-Etiqueta10Printer {
    param(
        [string]$Wanted,
        [hashtable]$PrinterMap,
        [string]$Fallback = "VirtualZPLPrinter_Sistemas",
        [bool]$SkipUnc = $true
    )
    $local = @(Get-LocalPrinterNames)
    $usable = @()
    foreach ($n in $local) {
        if ($SkipUnc -and $n.StartsWith("\\")) { continue }
        $usable += $n
    }
    if (-not [string]::IsNullOrEmpty($Wanted)) {
        foreach ($n in $usable) {
            if ($n -eq $Wanted) { return $n }
        }
        if ($PrinterMap -and $PrinterMap.ContainsKey($Wanted)) {
            $mapped = [string]$PrinterMap[$Wanted]
            foreach ($n in $usable) {
                if ($n -eq $mapped) { return $n }
            }
        }
        foreach ($n in $usable) {
            if ($n.ToLower().IndexOf($Wanted.ToLower()) -ge 0) { return $n }
        }
    }
    foreach ($n in $usable) {
        if ($n -eq $Fallback) { return $n }
    }
    if ($usable.Count -gt 0) { return $usable[0] }
    return $Wanted
}
