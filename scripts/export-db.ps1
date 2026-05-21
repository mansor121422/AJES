# Export AjesDB to AjesDB-export.sql (reads .env for DB name when possible).
$root = Split-Path $PSScriptRoot -Parent
$out  = Join-Path $root "AjesDB-export.sql"
$mysqldump = "c:\xampp\mysql\bin\mysqldump.exe"

if (-not (Test-Path $mysqldump)) {
    Write-Error "mysqldump not found at $mysqldump. Adjust path for your XAMPP install."
    exit 1
}

$dbName = "AjesDB"
$dbUser = "root"
$dbPass = ""
$envFile = Join-Path $root ".env"
if (Test-Path $envFile) {
    foreach ($line in Get-Content $envFile) {
        if ($line -match '^\s*database\.default\.database\s*=\s*(.+)$') { $dbName = $Matches[1].Trim() }
        if ($line -match '^\s*database\.default\.username\s*=\s*(.+)$') { $dbUser = $Matches[1].Trim() }
        if ($line -match '^\s*database\.default\.password\s*=\s*(.*)$') { $dbPass = $Matches[1].Trim() }
    }
}

$args = @("-u", $dbUser, "--host=localhost", "--single-transaction", "--routines", "--triggers", "--add-drop-table", "--default-character-set=utf8mb4", $dbName)
if ($dbPass -ne "") { $args = @("-u", $dbUser, "-p$dbPass") + $args[2..($args.Length - 1)] }

$dump = & $mysqldump @args 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Error ($dump -join "`n")
    exit 1
}

$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllLines($out, $dump, $utf8NoBom)
Write-Host "Exported $dbName -> $out ($((Get-Item $out).Length) bytes)"
