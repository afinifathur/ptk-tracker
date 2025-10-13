<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ptk_sequences', function (Blueprint $t) {
      $t->id();
      $t->foreignId('department_id')->constrained()->cascadeOnDelete();
      $t->unsignedSmallInteger('year');
      $t->unsignedTinyInteger('month');
      $t->unsignedInteger('last_run')->default(0);
      $t->timestamps();
      $t->unique(['department_id','year','month']);
    });
  }
  public function down(): void { Schema::dropIfExists('ptk_sequences'); }
};
