<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use App\Models\Department;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 0) Reset cache spatie (sebelum & sesudah perubahan)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        // 1) Definisi role
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

        // 2) Definisi permission dasar
        $adminPerms = [
            'ptk.create','ptk.update','ptk.delete','ptk.view','ptk.export','menu.settings',
        ];
        $approverExtra = ['ptk.approve','ptk.reject','menu.queue'];
        $extraDirector = ['menu.recycle','menu.audit','ptk.restore','ptk.force'];
        $otherMenu = ['menu.queue','menu.recycle','menu.audit']; // untuk konsistensi UI

        // 3) Kumpulan permission akhir yang dipastikan terdaftar
        $allNamedPerms = collect()
            ->merge($adminPerms)
            ->merge($approverExtra)
            ->merge($extraDirector)
            ->merge($otherMenu)
            ->unique()
            ->values();

        // 4) Mapping role → permission (tanpa cakupan departemen)
        $roleMatrix = [
            'admin_qc'         => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_qc_flange'  => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_qc_fitting' => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_hr'         => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_k3'         => array_values(array_unique(array_merge($adminPerms, $otherMenu))),

            'kabag_qc'         => array_values(array_unique(array_merge($adminPerms, $approverExtra))),
            'manager_hr'       => array_values(array_unique(array_merge($adminPerms, $approverExtra))),

            'director'         => '*', // semua permission terdaftar (plus nanti dept-scope)
            'auditor'          => ['ptk.view','ptk.export','menu.audit'],
        ];

        // 5) Konfigurasi cakupan departemen (SESUAIKAN nama persis di DB)
        $deptNamesForKabag = ['QC FLANGE','QC FITTING'];
        $deptNamesForMgrHr = ['HR DAN K3']; // pisahkan ke ['HR','K3'] jika di DB terpisah

        DB::transaction(function () use (
            $guard, $roles, $allNamedPerms, $roleMatrix,
            $deptNamesForKabag, $deptNamesForMgrHr
        ) {
            // 5.1) Pastikan semua Permission bernama dibuat
            $permMap = [];
            foreach ($allNamedPerms as $name) {
                $permMap[$name] = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => $guard]
                );
            }

            // 5.2) Pastikan semua Role dibuat
            $roleMap = [];
            foreach ($roles as $r) {
                $roleMap[$r] = Role::firstOrCreate(
                    ['name' => $r, 'guard_name' => $guard]
                );
            }

            // 5.3) Assign permission bernama sesuai matrix (belum termasuk view-dept-*)
            foreach ($roleMatrix as $roleName => $permList) {
                /** @var \Spatie\Permission\Models\Role|null $role */
                $role = $roleMap[$roleName] ?? null;
                if (!$role) continue;

                if ($permList === '*') {
                    // Director: semua permission bernama yang sudah dibuat
                    $role->syncPermissions(Permission::where('guard_name', $guard)->get());
                } else {
                    $assign = collect($permList)
                        ->map(fn($n) => $permMap[$n] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                    $role->syncPermissions($assign);
                }
            }

            // 5.4) Buat permission cakupan departemen: view-dept-{id} dan assign ke role terkait
            $kabagDeptIds = Department::whereIn('name', $deptNamesForKabag)->pluck('id');
            $mgrDeptIds   = Department::whereIn('name', $deptNamesForMgrHr)->pluck('id');

            $makeDeptPerm = function (int $deptId) use ($guard) {
                return Permission::firstOrCreate([
                    'name' => "view-dept-{$deptId}",
                    'guard_name' => $guard,
                ]);
            };

            $kabagDeptPerms = $kabagDeptIds->map(fn ($id) => $makeDeptPerm($id))->all();
            $mgrDeptPerms   = $mgrDeptIds->map(fn ($id) => $makeDeptPerm($id))->all();

            // givePermissionTo bisa nerima array/collection Permission model
            if (isset($roleMap['kabag_qc'])) {
                $roleMap['kabag_qc']->givePermissionTo($kabagDeptPerms);
            }
            if (isset($roleMap['manager_hr'])) {
                $roleMap['manager_hr']->givePermissionTo($mgrDeptPerms);
            }

            // Opsional: Director bisa melihat semua departemen (beri semua view-dept-*)
            if (isset($roleMap['director'])) {
                $allDeptIds = Department::pluck('id');
                $allDeptPerms = $allDeptIds->map(fn ($id) => $makeDeptPerm($id))->all();
                $roleMap['director']->givePermissionTo($allDeptPerms);
            }
        });

        // 6) Fallback: user tanpa role → director
        User::query()
            ->doesntHave('roles')
            ->each(fn (User $u) => $u->assignRole('director'));

        // 7) Reset cache lagi
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
