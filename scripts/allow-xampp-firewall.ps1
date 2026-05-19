# Run as Administrator: right-click → "Run with PowerShell" (as admin)
# Allows phones on your Wi-Fi to reach XAMPP Apache on port 80.
#Requires -RunAsAdministrator

param([switch]$NoPause)

$ruleName = "XAMPP Apache HTTP (AJES)"
$existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
if ($existing) {
    Write-Host "Rule already exists: $ruleName"
} else {
    New-NetFirewallRule `
        -DisplayName $ruleName `
        -Direction Inbound `
        -Protocol TCP `
        -LocalPort 80 `
        -Action Allow `
        -Profile Any
    Write-Host "Added firewall rule: $ruleName (TCP 80 inbound)"
}

$ip = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.IPAddress -notlike '127.*' -and $_.PrefixOrigin -ne 'WellKnown' } |
    Select-Object -First 1 -ExpandProperty IPAddress)

Write-Host ""
Write-Host "On your phone (same Wi-Fi), open in the browser:"
if ($ip) {
    Write-Host "  http://${ip}/AJES/"
} else {
    Write-Host "  http://YOUR_PC_IP/AJES/"
}
Write-Host "If that page loads, rebuild and run AJESCHAT."
Write-Host ""

if (-not $NoPause) {
    Read-Host "Press Enter to close"
}
