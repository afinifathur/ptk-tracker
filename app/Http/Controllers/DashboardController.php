<?php

namespace App\Http\Controllers;

use App\Models\PTK;
use App\Support\DeptScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $to   = now()->endOfDay();
        $from = now()->subWeeks(26)->startOfWeek();   // ~6 bulan = 26 minggu

        // base query + scope departemen (non-director/auditor)
        $q = PTK::with(['department','category','subcategory']);
        $allowed = DeptScope::allowedDeptIds(auth()->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        // ===== Weekly trend (26 minggu) =====
        $rows = (clone $q)
            ->whereBetween('created_at', [$from, $to])
            ->get(['id','created_at']);

        // kelompokkan per awal minggu (Senin)
        $bucketed = $rows->groupBy(function($p){
            return Carbon::parse($p->created_at)->startOfWeek()->format('Y-m-d');
        })->map->count();

        // isi 26 minggu dengan nol jika tidak ada data
        $period = CarbonPeriod::create($from, '1 week', $to);
        $labels = [];
        $data   = [];
        foreach ($period as $wstart) {
            $key      = $wstart->format('Y-m-d');
            $labels[] = $wstart->format('W'); // nomor minggu 01..52
            $data[]   = (int)($bucketed[$key] ?? 0);
        }

        // ===== KPI ringkas =====
        $total      = (clone $q)->count();
        $inProgress = (clone $q)->where('status','In Progress')->count();
        $completed  = (clone $q)->where('status','Completed')->count();
        $overdue    = (clone $q)->where('status','!=','Completed')->whereDate('due_date','<', today())->count();

        // ===== SLA 6 bulan (Completed on time) =====
        $slBase    = (clone $q)->whereBetween('created_at', [$from,$to])->where('status','Completed');
        $slaOnTime = (clone $slBase)->whereColumn('approved_at','<=','due_date')->count();
        $slaTotal  = (clone $slBase)->count();
        $slaPct    = $slaTotal ? round($slaOnTime * 100 / $slaTotal, 1) : 0;

        // ===== Top 3 (6 bulan) =====
        $base6 = (clone $q)->whereBetween('created_at', [$from,$to]);

        $topDepartments = (clone $base6)->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')->orderByDesc('total')->take(3)->get()
            ->map(fn($r)=>['name'=>$r->department->name ?? '-', 'total'=>$r->total]);

        $topCategories = (clone $base6)->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')->orderByDesc('total')->take(3)->get()
            ->map(fn($r)=>['name'=>$r->category->name ?? '-', 'total'=>$r->total]);

        $topSubcategories = (clone $base6)->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as total')
            ->groupBy('subcategory_id')->orderByDesc('total')->take(3)->get()
            ->map(fn($r)=>['name'=>$r->subcategory->name ?? '-', 'total'=>$r->total]);

        // ===== Overdue Top 10 (belum Completed & due_date < today) =====
        $overdueTop = (clone $q)
            ->with(['pic','department'])
            ->where('status','!=','Completed')
            ->whereDate('due_date','<', today())
            ->orderBy('due_date')   // paling lama di atas
            ->take(10)
            ->get(['id','number','title','pic_user_id','department_id','due_date','status']);

        return view('dashboard', [
            'total'            => $total,
            'inProgress'       => $inProgress,
            'completed'        => $completed,
            'overdue'          => $overdue,
            'labels'           => $labels,
            'series'           => $data,
            'slaPct'           => $slaPct,
            'topDepartments'   => $topDepartments,
            'topCategories'    => $topCategories,
            'topSubcategories' => $topSubcategories,
            'from'             => $from,
            'to'               => $to,
            'overdueTop'       => $overdueTop,
        ]);
    }
}
