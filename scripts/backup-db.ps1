<#
.SYNOPSIS
  Creates a MySQL logical backup (mysqldump) for the database configured in .env (Lab 4).

.DESCRIPTION
  Reads database.default.* from the project .env and writes:
    writable/backups/ajesdb_YYYYMMDD_HHMMSS.sql
  Verifies the output looks like a real SQL dump (non-trivial size + header patterns).

  Optional: set $env:MYSQL_BIN to the folder containing mysqldump.exe (e.g. C:\xampp\mysql\bin).

#>
$ErrorActionPreference = "Stop"

$ScriptDir   = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "_ReadCiEnv.ps1")

$EnvFile = Join-Path $ProjectRoot ".env"
$OutDir  = Join-Path $ProjectRoot "writable\backups"

$envMap   = Get-AjesCiEnv -EnvFilePath $EnvFile
$hostDb   = $envMap['database.default.hostname']; if (-not $hostDb) { $hostDb = "localhost" }
$dbName   = $envMap['database.default.database']
$user     = $envMap['database.default.username']
$pass     = $envMap['database.default.password']

if (-not $dbName -or -not $user) {
    Write-Error ".env must define database.default.database and database.default.username"
}

if (-not (Test-Path $OutDir)) {
    New-Item -ItemType Directory -Path $OutDir | Out-Null
}

$mysqldump = Resolve-AjesMysqlTool -ExeName "mysqldump.exe"
if (-not $mysqldump) {
    Write-Error "mysqldump.exe not found. Add MySQL bin to PATH or set `$env:MYSQL_BIN (e.g. C:\xampp\mysql\bin)."
}

$stamp   = Get-Date -Format "yyyyMMdd_HHmmss"
$outFile = Join-Path $OutDir "ajesdb_$stamp.sql"
$errFile = Join-Path $OutDir "_mysqldump_err.txt"

Write-Host "Backing up database '$dbName' on host '$hostDb' -> $outFile"

$argList = @(
    "--host=$hostDb",
    "--user=$user",
    "--single-transaction",
    "--routines",
    "--events",
    "--add-drop-table",
    "--default-character-set=utf8mb4",
    $dbName
)
if ($pass) {
    $argList = @(
        "--host=$hostDb",
        "--user=$user",
        "-p$pass",
        "--single-transaction",
        "--routines",
        "--events",
        "--add-drop-table",
        "--default-character-set=utf8mb4",
        $dbName
    )
}

$p = Start-Process -FilePath $mysqldump -ArgumentList $argList -NoNewWindow -Wait -PassThru -RedirectStandardOutput $outFile -RedirectStandardError $errFile
if ($p.ExitCode -ne 0) {
    $err = Get-Content $errFile -Raw -ErrorAction SilentlyContinue
    Remove-Item $outFile -ErrorAction SilentlyContinue
    Remove-Item $errFile -ErrorAction SilentlyContinue
    Write-Error "mysqldump failed (exit $($p.ExitCode)): $err"
}
Remove-Item $errFile -ErrorAction SilentlyContinue

if (-not (Test-AjesSqlDumpFile -Path $outFile)) {
    Remove-Item $outFile -ErrorAction SilentlyContinue
    Write-Error "Backup verification failed: output is empty or does not look like a SQL dump. Check DB credentials and that mysqldump completed successfully."
}

Write-Host "Done. Verified SQL dump. Size:" (Get-Item $outFile).Length "bytes"
