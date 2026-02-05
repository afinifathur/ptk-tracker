<#
.SYNOPSIS
    Full Backup - Database + Storage Files
    
.DESCRIPTION
    Script ini menjalankan:
    1. Backup database dari server
    2. Sync storage files (incremental)
    
.EXAMPLE
    .\full_backup.ps1
#>

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  PTK TRACKER - FULL BACKUP" -ForegroundColor Magenta
Write-Host "  Database + Storage Files" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Step 1: Backup Database
Write-Host "[1/2] BACKUP DATABASE" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow
& "$scriptDir\backup_ptk_database.ps1"

Write-Host ""

# Step 2: Sync Storage Files
Write-Host "[2/2] SYNC STORAGE FILES" -ForegroundColor Yellow
Write-Host "----------------------------------------" -ForegroundColor Yellow
& "$scriptDir\sync_storage_files.ps1"

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  FULL BACKUP SELESAI!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Backup tersimpan di:" -ForegroundColor Cyan
Write-Host "  Database : C:\laragon\www\ptk-tracker\backups\" -ForegroundColor White
Write-Host "  Files    : C:\laragon\www\ptk-tracker\storage\app\public\" -ForegroundColor White
Write-Host ""
