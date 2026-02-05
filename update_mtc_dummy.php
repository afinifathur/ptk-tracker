<?php

use App\Models\PTK;
use Carbon\Carbon;

// 1. PTK 001: Crane Hoist (Sudah created, update texts only if needed)
$ptk1 = PTK::where('number', 'PTK/MTC/2026/01/001')->first();
if ($ptk1 && $ptk1->mtcDetail) {
    // Ensure long text for testing UI
    $ptk1->mtcDetail->update([
        'machine_damage_desc' => "RUSAK PADA TEMBAGA DINAMO YANG SUDAH AUS DAN KURANG BERTENAGA DAN GEARBOX SUDAH RUSAK.\n\nKerusakan ini menyebabkan performa angkat menurun drastis, terutama saat beban penuh. Suara kasar terdengar dari area gearbox yang mengindikasikan keausan gear internal yang parah.",
        'problem_evaluation' => "Evaluasi teknis menunjukkan bahwa umur pakai dinamo sudah melebihi 5 tahun tanpa rewinding ulang. Selain itu, pelumasan pada gearbox ditemukan kering yang mempercepat keausan gigi. Diperlukan penggantian unit motor speed reducer sepaket untuk efisiensi waktu daripada perbaikan parsial.\n\nRisiko kecelakaan kerja tinggi jika dipaksakan beroperasi.",
        'technical_notes' => "Unit lama sudah dibongkar. Menunggu sparepart datang untuk pemasangan plug and play. Jalur kelistrikan sudah diperiksa dan dalam kondisi baik. Dudukan mounting perlu dimodifikasi sedikit agar motor baru bisa masuk.",
        'trial_result' => "Belum ada hasil uji coba karena sparepart belum terpasang. Estimasi running test akan dilakukan 2 jam setelah pemasangan selesai.",
    ]);
    echo "PTK 001 Updated.\n";
}

// 2. PTK 002: CNC Lathe (Update to match user reguest: 4 Spareparts, Status In Progress)
$ptk2 = PTK::where('number', 'PTK/MTC/2026/01/002')->first();
if ($ptk2 && $ptk2->mtcDetail) {

    $ptk2->update([
        'title' => 'KERUSAKAN MESIN BUBUT CNC HASIL PRODUKSI TIDAK PRESISI',
        'status' => 'In Progress',
        'due_date' => '2026-02-15',
        'form_date' => '2026-01-10',
        'description' => 'Hasil bubutan mengalami penyimpangan dimensi sebesar 0.05mm pada diameter luar.',
    ]);

    $ptk2->mtcDetail->update([
        'machine_damage_desc' => "Spindle utama mengalami getaran berlebih (vibrasi) saat putaran di atas 1500 RPM. Hasil pengukuran pada produk menunjukkan ovalitas dan kekasaran permukaan yang tidak masuk standar QC.\n\nDiduga bearing spindle mengalami kelelahan material akibat jam kerja tinggi tanpa interval cooling down yang cukup.",
        'machine_stop_status' => 'partial',
        'problem_evaluation' => "Setelah pengecekan dial indicator, run-out spindle mencapai 0.08mm (standar max 0.01mm). Bearing harus diganti presisi tinggi. Ball screw sumbu X juga terdengar kasar, perlu pelumasan ulang dan adjustment preload.",
        'needs_sparepart' => true,
        'installation_date' => '2026-02-10',
        'repaired_by' => 'Tomo',
        'technical_notes' => "Mesin sudah dipasang dengan baik. Penggantian Spindle Bearing Set (Front & Rear) telah dilakukan. Adjustment backlash pada sumbu X dan Z serta kalibrasi ulang geometri mesin menggunakan laser interferometer telah diselesaikan.",
        'machine_status_after' => 'normal',
        'trial_hours' => 1,
        'trial_result' => "Masih ada suara di gearbox.\n\nHasil pengamatan: Getaran berkurang 80%, namun pada RPM rendah (500) masih terdengar dengung halus dari gearbox utama. Dimensi produk masuk toleransi, tetapi perlu monitoring suhu gearbox.",
    ]);

    // Update Spareparts: 4 Items (Duplicate existing 2)
    $ptk2->mtcDetail->spareparts()->delete();
    $ptk2->mtcDetail->spareparts()->createMany([
        [
            'name' => 'MOTOR SPEED REDUCER 1.1KW*4P*M4*10T',
            'spec' => '1.1 KW',
            'qty' => 1,
            'supplier' => 'PT. Mega Tech',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-31',
            'actual_arrival_date' => '2026-02-07',
        ],
        [
            'name' => 'Grease Kluber Isoflex NBU 15',
            'spec' => '1 Kg Can',
            'qty' => 2,
            'supplier' => 'Lokal',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-14',
            'actual_arrival_date' => '2026-01-13',
        ],
        // Duplicates as requested
        [
            'name' => 'MOTOR SPEED REDUCER 1.1KW*4P*M4*10T',
            'spec' => '1.1 KW',
            'qty' => 1,
            'supplier' => 'PT. Mega Tech',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-31',
            'actual_arrival_date' => '2026-02-07',
        ],
        [
            'name' => 'Grease Kluber Isoflex NBU 15',
            'spec' => '1 Kg Can',
            'qty' => 2,
            'supplier' => 'Lokal',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-14',
            'actual_arrival_date' => '2026-01-13',
        ]
    ]);

    echo "PTK 002 Updated with 4 Spareparts.\n";
}
