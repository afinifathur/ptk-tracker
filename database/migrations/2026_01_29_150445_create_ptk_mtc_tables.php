<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ptk_mtc_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ptk_id')->constrained('ptks')->cascadeOnDelete();

            // B. Spesifik Machine
            $table->text('machine_damage_desc')->nullable();
            $table->enum('machine_stop_status', ['total', 'partial'])->nullable();
            $table->text('problem_evaluation')->nullable(); // Evaluasi Masalah

            // C. Gate Utama
            $table->boolean('needs_sparepart')->default(true);

            // E. Koreksi & Perbaikan (Sparepart datang)
            $table->date('installation_date')->nullable();
            $table->string('repaired_by')->nullable();
            $table->text('technical_notes')->nullable();

            // F. Hasil Uji Coba
            $table->enum('machine_status_after', ['normal', 'trouble'])->nullable();
            $table->integer('trial_hours')->nullable();
            $table->text('trial_result')->nullable();

            $table->timestamps();
        });

        Schema::create('ptk_spareparts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ptk_mtc_detail_id')->constrained('ptk_mtc_details')->cascadeOnDelete();

            $table->string('name');
            $table->string('spec')->nullable();
            $table->integer('qty')->default(1);
            $table->string('supplier')->nullable();

            $table->date('order_date')->nullable();
            $table->enum('status', ['Requested', 'Ordered', 'Shipped', 'Received'])->default('Requested');

            $table->date('est_arrival_date')->nullable();
            $table->date('actual_arrival_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ptk_mtc_tables');
    }
};
