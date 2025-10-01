<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ptks', function (Blueprint $table) {
            $table->index('number');
            $table->index('status');
            $table->index('department_id');
            $table->index(['due_date','status']);
            $table->index('created_at');
        });
    }
    public function down(): void {
        Schema::table('ptks', function (Blueprint $table) {
            $table->dropIndex(['ptks_number_index']);
            $table->dropIndex(['ptks_status_index']);
            $table->dropIndex(['ptks_department_id_index']);
            $table->dropIndex(['ptks_due_date_status_index']);
            $table->dropIndex(['ptks_created_at_index']);
        });
    }
};
