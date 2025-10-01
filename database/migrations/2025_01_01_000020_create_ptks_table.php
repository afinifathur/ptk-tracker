<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ptks', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('title');
            $table->longText('description');

            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pic_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('status')->default('Not Started'); // Not Started | In Progress | Completed
            $table->date('due_date')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status','due_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('ptks'); }
};
