<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PTK;

class ApprovalController extends Controller
{
    public function approve(Request $request, PTK $ptk)
    {
        // Sederhana: jika belum ada approver_id -> isi; kalau sudah ada -> director_id
        if (is_null($ptk->approver_id)) {
            $ptk->approver_id = auth()->id();
            // bisa sekaligus ubah status
            if ($ptk->status === 'Not Started') $ptk->status = 'In Progress';
        } else {
            $ptk->director_id = auth()->id();
            if ($ptk->status !== 'Completed') {
                $ptk->status = 'Completed';
                $ptk->approved_at = now();
            }
        }
        $ptk->save();
        return back()->with('ok','PTK di-approve.');
    }

    public function reject(Request $request, PTK $ptk)
    {
        $request->validate(['reason'=>'nullable|string|max:500']);
        // Reset persetujuan dan kembalikan ke Not Started
        $ptk->update([
            'approver_id'=>null,
            'director_id'=>null,
            'approved_at'=>null,
            'status'=>'Not Started',
        ]);
        // TODO: simpan reason ke kolom/riwayat bila dibutuhkan.
        return back()->with('ok','PTK dikembalikan untuk revisi.');
    }
}
