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
        // Bersihkan cache permission Spatie (penting)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        // ==== 1) Daftar role ====
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

        // ==== 2) Permissions yang dipakai UI (sesuai instruksi) ====
        $uiPerms = [
            'ptk.create','ptk.update','ptk.delete',
            'ptk.view','ptk.export',           // penting untuk detail & preview/unduh
            'menu.queue','menu.recycle','menu.settings',
            'menu.audit',
        ];

        // ==== 3) Matrix role -> permissions ====
        $matrix = [
            // role admin (disamakan)
            'admin_qc'         => ['ptk.create','ptk.update','ptk.delete','ptk.view','ptk.export','menu.queue','menu.recycle','menu.settings'],
            'admin_qc_flange'  => ['ptk.create','ptk.update','ptk.delete','ptk.view','ptk.export','menu.queue','menu.recycle','menu.settings'],
            'admin_qc_fitting' => ['ptk.create','ptk.update','ptk.delete','ptk.view','ptk.export','menu.queue','menu.recycle','menu.settings'],
            'admin_hr'         => ['ptk.create','ptk.update','ptk.delete','ptk.view','ptk.export','menu.queue','menu.recycle','menu.settings'],
            'admin_k3'         => ['ptk.create','ptk.update','ptk.delete','ptk.view','ptk.export','menu.queue','menu.recycle','menu.settings'],

            // non-admin
            'kabag_qc'         => ['ptk.view','ptk.approve','menu.queue'],
            'manager_hr'       => ['ptk.view','ptk.approve','menu.queue'],
            'director'         => '*', // semua permission
            'auditor'          => ['ptk.view','menu.audit'],
        ];

        // ==== 4) Bangun daftar permission final: UI + yang ada di matrix ====
        $matrixPerms = collect($matrix)
            ->flatMap(fn($permList) => $permList === '*' ? [] : $permList)
            ->unique()
            ->values();

        $permissions = collect($uiPerms)
            ->merge($matrixPerms)
            ->unique()
            ->values()
            ->all();

        // ==== 5) Buat role & permission, lalu assign ====
        DB::transaction(function () use ($guard, $roles, $permissions, $matrix) {
            // Buat semua permissions (hindari mismatch key)
            $permMap = [];
            foreach ($permissions as $p) {
                $permMap[$p] = Permission::firstOrCreate([
                    'name' => $p,
                    'guard_name' => $guard,
                ]);
            }

            // Buat semua roles
            $roleMap = [];
            foreach ($roles as $r) {
                $roleMap[$r] = Role::firstOrCreate([
                    'name' => $r,
                    'guard_name' => $guard,
                ]);
            }

            // Assign permissions sesuai matrix
            foreach ($matrix as $roleName => $permList) {
                $role = $roleMap[$roleName] ?? null;
                if (!$role) {
                    continue;
                }

                if ($permList === '*') {
                    // Director: semua permission yang sudah terdaftar
                    $role->syncPermissions(Permission::where('guard_name', $guard)->get());
                } else {
                    // Map aman: hanya ambil permission yang sudah dibuat
                    $assign = collect($permList)
                        ->map(fn($name) => $permMap[$name] ?? null)
                        ->filter()
                        ->values()
                        ->all();

                    $role->syncPermissions($assign);
                }
            }
        });

        // ==== 6) Fallback: user tanpa role => director ====
        User::query()
            ->doesntHave('roles')
            ->each(fn(User $u) => $u->assignRole('director'));

        // Bersihkan cache lagi setelah perubahan
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
