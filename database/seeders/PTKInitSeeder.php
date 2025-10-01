<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\{User, Department, Category, PTK};

class PTKInitSeeder extends Seeder
{
    public function run(): void
    {
        // Departemen
        $deptNames = ['Flange','Fitting','HR & K3','Produksi','QA/QC','PPIC'];
        $departments = collect($deptNames)->map(fn($n)=>Department::firstOrCreate(['name'=>$n]));

        // Kategori
        $catNames = ['Quality','Delivery','Safety','Cost','Morale','Environment'];
        $categories = collect($catNames)->map(fn($n)=>Category::firstOrCreate(['name'=>$n]));

        // Pastikan ada user PIC
        $pic = User::first() ?? User::factory()->create([
            'name'=>'Director','email'=>'admin@example.com','password'=>bcrypt('password')
        ]);

        // 10 dummy PTK
        for ($i=1; $i<=10; $i++) {
            $dept = $departments->random();
            $cat  = $categories->random();
            $status = Arr::random(['Not Started','In Progress','Completed']);
            $due = now()->addDays(rand(-10, 20))->toDateString();

            PTK::updateOrCreate(
                ['number' => 'PTK-'.str_pad((string)$i, 3, '0', STR_PAD_LEFT)],
                [
                    'title'        => 'Contoh PTK #'.$i,
                    'description'  => Str::of('Deskripsi singkat PTK #'.$i.' terkait isu dan rencana tindakan.')->toString(),
                    'category_id'  => $cat->id,
                    'department_id'=> $dept->id,
                    'pic_user_id'  => $pic->id,
                    'status'       => $status,
                    'due_date'     => $due,
                    'approved_at'  => $status==='Completed' ? now()->subDays(rand(0,5)) : null,
                ]
            );
        }
    }
}
