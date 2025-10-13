<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('departments', function (Blueprint $t) {
      $t->string('code',10)->nullable()->after('name')->index();
    });
  }
  public function down(): void {
    Schema::table('departments', fn(Blueprint $t)=>$t->dropColumn('code'));
  }
};
