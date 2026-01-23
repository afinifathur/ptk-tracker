<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit; // jika model Audit kamu beda: use App\Models\Audit;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = Audit::query()->with(['user', 'auditable']);

        // Filters (opsional)
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('event')) {
            $q->where('event', $request->get('event'));
        }

        if ($request->filled('model')) {
            // dukung short name / potongan namespace model (mis. "PTK")
            $model = trim($request->get('model'));
            $q->where('auditable_type', 'like', "%{$model}%");
        }

        $audits = $q->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('audits.index', compact('audits'));
    }

    /**
     * Log khusus Approval / Reject (untuk Admin)
     */
    public function approvalLog(Request $request)
    {
        // Hanya user dengan role admin_* yang mestinya lihat ini (middleware/check role di view)
        // Kita cari audit trail dari model PTK
        $q = Audit::with(['user', 'auditable'])
            ->where('auditable_type', 'App\Models\PTK');

        // Filter: Cari (No PTK / Alasan)
        if ($term = $request->get('q')) {
            $q->where(function ($sub) use ($term) {
                // Cari by values (reason/number) -> JSON
                $sub->where('new_values', 'like', "%{$term}%")
                    ->orWhere('old_values', 'like', "%{$term}%");
            });
        }

        // Filter: User
        if ($uid = $request->get('user_id')) {
            $q->where('user_id', $uid);
        }

        // Filter: Aksi (Reject/Approve - kita filter kasar via 'event' update)
        // Sebenarnya filter spesifik 'reject' agak sulit di level SQL JSON, 
        // tapi kita filter 'updated' event umumnya.
        if ($aksi = $request->get('action')) {
            // Logic kasar untuk filter aksi bisa kita handle di SQL kalau database support JSON query
            // Atau sekadar filter event 'updated'
            $q->where('event', $aksi); // created, updated, deleted
        }

        $logs = $q->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // List user untuk dropdown filter (pass full object collection, view expects 'users' as collection of models)
        $users = \App\Models\User::orderBy('name')->get();

        return view('approval-log.index', compact('logs', 'users'));
    }
}
