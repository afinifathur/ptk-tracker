<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class InitSeeder extends Seeder {
    public function run(): void {
        User::truncate();
        User::create(['name'=>'Director','email'=>'admin@example.com','password'=>Hash::make('password')]);
        User::create(['name'=>'Approver','email'=>'approver@example.com','password'=>Hash::make('password')]);
        User::create(['name'=>'Auditor','email'=>'auditor@example.com','password'=>Hash::make('password')]);
        User::create(['name'=>'Flange Admin','email'=>'flange_admin@example.com','password'=>Hash::make('password')]);
        User::create(['name'=>'Fitting Admin','email'=>'fitting_admin@example.com','password'=>Hash::make('password')]);
        User::create(['name'=>'HRK3 Admin','email'=>'hrk3_admin@example.com','password'=>Hash::make('password')]);
    }
}
