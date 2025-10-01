<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder inisialisasi lain kalau ada
        if (class_exists(\Database\Seeders\InitSeeder::class)) {
            $this->call(\Database\Seeders\InitSeeder::class);
        }

        // Registrasikan & jalankan PTKInitSeeder
        $this->call(\Database\Seeders\PTKInitSeeder::class);
    }
}
