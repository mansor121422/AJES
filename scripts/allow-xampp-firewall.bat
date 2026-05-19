@echo off
:: Double-click and approve UAC so your phone can reach XAMPP on port 80.
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell -Verb RunAs -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"%~dp0allow-xampp-firewall.ps1\"'"
