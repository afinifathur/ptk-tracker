#!/bin/bash
#
# PTK Tracker - Safe Deployment Script
# Jalankan di server: ./deploy.sh
#
# Penggunaan:
#   ./deploy.sh          # Deploy normal
#   ./deploy.sh --skip-npm  # Skip npm install/build
#

set -e  # Stop jika ada error

# ============================================
# KONFIGURASI
# ============================================
PROJECT_DIR="/srv/docker/apps/ptk-tracker"
CONTAINER_APP="ptk_app"
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
echo "  PTK TRACKER - SAFE DEPLOYMENT"
echo "============================================"
echo ""

# Cek argument
SKIP_NPM=false
if [[ "$1" == "--skip-npm" ]]; then
    SKIP_NPM=true
    log_info "Skipping npm install/build"
fi

# Step 1: Masuk ke directory project
log_info "Masuk ke directory project..."
cd $PROJECT_DIR

# Step 2: Backup database SEBELUM update
log_info "Membuat backup database..."
mkdir -p $BACKUP_DIR
BACKUP_FILE="$BACKUP_DIR/ptk_tracker_$(date +%Y%m%d_%H%M%S).sql"
docker exec $CONTAINER_DB mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE
BACKUP_SIZE=$(du -h $BACKUP_FILE | cut -f1)
log_success "Backup dibuat: $BACKUP_FILE ($BACKUP_SIZE)"

# Step 3: Pull code terbaru
log_info "Pulling code terbaru dari GitHub..."
git pull origin main
log_success "Code berhasil di-pull"

# Step 4: Install composer dependencies (jika ada perubahan)
if git diff HEAD~1 --name-only | grep -q "composer"; then
    log_info "Perubahan composer terdeteksi, menjalankan composer install..."
    docker exec $CONTAINER_APP composer install --no-dev --optimize-autoloader
    log_success "Composer install selesai"
else
    log_info "Tidak ada perubahan composer, skip..."
fi

# Step 5: Jalankan migrasi
log_info "Menjalankan database migrations..."
docker exec $CONTAINER_APP php artisan migrate --force
log_success "Migrations selesai"

# Step 6: Clear semua cache
log_info "Membersihkan cache..."
docker exec $CONTAINER_APP php artisan config:clear
docker exec $CONTAINER_APP php artisan cache:clear
docker exec $CONTAINER_APP php artisan view:clear
docker exec $CONTAINER_APP php artisan route:clear
log_success "Cache dibersihkan"

# Step 7: NPM install dan build (jika tidak di-skip)
if [[ "$SKIP_NPM" == false ]]; then
    if git diff HEAD~1 --name-only | grep -qE "(package\.json|vite\.config|resources/)"; then
        log_info "Perubahan frontend terdeteksi, menjalankan npm build..."
        docker exec $CONTAINER_APP npm install
        docker exec $CONTAINER_APP npm run build
        log_success "NPM build selesai"
    else
        log_info "Tidak ada perubahan frontend, skip npm build..."
    fi
fi

# Step 8: Storage symlink (jika belum ada)
log_info "Memastikan storage symlink..."
docker exec $CONTAINER_APP php artisan storage:link 2>/dev/null || true

# Selesai
echo ""
echo "============================================"
echo "  DEPLOYMENT SELESAI!"
echo "============================================"
echo ""
log_success "Aplikasi berhasil di-update"
log_info "Backup tersimpan di: $BACKUP_FILE"
echo ""
log_warning "Jika ada error, rollback dengan:"
echo "  docker exec -i $CONTAINER_DB mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE"
echo ""

# Cleanup backup lama (simpan 7 hari terakhir)
log_info "Membersihkan backup lama..."
find $BACKUP_DIR -name "ptk_tracker_*.sql" -mtime +7 -delete
log_info "Backup lebih dari 7 hari dihapus"
