<#
.SYNOPSIS
    Setup Windows Task Scheduler untuk backup otomatis harian
    
.DESCRIPTION
    Script ini akan membuat scheduled task untuk menjalankan backup database
    setiap hari pada jam yang ditentukan.
    
.EXAMPLE
    .\setup_scheduled_backup.ps1
    .\setup_scheduled_backup.ps1 -Hour 8 -Minute 0
#>

param(
    [int]$Hour = 7,      # Jam backup (default: 07:00)
    [int]$Minute = 0
)

# ============================================
# KONFIGURASI
# ============================================
$TaskName = "PTK-Tracker Database Backup"
$TaskDescription = "Backup otomatis database PTK Tracker dari server Ubuntu Docker"
$ScriptPath = "C:\laragon\www\ptk-tracker\scripts\backup_ptk_database.ps1"

# ============================================
# MAIN
# ============================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  SETUP SCHEDULED BACKUP" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# Cek admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (!$isAdmin) {
    Write-Host "[WARNING] Script ini sebaiknya dijalankan sebagai Administrator" -ForegroundColor Yellow
    Write-Host "          untuk membuat scheduled task dengan benar." -ForegroundColor Yellow
    Write-Host ""
}

# Cek apakah script backup ada
if (!(Test-Path $ScriptPath)) {
    Write-Host "[ERROR] Script backup tidak ditemukan: $ScriptPath" -ForegroundColor Red
    exit 1
}

# Hapus task lama jika ada
$existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "[INFO] Menghapus scheduled task lama..." -ForegroundColor Cyan
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
}

# Buat scheduled task
Write-Host "[INFO] Membuat scheduled task baru..." -ForegroundColor Cyan
Write-Host "       Nama: $TaskName" -ForegroundColor White
Write-Host "       Waktu: Setiap hari pukul $($Hour.ToString('00')):$($Minute.ToString('00'))" -ForegroundColor White

try {
    $action = New-ScheduledTaskAction `
        -Execute "PowerShell.exe" `
        -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`"" `
        -WorkingDirectory "C:\laragon\www\ptk-tracker\scripts"

    $trigger = New-ScheduledTaskTrigger -Daily -At "$($Hour):$($Minute)"
    
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -RunOnlyIfNetworkAvailable

    $principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -RunLevel Highest

    Register-ScheduledTask `
        -TaskName $TaskName `
        -Description $TaskDescription `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Principal $principal | Out-Null

    Write-Host ""
    Write-Host "[SUCCESS] Scheduled task berhasil dibuat!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Task akan berjalan setiap hari pukul $($Hour.ToString('00')):$($Minute.ToString('00'))" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Untuk melihat task:" -ForegroundColor Cyan
    Write-Host "  Get-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    Write-Host "Untuk menjalankan task sekarang:" -ForegroundColor Cyan
    Write-Host "  Start-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    Write-Host "Untuk menghapus task:" -ForegroundColor Cyan
    Write-Host "  Unregister-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    
}
catch {
    Write-Host "[ERROR] Gagal membuat scheduled task: $_" -ForegroundColor Red
    exit 1
}
