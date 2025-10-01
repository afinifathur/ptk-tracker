<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $kpi = [
            'total'      => 0,
            'inProgress' => 0,
            'completed'  => 0,
            'overdue'    => 0,
            'sla'        => 0,
        ];
        $trendLabels = [];
        $trendValues = [];
        $topDept = collect();
        $overdueList = collect();

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
            $slaOK     = (clone $PTK)->where('status','Completed')
                            ->whereColumn('approved_at','<=','due_date')->count();
            $kpi['sla'] = $completed ? round($slaOK / max(1,$completed) * 100, 1) : 0;

            $trend = (clone $PTK)
                ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total")
                ->where('created_at','>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('ym')->orderBy('ym')->get();
            $trendLabels = $trend->pluck('ym')->values()->all();
            $trendValues = $trend->pluck('total')->values()->all();

            if (class_exists(\App\Models\Department::class)) {
                $topDept = \App\Models\Department::query()
                    ->withCount('ptk')
                    ->orderByDesc('ptk_count')->limit(5)->get(['id','name']);
                // mini sparkline (count PTK 6 bulan per dept)
                $sparks = [];
                foreach ($topDept as $d) {
                    $rows = \App\Models\PTK::selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total")
                        ->where('department_id', $d->id)
                        ->where('created_at','>=', now()->subMonths(5)->startOfMonth())
                        ->groupBy('ym')->orderBy('ym')->get();
                    $sparks[$d->id] = [
                        'labels' => $rows->pluck('ym')->values()->all(),
                        'values' => $rows->pluck('total')->values()->all(),
                    ];
                }
                $topDept->each(function($d) use ($sparks){ $d->spark = $sparks[$d->id] ?? ['labels'=>[],'values'=>[]]; });
            }
        }

        return view('dashboard.index', compact('kpi','trendLabels','trendValues','topDept','overdueList'));
    }
}
