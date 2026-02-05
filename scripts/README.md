# PTK Tracker Database Backup Scripts

Scripts untuk backup dan restore database PTK Tracker dari server Ubuntu (Docker) ke lokal (Laragon).

## Prasyarat

1. **SSH Key sudah dikonfigurasi** - Pastikan bisa SSH ke server tanpa password:
   ```powershell
   ssh peroniks@10.88.8.46
   ```
   
2. **Laragon berjalan** dengan MySQL aktif

3. **Execution Policy** PowerShell diizinkan:
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   ```

## Scripts Tersedia

### 1. `backup_ptk_database.ps1` - Backup dari Server

Backup database dari server Docker ke lokal Windows.

```powershell
# Jalankan backup
.\backup_ptk_database.ps1

# Simpan backup selama 30 hari (default 7 hari)
.\backup_ptk_database.ps1 -KeepDays 30
```

**Proses:**
1. SSH ke server `peroniks@10.88.8.46`
2. Export database dari container `ptk_db`
3. Download file `.sql` ke `C:\laragon\www\ptk-tracker\backups\`
4. Hapus file backup di server
5. Hapus backup lama di lokal (lebih dari 7 hari)

### 2. `restore_ptk_database.ps1` - Restore ke Laragon

Import backup ke database Laragon MySQL.

```powershell
# Menu interaktif
.\restore_ptk_database.ps1

# Langsung pilih file
.\restore_ptk_database.ps1 -BackupFile "ptk_tracker_backup_20260205_100000.sql"
```

### 3. `setup_scheduled_backup.ps1` - Backup Otomatis Harian

Setup Windows Task Scheduler untuk backup otomatis.

```powershell
# Backup setiap hari jam 07:00 (default)
.\setup_scheduled_backup.ps1

# Backup setiap hari jam 08:30
.\setup_scheduled_backup.ps1 -Hour 8 -Minute 30
```

## Quick Start

```powershell
cd C:\laragon\www\ptk-tracker\scripts

# 1. Test backup manual
.\backup_ptk_database.ps1

# 2. Jika berhasil, setup backup otomatis harian
.\setup_scheduled_backup.ps1

# 3. Untuk restore ke Laragon
.\restore_ptk_database.ps1
```

## Troubleshooting

### SSH tidak bisa connect
```powershell
# Test koneksi
ssh peroniks@10.88.8.46 "echo 'Connected!'"

# Jika perlu setup SSH key
ssh-keygen -t ed25519
ssh-copy-id peroniks@10.88.8.46
```

### MySQL path tidak ditemukan
Edit file `restore_ptk_database.ps1`, sesuaikan path MySQL:
```powershell
$MysqlPath = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
```

### Permission denied di server
```bash
# Di server
sudo usermod -aG docker peroniks
# Logout dan login kembali
```

## Konfigurasi

### Server (dalam scripts)
- Host: `peroniks@10.88.8.46`
- Container: `ptk_db`
- Database: `ptk_tracker`
- User: `ptk_user`
- Password: `ptk_pass`

### Lokal Laragon
- Host: `127.0.0.1:3306`
- Database: `ptk_tracker`
- User: `root`
- Password: `123456788`
