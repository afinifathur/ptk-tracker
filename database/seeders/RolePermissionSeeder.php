<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan cache permission Spatie dibersihkan
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        // ==== 1) Definisikan roles & permissions ====
        $roles = [
            'admin_qc',
            'admin_qc_flange',
            'admin_qc_fitting',
            'admin_hr',
            'admin_k3',
            'kabag_qc',
            'manager_hr',
            'director',
            'auditor',
        ];

        $permissions = [
            // PTK
            'ptk.create',
            'ptk.view',
            'ptk.update',
            'ptk.approve',
            'ptk.reject',
            // Menu
            'menu.queue',
            'menu.recycle',
            'menu.audit',
        ];

        // Matrix role -> permissions
        $matrix = [
            'admin_qc'         => ['ptk.create','ptk.view','ptk.update'],
            'admin_qc_flange'  => ['ptk.create','ptk.view','ptk.update'],
            'admin_qc_fitting' => ['ptk.create','ptk.view','ptk.update'],
            'admin_hr'         => ['ptk.create','ptk.view','ptk.update'],
            'admin_k3'         => ['ptk.create','ptk.view','ptk.update'],
            'kabag_qc'         => ['ptk.view','ptk.approve','menu.queue'],
            'manager_hr'       => ['ptk.view','ptk.approve','menu.queue'],
            'director'         => '*', // semua permission
            'auditor'          => ['ptk.view','menu.audit'],
        ];

        DB::transaction(function () use ($guard, $roles, $permissions, $matrix) {
            // ==== 2) Buat permissions ====
            $permMap = [];
            foreach ($permissions as $p) {
                $permMap[$p] = Permission::firstOrCreate(
                    ['name' => $p, 'guard_name' => $guard]
                );
            }

            // ==== 3) Buat roles ====
            $roleMap = [];
            foreach ($roles as $r) {
                $roleMap[$r] = Role::firstOrCreate(
                    ['name' => $r, 'guard_name' => $guard]
                );
            }

            // ==== 4) Isi matrix role-permission ====
            foreach ($matrix as $roleName => $permList) {
                $role = $roleMap[$roleName] ?? null;
                if (!$role) {
                    continue;
                }

                if ($permList === '*') {
                    // Director: semua permission
                    $role->syncPermissions(Permission::where('guard_name', $guard)->get());
                } else {
                    $assign = collect($permList)->map(fn($name) => $permMap[$name])->all();
                    $role->syncPermissions($assign);
                }
            }
        });

        // ==== 5) Fallback: user tanpa role => director ====
        // (Sama seperti kode awal; sesuaikan jika kebijakan berbeda)
        User::query()
            ->doesntHave('roles')
            ->each(function (User $u) {
                $u->assignRole('director');
            });

        // Bersihkan cache lagi setelah perubahan
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
