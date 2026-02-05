<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\{Role, Permission};
use App\Models\{User, Department};

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 0) Bersihkan cache Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        // =========================================================
        // 0.1) Pastikan Department "Maintenance" ada (karena dibutuhkan oleh kabag_mtc)
        // =========================================================
        Department::firstOrCreate(['name' => 'Maintenance']);

        // 1) Definisi Role (tambahkan admin_mtc, kabag_mtc)
        $roles = [
            'admin_qc',
            'admin_qc_flange',
            'admin_qc_fitting',
            'admin_hr',
            'admin_k3',
            'admin_mtc',    // NEW
            'kabag_qc',
            'kabag_mtc',    // NEW
            'manager_hr',
            'director',
            'auditor',
        ];

        // 2) Definisi permission "bernama" (global, tidak terkait dept)
        $adminPerms = ['ptk.create', 'ptk.update', 'ptk.delete', 'ptk.view', 'ptk.export', 'menu.settings'];
        $approverPerm = ['ptk.approve', 'ptk.reject', 'menu.queue'];
        $directorPerm = ['menu.recycle', 'menu.audit', 'ptk.restore', 'ptk.force'];
        $otherMenu = ['menu.queue', 'menu.recycle', 'menu.audit'];

        $allNamedPerms = collect()
            ->merge($adminPerms)
            ->merge($approverPerm)
            ->merge($directorPerm)
            ->merge($otherMenu)
            ->unique()->values();

        // 3) Matriks Role → Permission bernama (belum termasuk view-dept-*)
        $roleMatrix = [
            'admin_qc' => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_qc_flange' => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_qc_fitting' => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_hr' => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_k3' => array_values(array_unique(array_merge($adminPerms, $otherMenu))),
            'admin_mtc' => array_values(array_unique(array_merge($adminPerms, $otherMenu))), // NEW

            'kabag_qc' => array_values(array_unique(array_merge($adminPerms, $approverPerm))),
            'kabag_mtc' => array_values(array_unique(array_merge($adminPerms, $approverPerm))), // NEW
            'manager_hr' => array_values(array_unique(array_merge($adminPerms, $approverPerm))),

            'director' => '*', // semua permission terdaftar (ditambah view-dept-* di bawah)
            'auditor' => ['ptk.view', 'ptk.export', 'menu.audit'],
        ];

        // 4) Cakupan departemen berdasar NAMA FINAL di DB (hasil bersihmu)
        $deptNamesForKabagQC = ['QC FLANGE', 'QC FITTING'];
        $deptNamesForMgrHR = ['HR DAN K3'];
        $deptNamesForKabagMTC = ['Maintenance']; // NEW

        DB::transaction(function () use ($guard, $roles, $allNamedPerms, $roleMatrix, $deptNamesForKabagQC, $deptNamesForMgrHR, $deptNamesForKabagMTC) {
            // 4.1) Pastikan semua permission bernama terdaftar (dengan guard)
            $permMap = [];
            foreach ($allNamedPerms as $name) {
                $permMap[$name] = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => $guard]
                );
            }

            // 4.2) Pastikan semua role ada (dengan guard)
            $roleMap = [];
            foreach ($roles as $r) {
                $roleMap[$r] = Role::firstOrCreate(
                    ['name' => $r, 'guard_name' => $guard]
                );
            }

            // 4.3) Assign permission bernama sesuai matriks
            foreach ($roleMatrix as $roleName => $permList) {
                $role = $roleMap[$roleName] ?? null;
                if (!$role)
                    continue;

                if ($permList === '*') {
                    // director: semua permission bernama yang ada saat ini
                    $role->syncPermissions(Permission::where('guard_name', $guard)->get());
                } else {
                    $assign = collect($permList)
                        ->map(fn($n) => $permMap[$n] ?? null)
                        ->filter()
                        ->values();
                    $role->syncPermissions($assign);
                }
            }

            // 4.4) Buat permission "view-dept-{id}" dari ID final lalu assign
            $makeDeptPerm = function (int $deptId) use ($guard) {
                return Permission::firstOrCreate([
                    'name' => "view-dept-{$deptId}",
                    'guard_name' => $guard,
                ]);
            };

            // Ambil ID final berdasar nama
            $kabagDeptIds = Department::whereIn('name', $deptNamesForKabagQC)->pluck('id');
            $mgrDeptIds = Department::whereIn('name', $deptNamesForMgrHR)->pluck('id');
            $kabagMtcIds = Department::whereIn('name', $deptNamesForKabagMTC)->pluck('id'); // NEW

            // Buat permission view-dept-* untuk yang dibutuhkan
            $kabagDeptPerms = $kabagDeptIds->map(fn($id) => $makeDeptPerm($id));
            $mgrDeptPerms = $mgrDeptIds->map(fn($id) => $makeDeptPerm($id));
            $mtcDeptPerms = $kabagMtcIds->map(fn($id) => $makeDeptPerm($id));  // NEW

            // Assign ke role spesifik
            if (isset($roleMap['kabag_qc'])) {
                $roleMap['kabag_qc']->givePermissionTo($kabagDeptPerms);
            }
            if (isset($roleMap['manager_hr'])) {
                $roleMap['manager_hr']->givePermissionTo($mgrDeptPerms);
            }
            if (isset($roleMap['kabag_mtc'])) { // NEW
                $roleMap['kabag_mtc']->givePermissionTo($mtcDeptPerms);
            }

            // 4.5) (Opsional) Director bisa melihat SEMUA department
            // aktifkan kalau mau: berikan semua "view-dept-*"
            if (isset($roleMap['director'])) {
                $allDeptIds = Department::pluck('id');
                $allDeptPerms = $allDeptIds->map(fn($id) => $makeDeptPerm($id));
                $allDeptPerms = $allDeptPerms->concat($mtcDeptPerms)->unique('id'); // Update with new dept
                $roleMap['director']->givePermissionTo($allDeptPerms);
            }
        });

        // 5) (Opsional) User tanpa role diberi 'director'
        User::query()
            ->doesntHave('roles')
            ->each(fn(User $u) => $u->assignRole('director'));

        // 6) Bersihkan cache Spatie lagi
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
