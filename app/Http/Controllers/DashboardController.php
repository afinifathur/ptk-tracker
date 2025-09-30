// app/Http/Controllers/DashboardController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Default angka aman kalau model belum ada
        $kpi = [
            'total'      => 0,
            'inProgress' => 0,
            'completed'  => 0,
            'overdue'    => 0,
            'sla'        => 0,
        ];
        $trendLabels = [];
        $trendValues = [];
        $topDept = [];
        $overdueList = [];

        // Jika model PTK tersedia, kalkulasi beneran
        if (class_exists(\App\Models\PTK::class)) {
            $PTK = \App\Models\PTK::query();

            $kpi['total']      = (clone $PTK)->count();
            $kpi['inProgress'] = (clone $PTK)->where('status','In Progress')->count();
            $kpi['completed']  = (clone $PTK)->where('status','Completed')->count();

            $overdueQuery = (clone $PTK)
                ->whereIn('status',['Not Started','In Progress'])
                ->whereDate('due_date','<', now()->toDateString());

            $kpi['overdue'] = $overdueQuery->count();
            $overdueList    = $overdueQuery->with(['pic','department'])->latest()->limit(10)->get();

            $completed = (clone $PTK)->where('status','Completed')->count();
            $slaOK = (clone $PTK)->where('status','Completed')
                ->whereColumn('approved_at','<=','due_date')->count();
            $kpi['sla'] = $completed ? round($slaOK / max(1,$completed) * 100, 1) : 0;

            // Tren 6 bulan terakhir
            $trend = (clone $PTK)
                ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total")
                ->where('created_at','>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('ym')->orderBy('ym')->get();
            $trendLabels = $trend->pluck('ym')->values()->all();
            $trendValues = $trend->pluck('total')->values()->all();

            // Top 5 departemen
            if (class_exists(\App\Models\Department::class)) {
                $topDept = \App\Models\Department::withCount('ptk')
                    ->orderByDesc('ptk_count')->limit(5)->get(['name','id','ptk_count']);
            }
        }

        return view('dashboard.index', compact('kpi','trendLabels','trendValues','topDept','overdueList'));
    }
}
