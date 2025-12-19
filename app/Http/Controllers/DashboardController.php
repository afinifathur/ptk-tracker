<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PTK;
use App\Models\User;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // =========================================================
        // Periode 26 minggu ke belakang
        // =========================================================
        $from = now()->subWeeks(26)->startOfWeek();
        $to   = now();

        // Base query (visibility)
        $base = PTK::visibleTo($user);

        // =========================================================
        // KPI Ringkas
        // =========================================================
        $total      = (clone $base)->count();
        $completed  = (clone $base)->where('status', 'Completed')->count();
        $inProgress = (clone $base)->where('status', 'In Progress')->count();
        $overdue    = (clone $base)
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->count();

        // =========================================================
        // Tren Mingguan (QC vs HR) — FIX FINAL
        // =========================================================
        $qcRoles = ['admin_qc_flange', 'admin_qc_fitting'];
        $hrRoles = ['admin_hr', 'admin_k3'];

        // Ambil user_id berdasarkan role
        $qcUserIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', $qcRoles))
            ->pluck('id');

        $hrUserIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', $hrRoles))
            ->pluck('id');

        // Base tren (periode sama, form_date)
        $trendBase = (clone $base)
            ->whereBetween('form_date', [
                $from->toDateString(),
                $to->toDateString(),
            ]);

        // QC series
        $seriesQcRaw = (clone $trendBase)
            ->whereIn('created_by', $qcUserIds)
            ->selectRaw('YEARWEEK(form_date, 3) as yw, COUNT(*) as c')
            ->groupBy('yw')
            ->pluck('c', 'yw');

        // HR series
        $seriesHrRaw = (clone $trendBase)
            ->whereIn('created_by', $hrUserIds)
            ->selectRaw('YEARWEEK(form_date, 3) as yw, COUNT(*) as c')
            ->groupBy('yw')
            ->pluck('c', 'yw');

        // Build minggu (26 minggu)
        $weeks = collect(CarbonPeriod::create($from, '1 week', $to))
            ->map(fn ($w) => $w->copy()->startOfWeek());

        $labels   = [];
        $seriesQc = [];
        $seriesHr = [];

        foreach ($weeks as $week) {
            $yw = (int) $week->format('oW');
            $labels[]   = $week->format('W');
            $seriesQc[] = (int) ($seriesQcRaw[$yw] ?? 0);
            $seriesHr[] = (int) ($seriesHrRaw[$yw] ?? 0);
        }

        // =========================================================
        // Penanda Bulan (Chart)
        // =========================================================
        $indoMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nop', 12 => 'Des',
        ];

        $monthMarks = [];
        $prevMonth  = null;

        foreach ($weeks as $week) {
            $m = (int) $week->format('n');
            $monthMarks[] = ($m !== $prevMonth) ? $indoMonths[$m] : '';
            $prevMonth = $m;
        }

        // =========================================================
        // SLA 6 Bulan Terakhir
        // =========================================================
        $slaBase = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'Completed');

        $slaOnTime = (clone $slaBase)
            ->whereColumn('approved_at', '<=', 'due_date')
            ->count();

        $slaTotal = (clone $slaBase)->count();
        $slaPct   = $slaTotal ? round($slaOnTime * 100 / $slaTotal, 1) : 0;

        // =========================================================
        // Top Department / Category / Subcategory (6 bulan)
        // =========================================================
        $base6 = (clone $base)->whereBetween('created_at', [$from, $to]);

        $topDepartments = (clone $base6)
            ->with('department:id,name')
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->department->name ?? '-',
                'total' => (int) $r->total,
            ]);

        $topCategories = (clone $base6)
            ->with('category:id,name')
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->category->name ?? '-',
                'total' => (int) $r->total,
            ]);

        $topSubcategories = (clone $base6)
            ->with('subcategory:id,name')
            ->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as total')
            ->groupBy('subcategory_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->subcategory->name ?? '-',
                'total' => (int) $r->total,
            ]);

        // =========================================================
        // Overdue PTK (Top 10)
        // =========================================================
        $overdueTop = (clone $base)
            ->with([
                'pic:id,name',
                'department:id,name',
                'category:id,name',
                'subcategory:id,name',
            ])
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // =========================================================
        // Donut: PTK per Departemen (6 bulan)
        // =========================================================
        $donutFrom = now()->subMonths(6)->startOfDay();
        $donutTo   = now()->endOfDay();

        $deptCounts = (clone $base)
            ->with('department:id,name')
            ->whereBetween('created_at', [$donutFrom, $donutTo])
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->department->name ?? '-',
                'total' => (int) $r->total,
            ]);

        // =========================================================
        // Render
        // =========================================================
        return view('dashboard', [
            'total'            => $total,
            'inProgress'       => $inProgress,
            'completed'        => $completed,
            'overdue'          => $overdue,

            // chart tren
            'labels'           => $labels,
            'seriesQc'         => $seriesQc,
            'seriesHr'         => $seriesHr,
            'monthMarks'       => $monthMarks,

            'slaPct'           => $slaPct,
            'topDepartments'   => $topDepartments,
            'topCategories'    => $topCategories,
            'topSubcategories' => $topSubcategories,
            'from'             => $from,
            'to'               => $to,
            'overdueTop'       => $overdueTop,

            // donut
            'deptLabels'       => $deptCounts->pluck('name')->toArray(),
            'deptSeries'       => $deptCounts->pluck('total')->toArray(),
            'donutFrom'        => $donutFrom,
            'donutTo'          => $donutTo,
        ]);
    }
}
