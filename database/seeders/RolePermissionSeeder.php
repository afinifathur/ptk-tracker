<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role,Permission};
use App\Models\{User,Department};

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Dept master (QC, HR, K3)
        $qc = Department::firstOrCreate(['name'=>'QC']);
        $hr = Department::firstOrCreate(['name'=>'HR']);
        $k3 = Department::firstOrCreate(['name'=>'K3 & Lingkungan']);

        // Permissions (aksi)
        $perms = [
            'ptk.create','ptk.update','ptk.delete','ptk.restore','ptk.force',
            'ptk.approve','ptk.reject','ptk.export',
            'menu.queue','menu.recycle','menu.audit',
            // izin lihat per-departemen
            "view-dept-{$qc->id}", "view-dept-{$hr->id}", "view-dept-{$k3->id}",
            // read-only (auditor)
            'ptk.read-only'
        ];
        foreach ($perms as $p) Permission::firstOrCreate(['name'=>$p]);

        // Roles
        $adminQC   = Role::firstOrCreate(['name'=>'admin_qc']);
        $adminHR   = Role::firstOrCreate(['name'=>'admin_hr']);
        $adminK3   = Role::firstOrCreate(['name'=>'admin_k3']);
        $kabagQC   = Role::firstOrCreate(['name'=>'kabag_qc']);       // approver QC
        $managerHR = Role::firstOrCreate(['name'=>'manager_hr']);      // approver HR & K3
        $director  = Role::firstOrCreate(['name'=>'director']);
        $auditor   = Role::firstOrCreate(['name'=>'auditor']);

        // Attach permissions per role
        $adminQC   ->syncPermissions(['ptk.create','ptk.update','ptk.delete','ptk.export',"view-dept-{$qc->id}"]);
        $adminHR   ->syncPermissions(['ptk.create','ptk.update','ptk.delete','ptk.export',"view-dept-{$hr->id}"]);
        $adminK3   ->syncPermissions(['ptk.create','ptk.update','ptk.delete','ptk.export',"view-dept-{$k3->id}"]);

        $kabagQC   ->syncPermissions(['ptk.approve','ptk.reject','ptk.export','menu.queue',"view-dept-{$qc->id}"]);
        $managerHR ->syncPermissions(['ptk.approve','ptk.reject','ptk.export','menu.queue',"view-dept-{$hr->id}","view-dept-{$k3->id}"]);

        $director  ->syncPermissions(array_merge($perms, [])); // semua
        $auditor   ->syncPermissions(['ptk.export','menu.audit',"view-dept-{$qc->id}","view-dept-{$hr->id}","view-dept-{$k3->id}",'ptk.read-only']);

        // Users
        $u = fn($name,$email,$dept=null)=>User::firstOrCreate(
            ['email'=>$email],
            ['name'=>$name,'password'=>bcrypt('password'),'department_id'=>$dept?->id]
        );

        $U_adminQC   = $u('Admin QC','adminQC@peroniks.com',$qc)->assignRole($adminQC);
        $U_kabagQC   = $u('Kabag QC','kabagQC@peroniks.com',$qc)->assignRole($kabagQC);
        $U_adminHR   = $u('Admin HR','adminHR@peroniks.com',$hr)->assignRole($adminHR);
        $U_adminK3   = $u('Admin K3','adminK3@peroniks.com',$k3)->assignRole($adminK3);
        $U_managerHR = $u('Manager HR','managerHR@peroniks.com',$hr)->assignRole($managerHR);
        $U_director  = $u('Direktur','direktur@peroniks.com',null)->assignRole($director);
        $U_auditor   = $u('Auditor','auditor@peroniks.com',null)->assignRole($auditor);
    }
}
