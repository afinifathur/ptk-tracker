<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sla_settings', function (Blueprint $t) {
      $t->id();
      $t->enum('entity_type',['category','department']);
      $t->unsignedBigInteger('entity_id');
      $t->unsignedInteger('days'); // target hari penyelesaian
      $t->timestamps();
      $t->unique(['entity_type','entity_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('sla_settings'); }
};