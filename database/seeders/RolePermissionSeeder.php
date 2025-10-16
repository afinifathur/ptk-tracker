<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = [
            'admin_qc','admin_hr','admin_k3',
            'kabag_qc','manager_hr',
            'director','auditor'
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // Permissions
        $perms = [
            'ptk.create','ptk.view','ptk.update',
            'ptk.approve','ptk.reject',
            'menu.queue','menu.recycle','menu.audit'
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Role–permission matrix
        Role::findByName('admin_qc')->givePermissionTo(['ptk.create','ptk.view','ptk.update']);
        Role::findByName('admin_hr')->givePermissionTo(['ptk.create','ptk.view','ptk.update']);
        Role::findByName('admin_k3')->givePermissionTo(['ptk.create','ptk.view','ptk.update']);
        Role::findByName('kabag_qc')->givePermissionTo(['ptk.view','ptk.approve','menu.queue']);
        Role::findByName('manager_hr')->givePermissionTo(['ptk.view','ptk.approve','menu.queue']);
        Role::findByName('director')->givePermissionTo(Permission::all());
        Role::findByName('auditor')->givePermissionTo(['ptk.view','menu.audit']);

        // Default role untuk user yang belum punya role
        foreach (User::all() as $u) {
            if ($u->roles()->count() === 0) {
                $u->assignRole('director');
            }
        }
    }
}
