<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // pastikan kolom ada setelah email (opsional)
            $t->foreignId('department_id')
              ->nullable()
              ->after('email')
              // jika nama tabelmu 'departments', cukup ->constrained()
              // kalau beda, gunakan ->constrained('nama_tabel_departemen')
              ->constrained()
              ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // drop FK + kolom sekaligus (method helper Laravel)
            $t->dropConstrainedForeignId('department_id');
        });
    }
};
