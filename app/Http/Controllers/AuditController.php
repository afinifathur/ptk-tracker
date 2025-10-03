<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function index(Request $req)
    {
        $q = Audit::query()->latest();
        if ($req->filled('user')) $q->where('user_id',$req->user);
        if ($req->filled('event')) $q->where('event',$req->event); // created/updated/deleted/restored
        if ($req->filled('type'))  $q->where('auditable_type',$req->type);

        $audits = $q->paginate(50)->appends($req->query());
        $types  = Audit::select('auditable_type')->distinct()->pluck('auditable_type');
        return view('audits.index', compact('audits','types'));
    }
}
