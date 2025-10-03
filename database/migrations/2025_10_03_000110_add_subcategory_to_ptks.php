<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('ptks', function (Blueprint $t) {
      $t->foreignId('subcategory_id')->nullable()->after('category_id')
        ->constrained('subcategories')->nullOnDelete();
      $t->index('subcategory_id');
    });
  }
  public function down(): void {
    Schema::table('ptks', function (Blueprint $t) {
      $t->dropConstrainedForeignId('subcategory_id');
    });
  }
};
