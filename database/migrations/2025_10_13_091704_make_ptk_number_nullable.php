<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ptks', function (Blueprint $t) {
            $t->string('number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ptks', function (Blueprint $t) {
            $t->string('number')->nullable(false)->change();
        });
    }
};
