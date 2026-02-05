<?php

use App\Models\PTK;
use App\Models\Department;
use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

// 1. Setup Dependencies
$dept = Department::firstOrCreate(
    ['name' => 'MAINTENANCE'],
    ['code' => 'MTC']
);

$user = User::firstOrCreate(
    ['email' => 'admin.mtc@example.com'],
    [
        'name' => 'Admin Maintenance',
        'password' => Hash::make('password'),
        'department_id' => $dept->id,
    ]
);
// Assign role if needed, assuming role seeding exists or manual assignment irrelevant for data display
// $user->assignRole('admin_mtc'); 

$cat = Category::firstOrCreate(['name' => 'Safety']);
$subcat = Subcategory::firstOrCreate(
    ['name' => 'Pelanggaran Area Kerja', 'category_id' => $cat->id]
);

// 2. Data PTK 1: Crane Hoist
$ptk1 = PTK::updateOrCreate(
    ['number' => 'PTK/MTC/2026/01/001'],
    [
        'title' => 'KERUSAKAN CRANE HOIST 5 TON DAN 3 TON',
        'status' => 'In Progress',
        'category_id' => $cat->id,
        'subcategory_id' => $subcat->id,
        'department_id' => $dept->id,
        'pic_user_id' => $user->id,
        'created_by' => $user->id,
        'due_date' => '2026-03-10',
        'form_date' => '2026-01-29',
        'description' => 'Kerusakan pada unit crane hoist kapasitas 5 ton dan 3 ton di area produksi.',
    ]
);

// MTC Detail 1
$detail1 = $ptk1->mtcDetail()->updateOrCreate(
    ['ptk_id' => $ptk1->id],
    [
        'machine_damage_desc' => "RUSAK PADA TEMBAGA DINAMO YANG SUDAH AUS DAN KURANG BERTENAGA DAN GEARBOX SUDAH RUSAK.\n\nKerusakan ini menyebabkan performa angkat menurun drastis, terutama saat beban penuh. Suara kasar terdengar dari area gearbox yang mengindikasikan keausan gear internal yang parah. Hal ini berpotensi menyebabkan downtime produksi jika tidak segera ditangani, mengingat crane ini merupakan alat vital untuk pemindahan material bahan baku ke mesin bubut.",
        'machine_stop_status' => 'partial',
        'problem_evaluation' => "Evaluasi teknis menunjukkan bahwa umur pakai dinamo sudah melebihi 5 tahun tanpa rewinding ulang. Selain itu, pelumasan pada gearbox ditemukan kering yang mempercepat keausan gigi. Diperlukan penggantian unit motor speed reducer sepaket untuk efisiensi waktu daripada perbaikan parsial.",
        'needs_sparepart' => true,
        'installation_date' => null, // Belum dipasang (karena sparepart baru requested/ordered)
        'repaired_by' => 'Tomo',
        'technical_notes' => "Unit lama sudah dibongkar. Menunggu sparepart datang untuk pemasangan plug and play. Jalur kelistrikan sudah diperiksa dan dalam kondisi baik.",
        'machine_status_after' => null,
        'trial_hours' => 0,
        'trial_result' => null,
    ]
);

// Spareparts 1
$detail1->spareparts()->delete();
$detail1->spareparts()->createMany([
    [
        'name' => 'MOTOR SPEED REDUCER 1.1KW*4P*M4*10T',
        'spec' => '1.1 KW',
        'qty' => 1,
        'supplier' => 'PT. Mega Tech',
        'order_date' => '2026-01-30',
        'status' => 'Requested',
        'est_arrival_date' => '2026-03-29',
        'actual_arrival_date' => '2026-02-27', // Aneh est > actual tapi sesuai request user dummy
    ],
    [
        'name' => 'MOTOR SPEED REDUCER 1.1KW*4P*M4*10T (Cadangan)',
        'spec' => '1.1 KW',
        'qty' => 1,
        'supplier' => 'PT. Mega Tech',
        'order_date' => '2026-01-30',
        'status' => 'Requested',
        'est_arrival_date' => '2026-03-29',
        'actual_arrival_date' => '2026-02-27',
    ]
]);

// 2. Data PTK 2: CNC Lathe (Full Cycle)
$ptk2 = PTK::updateOrCreate(
    ['number' => 'PTK/MTC/2026/01/002'],
    [
        'title' => 'KERUSAKAN MESIN BUBUT CNC HASIL PRODUKSI TIDAK PRESISI',
        'status' => 'Completed', // Status completed untuk tes tampilan full
        'category_id' => $cat->id,
        'subcategory_id' => $subcat->id,
        'department_id' => $dept->id,
        'pic_user_id' => $user->id,
        'created_by' => $user->id,
        'due_date' => '2026-02-15',
        'form_date' => '2026-01-10',
        'description' => 'Hasil bubutan mengalami penyimpangan dimensi sebesar 0.05mm pada diameter luar.',
    ]
);

// MTC Detail 2
$detail2 = $ptk2->mtcDetail()->updateOrCreate(
    ['ptk_id' => $ptk2->id],
    [
        'machine_damage_desc' => "Spindle utama mengalami getaran berlebih (vibrasi) saat putaran di atas 1500 RPM. Hasil pengukuran pada produk menunjukkan ovalitas dan kekasaran permukaan yang tidak masuk standar QC.\n\nDiduga bearing spindle mengalami kelelahan material akibat jam kerja tinggi tanpa interval cooling down yang cukup.",
        'machine_stop_status' => 'total',
        'problem_evaluation' => "Setelah pengecekan dial indicator, run-out spindle mencapai 0.08mm (standar max 0.01mm). Bearing harus diganti presisi tinggi. Ball screw sumbu X juga terdengar kasar, perlu pelumasan ulang dan adjustment preload.",
        'needs_sparepart' => true,
        'installation_date' => '2026-02-10',
        'repaired_by' => 'Vendor (PT. CNC Solusi)',
        'technical_notes' => "Penggantian Spindle Bearing Set (Front & Rear). Adjustment backlash pada sumbu X dan Z. Kalibrasi ulang geometri mesin menggunakan laser interferometer.",
        'machine_status_after' => 'normal',
        'trial_hours' => 24, // Running 24 jam
        'trial_result' => "Mesin dijalankan running test selama 24 jam non-stop dengan variasi RPM. Getaran sudah hilang. Suara halus.\n\nHasil pengukuran produk sample:\n1. Diameter luar: OK (masuk toleransi 0.005mm)\n2. Kekasaran (Ra): 0.4 micron (OK)\n\nMesin dinyatakan laik operasi kembali per tanggal 12 Feb 2026.",
    ]
);

// Spareparts 2
$detail2->spareparts()->delete();
$detail2->spareparts()->createMany([
    [
        'name' => 'Spindle Bearing Set NSK 7014',
        'spec' => 'P4 Precision',
        'qty' => 1,
        'supplier' => 'NSK Japan',
        'order_date' => '2026-01-12',
        'status' => 'Received',
        'est_arrival_date' => '2026-02-01',
        'actual_arrival_date' => '2026-02-08',
    ],
    [
        'name' => 'Grease Kluber Isoflex NBU 15',
        'spec' => '1 Kg Can',
        'qty' => 2,
        'supplier' => 'Lokal',
        'order_date' => '2026-01-12',
        'status' => 'Received',
        'est_arrival_date' => '2026-01-15',
        'actual_arrival_date' => '2026-01-14',
    ]
]);

echo "Dummy Data Created Successfully.\n";
