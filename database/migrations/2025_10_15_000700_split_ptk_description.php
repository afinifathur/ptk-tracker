<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom baru untuk memecah deskripsi PTK menjadi beberapa bagian.
     */
    public function up(): void
    {
        Schema::table('ptks', function (Blueprint $table) {
            if (!Schema::hasColumn('ptks', 'description_nc')) {
                $table->text('description_nc')->nullable()->after('description'); // Deskripsi Ketidaksesuaian
            }

            if (!Schema::hasColumn('ptks', 'evaluation')) {
                $table->text('evaluation')->nullable()->after('description_nc'); // Evaluasi Masalah
            }

            if (!Schema::hasColumn('ptks', 'correction_action')) {
                $table->text('correction_action')->nullable()->after('evaluation'); // 3a Koreksi
            }

            if (!Schema::hasColumn('ptks', 'corrective_action')) {
                $table->text('corrective_action')->nullable()->after('correction_action'); // 3b Tindakan Korektif
            }
        });
    }

    /**
     * Rollback: hapus kolom tambahan jika ada.
     */
    public function down(): void
    {
        Schema::table('ptks', function (Blueprint $table) {
            $cols = ['description_nc', 'evaluation', 'correction_action', 'corrective_action'];

            foreach ($cols as $col) {
                if (Schema::hasColumn('ptks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
