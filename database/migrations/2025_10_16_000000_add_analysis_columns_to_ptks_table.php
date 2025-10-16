<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tambahkan kolom analisis ke tabel ptks.
     */
    public function up(): void
    {
        Schema::table('ptks', function (Blueprint $table) {
            // Deskripsi Ketidaksesuaian
            $table->longText('desc_nc')->nullable();

            // Tindakan Koreksi
            $table->longText('action_correction')->nullable();

            // Tindakan Korektif
            $table->longText('action_corrective')->nullable();
        });
    }

    /**
     * Hapus kolom analisis jika rollback.
     */
    public function down(): void
    {
        Schema::table('ptks', function (Blueprint $table) {
            $table->dropColumn(['desc_nc', 'action_correction', 'action_corrective']);
        });
    }
};
