<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PTK;
use App\Services\PTKNumberingService;
use Illuminate\Http\RedirectResponse;

class ApprovalController extends Controller
{
    /**
     * Approve PTK:
     * - Wajib lolos policy 'approve'
     * - Nomor otomatis dari PTKNumberingService
     * - Set approver & approved_at
     */
    public function approve(PTK $ptk, PTKNumberingService $svc): RedirectResponse
    {
        $this->authorize('approve', $ptk);

        // Generate nomor (mis: PTK/{DEPT}/{YY}/{MM}/{RUN})
        if (empty($ptk->number)) {
            $ptk->number = $svc->generate($ptk);
        }

        $ptk->approver_id = auth()->id();
        $ptk->approved_at = now();
        // Biasanya sudah 'Completed' saat masuk antrian; biarkan atau set ulang:
        $ptk->status = 'Completed';

        // Opsional: cap juga director_id jika yang approve adalah director
        if (auth()->user()?->hasRole('director')) {
            $ptk->director_id = auth()->id();
        }

        $ptk->save();

        return back()->with('ok', 'PTK sudah di-approve.');
    }

    /**
     * Reject PTK:
     * - Wajib lolos policy 'reject'
     * - Kembalikan status ke In Progress, kosongkan approved_at
     * - Nomor dibiarkan (tetap kosong jika belum dinomori)
     */
    public function reject(PTK $ptk): RedirectResponse
    {
        $this->authorize('reject', $ptk);

        $ptk->status       = 'In Progress';
        $ptk->approved_at  = null;
        $ptk->approver_id  = null;
        $ptk->director_id  = null;
        // $ptk->number tetap seperti adanya (biasanya masih kosong)
        $ptk->save();

        return back()->with('ok', 'PTK dikembalikan ke In Progress.');
    }
}
