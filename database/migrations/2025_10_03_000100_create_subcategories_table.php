<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('subcategories', function (Blueprint $t) {
      $t->id();
      $t->foreignId('category_id')->constrained()->cascadeOnDelete();
      $t->string('name');
      $t->timestamps();
      $t->unique(['category_id','name']);
    });
  }
  public function down(): void { Schema::dropIfExists('subcategories'); }
};
