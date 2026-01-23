<?php

namespace App\Http\Controllers;

use App\Models\PTK;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Http\Request;

class ApprovalLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit::query()
            ->with(['user', 'auditable'])
            ->where('auditable_type', PTK::class)
            ->where(function ($q) {
                // REJECT (punya last_reject_stage)
                $q->whereNotNull('new_values->last_reject_stage')

                  // APPROVE STAGE 1
                  ->orWhereNotNull('new_values->approved_stage1_at')

                  // APPROVE STAGE 2
                  ->orWhereNotNull('new_values->approved_stage2_at');
            });

        // ===============================
        // FILTER: SEARCH (PTK / ALASAN)
        // ===============================
        if ($search = $request->q) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('auditable', fn ($p) =>
                    $p->where('number', 'like', "%{$search}%")
                )
                ->orWhere('new_values->last_reject_reason', 'like', "%{$search}%");
            });
        }

        // ===============================
        // FILTER: USER
        // ===============================
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // ===============================
        // FILTER: AKSI
        // ===============================
        if ($action = $request->action) {
            match ($action) {
                'reject1'  => $query->where('new_values->last_reject_stage', 'stage1'),
                'reject2'  => $query->where('new_values->last_reject_stage', 'stage2'),
                'approve1' => $query->whereNotNull('new_values->approved_stage1_at'),
                'approve2' => $query->whereNotNull('new_values->approved_stage2_at'),
                default    => null,
            };
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        return view('approval-log.index', [
            'logs'  => $logs,
            'users' => \App\Models\User::orderBy('name')->get(),
        ]);
    }
}
