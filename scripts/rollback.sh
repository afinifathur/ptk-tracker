#!/bin/bash
#
# PTK Tracker - Rollback Script
# Gunakan jika deployment gagal
#
# Penggunaan:
#   ./rollback.sh                    # Tampilkan daftar backup
#   ./rollback.sh backup_file.sql    # Restore dari file tertentu
#

set -e

# ============================================
# KONFIGURASI
# ============================================
PROJECT_DIR="/srv/docker/apps/ptk-tracker"
CONTAINER_DB="ptk_db"
DB_USER="ptk_user"
DB_PASS="ptk_pass"
DB_NAME="ptk_tracker"
BACKUP_DIR="$HOME/db_backups"

# ============================================
# FUNGSI
# ============================================
log_info() {
    echo -e "\033[0;36m[INFO]\033[0m $1"
}

log_success() {
    echo -e "\033[0;32m[SUCCESS]\033[0m $1"
}

log_error() {
    echo -e "\033[0;31m[ERROR]\033[0m $1"
}

log_warning() {
    echo -e "\033[0;33m[WARNING]\033[0m $1"
}

# ============================================
# MAIN
# ============================================
echo ""
echo "============================================"
echo "  PTK TRACKER - ROLLBACK"
echo "============================================"
echo ""

# Jika tidak ada argument, tampilkan daftar backup
if [[ -z "$1" ]]; then
    log_info "Daftar backup tersedia:"
    echo ""
    ls -lh $BACKUP_DIR/ptk_tracker_*.sql 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'
    echo ""
    log_info "Untuk rollback, jalankan:"
    echo "  ./rollback.sh <nama_file_backup>"
    echo ""
    exit 0
fi

BACKUP_FILE="$1"

# Cek apakah file ada
if [[ ! -f "$BACKUP_FILE" ]]; then
    # Coba cari di backup dir
    if [[ -f "$BACKUP_DIR/$BACKUP_FILE" ]]; then
        BACKUP_FILE="$BACKUP_DIR/$BACKUP_FILE"
    else
        log_error "File backup tidak ditemukan: $BACKUP_FILE"
        exit 1
    fi
fi

log_warning "PERINGATAN: Ini akan mengganti database dengan backup!"
log_info "File backup: $BACKUP_FILE"
echo ""
read -p "Lanjutkan? (y/n): " confirm

if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
    log_info "Rollback dibatalkan"
    exit 0
fi

# Rollback database
log_info "Melakukan rollback database..."
docker exec -i $CONTAINER_DB mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE
log_success "Database berhasil di-rollback!"

# Rollback code (opsional)
echo ""
read -p "Rollback code ke commit sebelumnya juga? (y/n): " rollback_code

if [[ "$rollback_code" == "y" || "$rollback_code" == "Y" ]]; then
    cd $PROJECT_DIR
    log_info "Rollback code ke commit sebelumnya..."
    git reset --hard HEAD~1
    log_success "Code berhasil di-rollback"
fi

# Clear cache
log_info "Membersihkan cache..."
cd $PROJECT_DIR
docker exec ptk_app php artisan config:clear 2>/dev/null || true
docker exec ptk_app php artisan cache:clear 2>/dev/null || true
docker exec ptk_app php artisan view:clear 2>/dev/null || true

echo ""
log_success "Rollback selesai!"
echo ""
