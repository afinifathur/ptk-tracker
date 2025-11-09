<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PTK;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Periode 26 minggu ke belakang (untuk tren mingguan & KPI lainnya)
        $from = now()->copy()->subWeeks(26)->startOfWeek();
        $to   = now();

        // Semua data disaring lewat scope visibleTo()
        $base = PTK::visibleTo($user);

        // =========================================================
        // KPI ringkas
        // =========================================================
        $total      = (clone $base)->count();
        $completed  = (clone $base)->where('status', 'Completed')->count();
        $inProgress = (clone $base)->where('status', 'In Progress')->count();
        $overdue    = (clone $base)
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->count();

        // =========================================================
        // Tren mingguan (26 minggu) — basis: form_date
        // =========================================================
        $seriesRaw = (clone $base)
            ->whereBetween('form_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('YEARWEEK(form_date, 3) as yw, COUNT(*) as c')
            ->groupBy('yw')
            ->pluck('c', 'yw'); // contoh: [202401 => 5, 202402 => 3, ...]

        $weeks = collect(CarbonPeriod::create($from, '1 week', $to))
            ->map(fn($w) => $w->copy()->startOfWeek());

        $labels = [];
        $data   = [];

        foreach ($weeks as $week) {
            $yw = (int) $week->format('oW'); // format kunci YEARWEEK
            $labels[] = $week->format('W');
            $data[]   = (int) ($seriesRaw[$yw] ?? 0);
        }

        // =========================================================
        // Penanda bulan (untuk chart)
        // =========================================================
        $indoMonths = [1 => 'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nop','Des'];
        $monthMarks = [];
        $prevMonth  = null;

        foreach ($weeks as $week) {
            $m = (int) $week->format('n');
            $monthMarks[] = ($m !== $prevMonth) ? $indoMonths[$m] : '';
            $prevMonth = $m;
        }

        // =========================================================
        // SLA 6 bulan terakhir (Completed On Time)
        // =========================================================
        $slaBase = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'Completed');

        $slaOnTime = (clone $slaBase)->whereColumn('approved_at', '<=', 'due_date')->count();
        $slaTotal  = (clone $slaBase)->count();
        $slaPct    = $slaTotal ? round($slaOnTime * 100 / $slaTotal, 1) : 0;

        // =========================================================
        // Top 3 Department, Category, Subcategory (6 bulan)
        // =========================================================
        $base6 = (clone $base)->whereBetween('created_at', [$from, $to]);

        $topDepartments = (clone $base6)
            ->with('department:id,name')
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn($r) => [
                'name'  => $r->department->name ?? '-',
                'total' => (int) $r->total,
            ]);

        $topCategories = (clone $base6)
            ->with('category:id,name')
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn($r) => [
                'name'  => $r->category->name ?? '-',
                'total' => (int) $r->total,
            ]);

        $topSubcategories = (clone $base6)
            ->with('subcategory:id,name')
            ->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as total')
            ->groupBy('subcategory_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn($r) => [
                'name'  => $r->subcategory->name ?? '-',
                'total' => (int) $r->total,
            ]);

        // =========================================================
        // Overdue PTK (Top 10)
        // =========================================================
        $overdueTop = (clone $base)
            ->with(['pic:id,name', 'department:id,name', 'category:id,name', 'subcategory:id,name'])
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->take(10)
            ->get([
                'id', 'number', 'title', 'created_at', 'pic_user_id',
                'department_id', 'category_id', 'subcategory_id',
                'status', 'due_date',
            ]);

        // =========================================================
        // DONUT: Jumlah PTK per Departemen (6 bulan terakhir)
        // =========================================================
        $donutTo   = now()->endOfDay();
        $donutFrom = now()->copy()->subMonths(6)->startOfDay();

        // Hitung per departemen menggunakan builder yang sudah tervisibilitas (tanpa N+1)
        $deptCounts = (clone $base)
            ->with('department:id,name')
            ->whereBetween('created_at', [$donutFrom, $donutTo])
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'name'  => $r->department->name ?? '-',
                'total' => (int) $r->total,
            ]);

        // Siapkan data untuk Chart.js (labels & data series)
        $deptLabels = $deptCounts->pluck('name')->toArray();
        $deptSeries = $deptCounts->pluck('total')->toArray();

        // Jika ingin batasi, contoh top 6:
        // $deptLabels = array_slice($deptLabels, 0, 6);
        // $deptSeries = array_slice($deptSeries, 0, 6);

        // =========================================================
        // Render ke View
        // =========================================================
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

            // --- donut chart ---
            'deptLabels'       => $deptLabels,
            'deptSeries'       => $deptSeries,
            'donutFrom'        => $donutFrom,
            'donutTo'          => $donutTo,
        ]);
    }
}
