<#
.SYNOPSIS
    Sync files dari server ke lokal (incremental - hanya file baru)
    
.DESCRIPTION
    Script ini akan:
    1. Mendapatkan daftar file dari server
    2. Membandingkan dengan file lokal
    3. Hanya download file baru/yang berubah
    
.EXAMPLE
    .\sync_storage_files.ps1
    .\sync_storage_files.ps1 -Force  # Download semua (full sync)
#>

param(
    [switch]$Force  # Jika diset, download semua file (full sync)
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

function Get-ServerFileList($folder) {
    # Get list of files from server with their modification times
    $cmd = "find $ServerStoragePath/$folder -type f -printf '%T@ %p\n' 2>/dev/null"
    $result = ssh $ServerSSH $cmd
    
    $files = @{}
    foreach ($line in $result) {
        if ($line -match '^(\d+\.?\d*)\s+(.+)$') {
            $timestamp = [double]$Matches[1]
            $fullPath = $Matches[2]
            $relativePath = $fullPath.Replace("$ServerStoragePath/", "")
            $files[$relativePath] = $timestamp
        }
    }
    return $files
}

function Get-LocalFileList($folder) {
    $localPath = Join-Path $LocalStoragePath $folder
    $files = @{}
    
    if (Test-Path $localPath) {
        Get-ChildItem -Path $localPath -File -Recurse | ForEach-Object {
            $relativePath = $_.FullName.Replace("$LocalStoragePath\", "").Replace("\", "/")
            $timestamp = [double]($_.LastWriteTimeUtc - [datetime]'1970-01-01').TotalSeconds
            $files[$relativePath] = $timestamp
        }
    }
    
    return $files
}

function Sync-Folder($folder) {
    Log-Info "Syncing folder: $folder"
    
    # Get file lists
    Log-Info "  Mendapatkan daftar file dari server..."
    $serverFiles = Get-ServerFileList $folder
    Log-Info "  File di server: $($serverFiles.Count)"
    
    Log-Info "  Mendapatkan daftar file lokal..."
    $localFiles = Get-LocalFileList $folder
    Log-Info "  File di lokal: $($localFiles.Count)"
    
    # Find files to download
    $filesToDownload = @()
    
    foreach ($serverFile in $serverFiles.Keys) {
        $needDownload = $false
        
        if ($Force) {
            $needDownload = $true
        }
        elseif (-not $localFiles.ContainsKey($serverFile)) {
            # File doesn't exist locally
            $needDownload = $true
        }
        elseif ($serverFiles[$serverFile] -gt ($localFiles[$serverFile] + 60)) {
            # Server file is newer (with 60 second tolerance)
            $needDownload = $true
        }
        
        if ($needDownload) {
            $filesToDownload += $serverFile
        }
    }
    
    Log-Info "  File yang perlu didownload: $($filesToDownload.Count)"
    
    if ($filesToDownload.Count -eq 0) {
        Log-Success "  Tidak ada file baru untuk $folder"
        return 0
    }
    
    # Download files
    $downloaded = 0
    foreach ($file in $filesToDownload) {
        $serverPath = "$ServerStoragePath/$file"
        $localPath = Join-Path $LocalStoragePath ($file -replace "/", "\")
        
        # Create directory if needed
        $localDir = Split-Path $localPath -Parent
        if (!(Test-Path $localDir)) {
            New-Item -ItemType Directory -Path $localDir -Force | Out-Null
        }
        
        # Download file
        Write-Host "  Downloading: $file" -ForegroundColor Gray
        scp "${ServerSSH}:$serverPath" "$localPath" 2>$null
        
        if ($LASTEXITCODE -eq 0) {
            $downloaded++
        }
    }
    
    Log-Success "  Downloaded $downloaded files dari $folder"
    return $downloaded
}

# ============================================
# MAIN
# ============================================

Write-Host ""
Write-Host "============================================" -ForegroundColor Magenta
Write-Host "  PTK TRACKER - STORAGE FILES SYNC" -ForegroundColor Magenta
Write-Host "============================================" -ForegroundColor Magenta
Write-Host ""

if ($Force) {
    Log-Warning "Mode FORCE: Akan download semua file"
}

# Ensure local storage directory exists
if (!(Test-Path $LocalStoragePath)) {
    New-Item -ItemType Directory -Path $LocalStoragePath -Force | Out-Null
    Log-Info "Created local storage directory"
}

# Sync each folder
$totalDownloaded = 0
foreach ($folder in $FoldersToSync) {
    $count = Sync-Folder $folder
    $totalDownloaded += $count
}

# Summary
Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  SYNC SELESAI!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Total file didownload: $totalDownloaded" -ForegroundColor Yellow

# Show storage size
$localSize = (Get-ChildItem $LocalStoragePath -Recurse -File | Measure-Object -Property Length -Sum).Sum
$localSizeMB = [math]::Round($localSize / 1MB, 2)
Write-Host "Total ukuran storage lokal: $localSizeMB MB" -ForegroundColor Yellow
Write-Host ""
