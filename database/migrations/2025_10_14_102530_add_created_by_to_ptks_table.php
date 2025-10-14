<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan perubahan ke database.
     */
    public function up(): void
    {
        Schema::table('ptks', function (Blueprint $table) {
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    /**
     * Kembalikan perubahan (rollback).
     */
    public function down(): void
    {
        Schema::table('ptks', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
