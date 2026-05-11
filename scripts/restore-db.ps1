<#
.SYNOPSIS
  Restores a MySQL dump (.sql) into the database configured in .env (Lab 4).

.DESCRIPTION
  **Destructive:** imports SQL into the target database. You must type YES to confirm.
  Validates the dump file before running. Use -PreBackup to take a safety mysqldump first.

.PARAMETER DumpFile
  Path to the .sql file (relative to project root or absolute).

.PARAMETER PreBackup
  If set, runs scripts/backup-db.ps1 before restore (safety snapshot).

.EXAMPLE
  .\scripts\restore-db.ps1 -DumpFile ".\writable\backups\ajesdb_20260101_120000.sql"
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$DumpFile,
    [switch]$PreBackup
)

$ErrorActionPreference = "Stop"

$ScriptDir   = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
. (Join-Path $ScriptDir "_ReadCiEnv.ps1")

$EnvFile = Join-Path $ProjectRoot ".env"

$resolvedDump = $DumpFile
if (-not [System.IO.Path]::IsPathRooted($resolvedDump)) {
    $resolvedDump = Join-Path $ProjectRoot $DumpFile
}
if (-not (Test-Path -LiteralPath $resolvedDump)) {
    Write-Error "Dump file not found: $resolvedDump"
}

if (-not (Test-AjesSqlDumpFile -Path $resolvedDump)) {
    Write-Error "Dump file failed validation (too small or missing SQL dump markers). Refusing to restore."
}

$envMap = Get-AjesCiEnv -EnvFilePath $EnvFile
$hostDb = $envMap['database.default.hostname']; if (-not $hostDb) { $hostDb = "localhost" }
$dbName = $envMap['database.default.database']
$user   = $envMap['database.default.username']
$pass   = $envMap['database.default.password']

if (-not $dbName -or -not $user) {
    Write-Error ".env must define database.default.database and database.default.username"
}

Write-Host "WARNING: This will import SQL into database '$dbName' on '$hostDb'."
Write-Host "Dump file: $resolvedDump"
if ($PreBackup) {
    Write-Host "Taking pre-restore backup via backup-db.ps1 ..."
    & (Join-Path $ScriptDir "backup-db.ps1")
}

$confirm = Read-Host "Type YES (uppercase) to continue"
if ($confirm -cne "YES") {
    Write-Host "Aborted."
    exit 1
}

$mysql = Resolve-AjesMysqlTool -ExeName "mysql.exe"
if (-not $mysql) {
    Write-Error "mysql.exe not found. Add MySQL bin to PATH or set `$env:MYSQL_BIN."
}

$errFile = Join-Path $ProjectRoot "writable\backups\_mysql_restore_err.txt"

if ($pass) {
    $p = Start-Process -FilePath $mysql -ArgumentList @("--host=$hostDb", "--user=$user", "-p$pass", $dbName) -NoNewWindow -Wait -PassThru -RedirectStandardInput $resolvedDump -RedirectStandardError $errFile
} else {
    $p = Start-Process -FilePath $mysql -ArgumentList @("--host=$hostDb", "--user=$user", $dbName) -NoNewWindow -Wait -PassThru -RedirectStandardInput $resolvedDump -RedirectStandardError $errFile
}

if ($p.ExitCode -ne 0) {
    $err = Get-Content $errFile -Raw -ErrorAction SilentlyContinue
    Remove-Item $errFile -ErrorAction SilentlyContinue
    Write-Error "mysql restore failed (exit $($p.ExitCode)): $err"
}
Remove-Item $errFile -ErrorAction SilentlyContinue
Write-Host "Restore completed for database '$dbName'."
