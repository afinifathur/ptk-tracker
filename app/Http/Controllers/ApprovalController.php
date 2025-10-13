<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PTK;
use App\Services\PTKNumberingService;

class ApprovalController extends Controller
{
    /**
     * Assign nomor saat pertama kali approve (jika belum ada),
     * lalu set status Completed + approver + approved_at.
     */
    public function approve(Request $request, PTK $ptk)
    {
        // Jika PTK belum memiliki nomor final, generate dari service
        if (empty($ptk->number)) {
            /** @var PTKNumberingService $svc */
            $svc = app(PTKNumberingService::class);
            // gunakan departemen PTK + waktu sekarang (atau bisa pakai approved_at nanti)
            $ptk->number = $svc->nextNumber($ptk->department_id, now());
        }

        // Set approval final
        $ptk->approver_id = auth()->id();   // user yang menyetujui
        $ptk->approved_at = now();
        $ptk->status      = 'Completed';

        $ptk->save();

        return back()->with('ok', 'PTK disetujui & dinomori.');
    }

    /**
     * Kembalikan ke Not Started (reset approval).
     * Catatan: "number" TIDAK disentuh agar histori nomor tidak rusak
     * kalau sebelumnya sudah pernah dinomori—atur sesuai kebijakanmu.
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
