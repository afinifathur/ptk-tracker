<?php

namespace App\Http\Controllers;

use App\Models\PTK;
use App\Services\PTKNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    /**
     * Approve PTK:
     * - Pastikan ada creator
     * - Tentukan role approver dari mapping role creator
     * - Validasi current user memiliki role approver tsb
     * - Isi nomor (jika kosong) dari service
     * - Set approver_id, approved_at, status = Completed (dan director_id jika applicable)
     */
    public function approve(Request $request, PTK $ptk)
    {
        // Pastikan PTK punya creator
        $creator = $ptk->creator ?? null; // relasi: belongsTo(User::class, 'created_by')
        if (!$creator) {
            abort(422, 'PTK tidak memiliki data pembuat (creator).');
        }

        // Tentukan role approver dari mapping role pertama si creator
        $map = config('ptk_roles.approval_map', []);
        $creatorPrimaryRole = $creator->getRoleNames()->first(); // contoh: 'admin_qc'
        $approverRole = $map[$creatorPrimaryRole] ?? ($map['*'] ?? 'director');

        // Hanya user dengan role approver yang boleh approve
        $user = $request->user();
        if (!$user->hasRole($approverRole)) {
            abort(403, "Aksi ditolak: approver untuk PTK ini harus role '{$approverRole}'.");
        }

        DB::transaction(function () use ($ptk, $user) {
            // Isi nomor hanya jika kosong (format handled by PTKNumberingService)
            if (empty($ptk->number)) {
                /** @var PTKNumberingService $svc */
                $svc = app(PTKNumberingService::class);
                // Contoh hasil: PTK/{DEPT}/2025/10/001
                $ptk->number = $svc->nextNumber($ptk->department_id, now());
            }

            // Set data approval final
            $ptk->approver_id = auth()->id();
            $ptk->approved_at = now();
            $ptk->status      = 'Completed';

            // Jika yang approve adalah director, simpan juga director_id (opsional)
            if ($user->hasRole('director')) {
                $ptk->director_id = auth()->id();
            }

            $ptk->save();
        });

        return back()->with('ok', 'PTK disetujui & dinomori.');
    }

    /**
     * Reject / kembalikan ke Not Started (reset approval).
     * Catatan: kolom number TIDAK diubah agar histori nomor tetap terjaga.
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
