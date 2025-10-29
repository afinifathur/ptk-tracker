<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('ptks', function (Blueprint $table) {
            $table->date('form_date')->nullable()->after('due_date');
        });

        // Seed nilai awal agar historis tetap masuk akal
        DB::statement("UPDATE ptks SET form_date = DATE(created_at) WHERE form_date IS NULL");
    }

    public function down(): void {
        Schema::table('ptks', function (Blueprint $table) {
            $table->dropColumn('form_date');
        });
    }
};
