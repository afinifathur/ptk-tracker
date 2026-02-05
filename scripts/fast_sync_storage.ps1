<#
.SYNOPSIS
    Fast sync files dari server ke lokal menggunakan SCP recursive
    
.DESCRIPTION
    Script ini akan:
    1. Download seluruh folder secara recursive via SCP
    2. Jauh lebih cepat dari SCP per-file
    3. Overwrite file yang sudah ada jika ada di server
    
.EXAMPLE
    .\fast_sync_storage.ps1
    .\fast_sync_storage.ps1 -FolderName ptk  # Sync folder tertentu saja
#>

param(
    [string]$FolderName = ""  # Jika kosong, sync semua folder
)

# ============================================
# KONFIGURASI
# ============================================
$ServerUser = "peroniks"
$ServerHost = "10.88.8.46"
$ServerSSH = "$ServerUser@$ServerHost"

$ServerStoragePath = "/srv/docker/apps/ptk-tracker/storage/app/public"
$LocalStoragePath = "C:\laragon\www\ptk-tracker\storage\app\public"

# Folders to sync
$FoldersToSync = @("ptk", "pdf-images")

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

function Fast-Sync-Folder($folder) {
    Log-Info "===================="
    Log-Info "Syncing folder: $folder"
    Log-Info "===================="
    
    $localFolderPath = Join-Path $LocalStoragePath $folder
    
    # Get count of files on server
    Log-Info "Checking server files..."
    $serverCount = ssh $ServerSSH "find $ServerStoragePath/$folder -type f 2>/dev/null | wc -l"
    Log-Info "Server has $serverCount files in $folder"
    
    # Get count of local files before sync
    $localCountBefore = 0
    if (Test-Path $localFolderPath) {
        $localCountBefore = (Get-ChildItem -Path $localFolderPath -File -Recurse -ErrorAction SilentlyContinue | Measure-Object).Count
    }
    Log-Info "Local has $localCountBefore files in $folder"
    
    # Download using SCP recursive
    Log-Info "Downloading $folder via SCP..."
    $startTime = Get-Date
    
    try {
        # SCP recursive - fast and reliable
        $serverPath = "${ServerSSH}:$ServerStoragePath/$folder"
        
        scp -r $serverPath "$LocalStoragePath\"
        
        if ($LASTEXITCODE -eq 0) {
            $endTime = Get-Date
            $duration = ($endTime - $startTime).TotalSeconds
            Log-Success "Downloaded successfully in $([math]::Round($duration, 2)) seconds"
        }
        else {
            Log-Error "Download failed with exit code: $LASTEXITCODE"
        }
        
        # Verify
        $localCountAfter = (Get-ChildItem -Path $localFolderPath -File -Recurse -ErrorAction SilentlyContinue | Measure-Object).Count
        Log-Info "Final local count: $localCountAfter files"
        
        $newFiles = $localCountAfter - $localCountBefore
        if ($newFiles -gt 0) {
            Log-Success "Added $newFiles new files"
        }
        
        return $newFiles
    }
    catch {
        Log-Error "Error: $_"
        return 0
    }
}

# ============================================
# MAIN
# ============================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  PTK TRACKER - FAST STORAGE SYNC" -ForegroundColor Magenta
Write-Host "  (Using SCP recursive)" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

$overallStart = Get-Date

# Ensure local storage directory exists
if (!(Test-Path $LocalStoragePath)) {
    New-Item -ItemType Directory -Path $LocalStoragePath -Force | Out-Null
    Log-Info "Created local storage directory"
}

# Determine which folders to sync
if ($FolderName -ne "") {
    $foldersToProcess = @($FolderName)
}
else {
    $foldersToProcess = $FoldersToSync
}

# Sync each folder
$totalNew = 0
foreach ($folder in $foldersToProcess) {
    $count = Fast-Sync-Folder $folder
    $totalNew += $count
}

$overallEnd = Get-Date
$totalDuration = ($overallEnd - $overallStart).TotalSeconds

# Summary
Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  SYNC SELESAI!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Total waktu: $([math]::Round($totalDuration, 2)) detik" -ForegroundColor Yellow

# Show storage size
if (Test-Path $LocalStoragePath) {
    $localSize = (Get-ChildItem $LocalStoragePath -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
    $localSizeMB = [math]::Round($localSize / 1MB, 2)
    Write-Host "Total ukuran storage lokal: $localSizeMB MB" -ForegroundColor Yellow
}
Write-Host ""
