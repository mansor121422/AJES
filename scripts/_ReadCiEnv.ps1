# Dot-source from backup-db.ps1 / restore-db.ps1 — parses CodeIgniter .env key = value lines.
function Get-AjesCiEnv {
    param([Parameter(Mandatory)][string]$EnvFilePath)
    if (-not (Test-Path -LiteralPath $EnvFilePath)) {
        throw "Missing .env at: $EnvFilePath"
    }
    $map = @{}
    Get-Content -LiteralPath $EnvFilePath | ForEach-Object {
        $line = $_.Trim()
        if ($line -match '^\s*#' -or $line -eq '') { return }
        $eq = $line.IndexOf('=')
        if ($eq -lt 1) { return }
        $key = $line.Substring(0, $eq).Trim()
        $val = $line.Substring($eq + 1).Trim()
        if (($val.StartsWith('"') -and $val.EndsWith('"')) -or ($val.StartsWith("'") -and $val.EndsWith("'"))) {
            $val = $val.Substring(1, $val.Length - 2)
        }
        $map[$key] = $val
    }
    return $map
}

function Resolve-AjesMysqlTool {
    param([Parameter(Mandatory)][string]$ExeName)
    $bin = $null
    if ($env:MYSQL_BIN) {
        $cand = Join-Path $env:MYSQL_BIN.TrimEnd('\') $ExeName
        if (Test-Path -LiteralPath $cand) { $bin = $cand }
    }
    if (-not $bin -and $ExeName -eq 'mysqldump.exe') {
        $x = 'C:\xampp\mysql\bin\mysqldump.exe'
        if (Test-Path -LiteralPath $x) { $bin = $x }
    }
    if (-not $bin -and $ExeName -eq 'mysql.exe') {
        $x = 'C:\xampp\mysql\bin\mysql.exe'
        if (Test-Path -LiteralPath $x) { $bin = $x }
    }
    if (-not $bin) {
        $cmd = Get-Command $ExeName -ErrorAction SilentlyContinue
        if ($cmd) { $bin = $cmd.Source }
    }
    return $bin
}

function Test-AjesSqlDumpFile {
    param([Parameter(Mandatory)][string]$Path)
    if (-not (Test-Path -LiteralPath $Path)) { return $false }
    $len = (Get-Item -LiteralPath $Path).Length
    if ($len -lt 64) { return $false }
    $lines = @(Get-Content -LiteralPath $Path -TotalCount 60 -ErrorAction SilentlyContinue)
    if ($lines.Count -eq 0) { return $false }
    $head = $lines -join "`n"
    return ($head -match '(?i)CREATE\s+TABLE|INSERT\s+INTO|DROP\s+TABLE|mysqldump')
}
