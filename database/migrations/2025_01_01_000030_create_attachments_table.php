<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ptk_id')->constrained('ptks')->cascadeOnDelete();
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attachments'); }
};
