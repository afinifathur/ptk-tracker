<?php

namespace App\Http\Controllers;

use App\Models\PTK;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ApprovalController extends Controller
{
    /**
     * Menyetujui PTK (Kabag) dan menandai sebagai selesai.
     */
    public function approve(Request $request, PTK $ptk): RedirectResponse
    {
        $ptk->update([
            'approver_id' => $request->user()->id,
            'approved_at' => now(),
            'status'      => 'Completed',
        ]);

        return back()->with('ok', 'PTK disetujui & selesai.');
    }

    /**
     * Menolak PTK dan mengembalikan untuk revisi.
     */
    public function reject(Request $request, PTK $ptk): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $ptk->update([
            'approver_id' => null,
            'approved_at' => null,
            'status'      => 'In Progress', // atau 'Not Started' sesuai kebijakan
        ]);

        // Catatan: simpan $request->reason ke kolom/riwayat jika diperlukan.

        return back()->with('ok', 'PTK dikembalikan untuk revisi.');
    }
}
