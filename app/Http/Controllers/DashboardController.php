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
        // Periode 26 minggu ke belakang hingga akhir hari ini
        $now  = now();
        $to   = $now->copy()->endOfDay();
        $from = $now->copy()->subWeeks(26)->startOfWeek(); // ~6 bulan = 26 minggu (mulai Senin)

        // Base query + scope departemen (non-director/auditor)
        $q = PTK::with(['department', 'category', 'subcategory']);
        $allowed = DeptScope::allowedDeptIds(auth()->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        // ===== Weekly trend (26 minggu) =====
        // Ambil data created_at dalam rentang
        $rows = (clone $q)
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'created_at']);

        // Hitung per awal minggu (Senin)
        $bucketed = $rows->groupBy(function ($p) {
            return Carbon::parse($p->created_at)->startOfWeek()->format('Y-m-d');
        })->map->count();

        // Siapkan daftar minggu agar bisa dipakai untuk labels, series, dan monthMarks
        $weeks = collect(CarbonPeriod::create($from, '1 week', $to))
            ->map(fn ($w) => $w->copy()->startOfWeek());

        // Labels (nomor minggu 01..52) & data
        $labels = [];
        $data   = [];
        foreach ($weeks as $wstart) {
            $key      = $wstart->format('Y-m-d');
            $labels[] = $wstart->format('W');
            $data[]   = (int)($bucketed[$key] ?? 0);
        }

        // ===== Penanda bulan (ID) untuk setiap minggu =====
        $indoMonths = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Ags', 'Sep', 'Okt', 'Nop', 'Des'];
        $monthMarks = [];
        $prevMonth  = null;
        foreach ($weeks as $wstart) {
            $m = (int)$wstart->format('n');
            if ($m !== $prevMonth) {
                $monthMarks[] = $indoMonths[$m];
                $prevMonth    = $m;
            } else {
                $monthMarks[] = '';
            }
        }

        // ===== KPI ringkas =====
        $total      = (clone $q)->count();
        $inProgress = (clone $q)->where('status', 'In Progress')->count();
        $completed  = (clone $q)->where('status', 'Completed')->count();
        $overdue    = (clone $q)->where('status', '!=', 'Completed')
                                ->whereDate('due_date', '<', today())
                                ->count();

        // ===== SLA 6 bulan (Completed on time) =====
        $slBase    = (clone $q)->whereBetween('created_at', [$from, $to])->where('status', 'Completed');
        $slaOnTime = (clone $slBase)->whereColumn('approved_at', '<=', 'due_date')->count();
        $slaTotal  = (clone $slBase)->count();
        $slaPct    = $slaTotal ? round($slaOnTime * 100 / $slaTotal, 1) : 0;

        // ===== Top 3 (6 bulan) =====
        $base6 = (clone $q)->whereBetween('created_at', [$from, $to]);

        $topDepartments = (clone $base6)->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')->orderByDesc('total')->take(3)->get()
            ->map(fn ($r) => ['name' => $r->department->name ?? '-', 'total' => (int)$r->total]);

        $topCategories = (clone $base6)->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')->orderByDesc('total')->take(3)->get()
            ->map(fn ($r) => ['name' => $r->category->name ?? '-', 'total' => (int)$r->total]);

        $topSubcategories = (clone $base6)->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as total')
            ->groupBy('subcategory_id')->orderByDesc('total')->take(3)->get()
            ->map(fn ($r) => ['name' => $r->subcategory->name ?? '-', 'total' => (int)$r->total]);

        // ===== Overdue PTK (Top 10) – lengkap relasi utk tabel =====
        $overdueTop = (clone $q)
            ->with(['pic:id,name', 'department:id,name', 'category:id,name', 'subcategory:id,name'])
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date') // paling lama di atas
            ->take(10)
            ->get([
                'id','number','title','created_at','pic_user_id','department_id',
                'category_id','subcategory_id','status','due_date'
            ]);

        return view('dashboard', [
            'total'            => $total,
            'inProgress'       => $inProgress,
            'completed'        => $completed,
            'overdue'          => $overdue,
            'labels'           => $labels,
            'series'           => $data,
            'monthMarks'       => $monthMarks,
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
