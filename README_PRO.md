# PTK Tracker – PRO Overlay (Logo + Hash + Import + Dept Policy)
**Updated:** 2025-09-29 08:06

Berisi:
- Watermark & footer audit pakai **logo perusahaan** + **hash dokumen unik** (PDF).
- Logo di halaman **Login** (Breeze) & **kop PDF**: **PT. Peroni Karya Sentra**.
- **Import Excel (bulk PTK)** + validasi (contoh template CSV disertakan).
- **Policy/permission granular per departemen** + scoping listing berdasar role admin_dept.

## Langkah cepat
1) Ekstrak overlay ini ke root project (overwrite). Letakkan logo perusahaan di: `public/brand/logo.png` (PNG transparan disarankan).
2) Jika belum, install Breeze: `composer require laravel/breeze --dev && php artisan breeze:install blade && npm run build`
3) Migrasi tidak diperlukan untuk fitur ini. Jalankan: `composer dump-autoload`
4) Coba PDF (detail PTK → tombol PDF) dan Import (Daftar PTK → tombol Import).

## Template Import
- File contoh: `storage/app/templates/ptk_import_template.csv`
- Kolom: number,title,description,category,department,pic_user_id,due_date(YYYY-MM-DD),status
- Status: Not Started | In Progress | Completed
