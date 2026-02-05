<#
.SYNOPSIS
    Restore/Import PTK Tracker Database ke Laragon MySQL
    
.DESCRIPTION
    Script ini akan:
    1. Menampilkan daftar backup yang tersedia
    2. Import file backup ke database Laragon MySQL
    
.EXAMPLE
    .\restore_ptk_database.ps1
    .\restore_ptk_database.ps1 -BackupFile "ptk_tracker_backup_20260205_100000.sql"
#>

param(
    [string]$BackupFile = ""
)

# ============================================
# KONFIGURASI LARAGON
# ============================================
$MysqlPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$DbHost = "127.0.0.1"
$DbPort = "3306"
$DbName = "ptk_tracker"
$DbUser = "root"
$DbPass = "123456788"

$BackupDir = "C:\laragon\www\ptk-tracker\backups"

# ============================================
# FUNGSI
# ============================================

function Log-Info($message) {
    Write-Host "[INFO] $message" -ForegroundColor Cyan
}

function Log-Success($message) {
    Write-Host "[SUCCESS] $message" -ForegroundColor Green
}

function Log-Error($message) {
    Write-Host "[ERROR] $message" -ForegroundColor Red
}

function Log-Warning($message) {
    Write-Host "[WARNING] $message" -ForegroundColor Yellow
}

# ============================================
# MAIN SCRIPT
# ============================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  PTK TRACKER DATABASE RESTORE SCRIPT" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# Cek apakah MySQL executable ada
if (!(Test-Path $MysqlPath)) {
    Log-Error "MySQL tidak ditemukan di: $MysqlPath"
    Log-Warning "Sesuaikan path MySQL di script ini"
    exit 1
}

# Tampilkan daftar backup
$backups = Get-ChildItem "$BackupDir\ptk_tracker_backup_*.sql" | Sort-Object LastWriteTime -Descending

if ($backups.Count -eq 0) {
    Log-Error "Tidak ada file backup ditemukan di: $BackupDir"
    Log-Info "Jalankan backup_ptk_database.ps1 terlebih dahulu"
    exit 1
}

Write-Host "Daftar backup tersedia:" -ForegroundColor Yellow
Write-Host ""
$i = 1
foreach ($backup in $backups) {
    $sizeMB = [math]::Round($backup.Length / 1MB, 2)
    $date = $backup.LastWriteTime.ToString("yyyy-MM-dd HH:mm:ss")
    Write-Host "  [$i] $($backup.Name)" -ForegroundColor White -NoNewline
    Write-Host " - $sizeMB MB - $date" -ForegroundColor Gray
    $i++
}

Write-Host ""

# Pilih file backup
if ($BackupFile -eq "") {
    $selection = Read-Host "Pilih nomor backup (1-$($backups.Count)) atau ketik nama file"
    
    if ($selection -match '^\d+$') {
        $index = [int]$selection - 1
        if ($index -ge 0 -and $index -lt $backups.Count) {
            $selectedBackup = $backups[$index]
        }
        else {
            Log-Error "Nomor tidak valid"
            exit 1
        }
    }
    else {
        $selectedBackup = Get-Item "$BackupDir\$selection" -ErrorAction SilentlyContinue
        if (!$selectedBackup) {
            Log-Error "File tidak ditemukan: $selection"
            exit 1
        }
    }
}
else {
    $selectedBackup = Get-Item "$BackupDir\$BackupFile" -ErrorAction SilentlyContinue
    if (!$selectedBackup) {
        Log-Error "File tidak ditemukan: $BackupFile"
        exit 1
    }
}

Log-Info "File yang dipilih: $($selectedBackup.Name)"
Write-Host ""

# Konfirmasi
$confirm = Read-Host "PERHATIAN: Ini akan menimpa database '$DbName'. Lanjutkan? (y/n)"
if ($confirm -ne 'y' -and $confirm -ne 'Y') {
    Log-Warning "Operasi dibatalkan"
    exit 0
}

# Buat database jika belum ada
Log-Info "Menyiapkan database..."
$createDbCommand = "CREATE DATABASE IF NOT EXISTS $DbName;"
& $MysqlPath -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" -e $createDbCommand 2>$null

# Import database
Log-Info "Mengimport database... (ini mungkin memakan waktu beberapa menit)"

try {
    $importProcess = Start-Process -FilePath $MysqlPath `
        -ArgumentList "-h", $DbHost, "-P", $DbPort, "-u", $DbUser, "-p$DbPass", $DbName `
        -RedirectStandardInput $selectedBackup.FullName `
        -NoNewWindow -Wait -PassThru
    
    if ($importProcess.ExitCode -eq 0) {
        Log-Success "Database berhasil diimport!"
    }
    else {
        Log-Error "Gagal import database. Exit code: $($importProcess.ExitCode)"
        exit 1
    }
}
catch {
    Log-Error "Error saat import: $_"
    exit 1
}

# Verifikasi
Log-Info "Memverifikasi import..."
$countQuery = "SELECT COUNT(*) as count FROM users;"
$result = & $MysqlPath -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" $DbName -e $countQuery 2>$null

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  RESTORE SELESAI!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Database '$DbName' telah diupdate dari backup:" -ForegroundColor Yellow
Write-Host "  $($selectedBackup.Name)" -ForegroundColor White
Write-Host ""
Write-Host "Jalankan aplikasi untuk verifikasi:" -ForegroundColor Cyan
Write-Host "  cd C:\laragon\www\ptk-tracker" -ForegroundColor White
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host ""
