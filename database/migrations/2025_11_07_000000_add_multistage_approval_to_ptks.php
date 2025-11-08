<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ptks', function (Blueprint $t) {
            // tahap 1 (kabag/manager)
            $t->foreignId('approved_stage1_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_stage1_at')->nullable();

            // tahap 2 (director)
            $t->foreignId('approved_stage2_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_stage2_at')->nullable();

            // reject info
            $t->enum('last_reject_stage', ['stage1','stage2'])->nullable();
            $t->text('last_reject_reason')->nullable();
            $t->foreignId('last_reject_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('last_reject_at')->nullable();
        });
    }

    public function down(): void {
        Schema::table('ptks', function (Blueprint $t) {
            $t->dropConstrainedForeignId('approved_stage1_by');
            $t->dropColumn('approved_stage1_at');
            $t->dropConstrainedForeignId('approved_stage2_by');
            $t->dropColumn('approved_stage2_at');
            $t->dropColumn(['last_reject_stage','last_reject_reason']);
            $t->dropConstrainedForeignId('last_reject_by');
            $t->dropColumn('last_reject_at');
        });
    }
};
