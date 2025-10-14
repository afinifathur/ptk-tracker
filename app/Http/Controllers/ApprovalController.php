<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PTK;
use App\Services\PTKNumberingService;

class ApprovalController extends Controller
{
    /**
     * Assign nomor saat pertama kali approve (jika belum ada),
     * tentukan approver berdasarkan role pembuat (mapping),
     * verifikasi bahwa current user memang role approver tsb,
     * lalu set status Completed + approver + approved_at.
     */
    public function approve(Request $request, PTK $ptk)
    {
        // pastikan PTK punya creator
        $creator = $ptk->creator ?? null; // relasi belongsTo(User::class, 'created_by')
        if (!$creator) {
            abort(422, 'PTK tidak memiliki data pembuat (creator).');
        }

        // Ambil role approver dari config mapping berbasis role pertama si creator
        $map = config('ptk_roles.approval_map', []);
        $creatorPrimaryRole = $creator->getRoleNames()->first(); // contoh: 'admin_qc'
        $approverRole = $map[$creatorPrimaryRole] ?? ($map['*'] ?? 'director');

        // Hanya user dengan role approver yang boleh approve
        $user = $request->user();
        if (!$user->hasRole($approverRole)) {
            abort(403, "Aksi ditolak: approver untuk PTK ini harus role '{$approverRole}'.");
        }

        DB::transaction(function () use ($ptk, $user) {
            // Jika PTK belum memiliki nomor final, generate dari service
            if (empty($ptk->number)) {
                /** @var PTKNumberingService $svc */
                $svc = app(PTKNumberingService::class);
                // gunakan departemen PTK + waktu sekarang (atau bisa pakai approved_at nanti)
                $ptk->number = $svc->nextNumber($ptk->department_id, now());
            }

            // Set approval final
            $ptk->approver_id = $user->id; // user yang menyetujui
            $ptk->approved_at = now();
            $ptk->status      = 'Completed';

            // Jika yang approve adalah director, isi juga director_id (opsional)
            if ($user->hasRole('director')) {
                $ptk->director_id = $user->id;
            }

            $ptk->save();
        });

        return back()->with('ok', 'PTK disetujui & dinomori.');
    }

    /**
     * Kembalikan ke Not Started (reset approval).
     * Catatan: "number" TIDAK disentuh agar histori nomor tidak rusak.
     */
    public function reject(Request $request, PTK $ptk)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $ptk->update([
            'approver_id' => null,
            'director_id' => null,
            'approved_at' => null,
            'status'      => 'Not Started',
        ]);

        // TODO: simpan $request->reason ke kolom/riwayat jika diperlukan.

        return back()->with('ok', 'PTK dikembalikan untuk revisi.');
    }
}
