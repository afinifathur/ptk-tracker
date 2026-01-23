<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit; // jika model Audit kamu beda: use App\Models\Audit;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = Audit::query()
            ->with(['user', 'auditable']);

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
}
