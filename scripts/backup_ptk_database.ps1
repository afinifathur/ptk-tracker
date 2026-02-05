<#
.SYNOPSIS
    Backup PTK Tracker Database from Ubuntu Server (Docker)
    
.DESCRIPTION
    Script ini akan:
    1. SSH ke server Ubuntu
    2. Export database dari container Docker ptk_db
    3. Download file backup ke Windows lokal
    4. Menghapus backup lama di server (opsional)
    
.NOTES
    Server: peroniks@10.88.8.46
    Container: ptk_db
    Database: ptk_tracker
    
.EXAMPLE
    .\backup_ptk_database.ps1
    .\backup_ptk_database.ps1 -KeepDays 30
#>

param(
    [int]$KeepDays = 7  # Simpan backup selama X hari
)

# ============================================
# KONFIGURASI - Sesuaikan jika diperlukan
# ============================================
$ServerUser = "peroniks"
$ServerHost = "10.88.8.46"
$ServerSSH = "$ServerUser@$ServerHost"

$DockerContainer = "ptk_db"
$DbName = "ptk_tracker"
$DbUser = "ptk_user"
$DbPass = "ptk_pass"

$LocalBackupDir = "C:\laragon\www\ptk-tracker\backups"
$ServerBackupDir = "/tmp"

# ============================================
# FUNGSI
# ============================================

function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

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

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFileName = "ptk_tracker_backup_$timestamp.sql"
$serverBackupPath = "$ServerBackupDir/$backupFileName"
$localBackupPath = "$LocalBackupDir\$backupFileName"

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  PTK TRACKER DATABASE BACKUP SCRIPT" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

# Step 1: Buat direktori backup lokal jika belum ada
Log-Info "Memeriksa direktori backup lokal..."
if (!(Test-Path $LocalBackupDir)) {
    New-Item -ItemType Directory -Path $LocalBackupDir -Force | Out-Null
    Log-Success "Direktori backup dibuat: $LocalBackupDir"
} else {
    Log-Info "Direktori backup sudah ada: $LocalBackupDir"
}

# Step 2: Export database dari Docker di server
Log-Info "Melakukan backup database di server..."
Log-Info "Container: $DockerContainer | Database: $DbName"

$exportCommand = "docker exec $DockerContainer mysqldump -u $DbUser -p$DbPass $DbName > $serverBackupPath"

try {
    ssh $ServerSSH $exportCommand
    if ($LASTEXITCODE -eq 0) {
        Log-Success "Database berhasil di-export ke server: $serverBackupPath"
    } else {
        Log-Error "Gagal export database. Exit code: $LASTEXITCODE"
        exit 1
    }
} catch {
    Log-Error "Error saat SSH: $_"
    exit 1
}

# Step 3: Download file backup ke lokal
Log-Info "Mendownload file backup ke Windows..."

try {
    scp "${ServerSSH}:$serverBackupPath" $localBackupPath
    if ($LASTEXITCODE -eq 0) {
        Log-Success "File berhasil didownload: $localBackupPath"
    } else {
        Log-Error "Gagal download file. Exit code: $LASTEXITCODE"
        exit 1
    }
} catch {
    Log-Error "Error saat SCP: $_"
    exit 1
}

# Step 4: Verifikasi file backup
$fileInfo = Get-Item $localBackupPath
$fileSizeMB = [math]::Round($fileInfo.Length / 1MB, 2)
Log-Info "Ukuran file backup: $fileSizeMB MB"

# Step 5: Hapus backup di server (cleanup)
Log-Info "Membersihkan file backup di server..."
ssh $ServerSSH "rm -f $serverBackupPath"
Log-Success "File backup di server dihapus"

# Step 6: Hapus backup lama di lokal (lebih dari $KeepDays hari)
Log-Info "Membersihkan backup lama (lebih dari $KeepDays hari)..."
$oldFiles = Get-ChildItem "$LocalBackupDir\ptk_tracker_backup_*.sql" | Where-Object {
    $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays)
}

if ($oldFiles.Count -gt 0) {
    foreach ($file in $oldFiles) {
        Remove-Item $file.FullName -Force
        Log-Warning "Dihapus: $($file.Name)"
    }
} else {
    Log-Info "Tidak ada backup lama yang perlu dihapus"
}

# Step 7: Tampilkan daftar backup yang ada
Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  DAFTAR BACKUP TERSEDIA" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta

$allBackups = Get-ChildItem "$LocalBackupDir\ptk_tracker_backup_*.sql" | Sort-Object LastWriteTime -Descending
foreach ($backup in $allBackups) {
    $sizeMB = [math]::Round($backup.Length / 1MB, 2)
    Write-Host "  $($backup.Name) - $sizeMB MB" -ForegroundColor White
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  BACKUP SELESAI!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "File terbaru: $localBackupPath" -ForegroundColor Yellow
Write-Host ""
