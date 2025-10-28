<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PTK;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ===============================
        // Periode 26 minggu ke belakang
        // ===============================
        $now  = now();
        $to   = $now->copy()->endOfDay();
        $from = $now->copy()->subWeeks(26)->startOfWeek(); // ~6 bulan (mulai Senin)

        // Base query harus lewat scope visibleTo
        $base = PTK::visibleTo($user)->with(['department', 'category', 'subcategory']);

        // ===============================
        // KPI Ringkas
        // ===============================
        $total      = (clone $base)->count();
        $completed  = (clone $base)->where('status', 'Completed')->count();
        $inProgress = (clone $base)->where('status', 'In Progress')->count();
        $overdue    = (clone $base)
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->count();

        // ===============================
        // Tren Mingguan (26 minggu)
        // ===============================
        $seriesRaw = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('YEARWEEK(created_at, 3) as yw, COUNT(*) as c')
            ->groupBy('yw')
            ->pluck('c', 'yw'); // hasil: [202401 => 5, 202402 => 3, ...]

        $weeks = collect(CarbonPeriod::create($from, '1 week', $to))
            ->map(fn ($w) => $w->copy()->startOfWeek());

        $labels = [];
        $data   = [];

        foreach ($weeks as $week) {
            $yw = $week->format('oW'); // kombinasi tahun + minggu ISO
            $labels[] = $week->format('W');
            $data[] = (int)($seriesRaw[$yw] ?? 0);
        }

        // ===============================
        // Penanda bulan (ID) untuk chart
        // ===============================
        $indoMonths = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nop', 'Des'];
        $monthMarks = [];
        $prevMonth  = null;

        foreach ($weeks as $week) {
            $m = (int)$week->format('n');
            if ($m !== $prevMonth) {
                $monthMarks[] = $indoMonths[$m];
                $prevMonth    = $m;
            } else {
                $monthMarks[] = '';
            }
        }

        // ===============================
        // SLA 6 Bulan (Completed On Time)
        // ===============================
        $slaBase   = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'Completed');

        $slaOnTime = (clone $slaBase)->whereColumn('approved_at', '<=', 'due_date')->count();
        $slaTotal  = (clone $slaBase)->count();
        $slaPct    = $slaTotal ? round($slaOnTime * 100 / $slaTotal, 1) : 0;

        // ===============================
        // Top 3 Department, Category, Subcategory (6 Bulan)
        // ===============================
        $base6 = (clone $base)->whereBetween('created_at', [$from, $to]);

        $topDepartments = (clone $base6)
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->department->name ?? '-',
                'total' => (int)$r->total,
            ]);

        $topCategories = (clone $base6)
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->category->name ?? '-',
                'total' => (int)$r->total,
            ]);

        $topSubcategories = (clone $base6)
            ->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as total')
            ->groupBy('subcategory_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->subcategory->name ?? '-',
                'total' => (int)$r->total,
            ]);

        // ===============================
        // Overdue PTK (Top 10)
        // ===============================
        $overdueTop = (clone $base)
            ->with(['pic:id,name', 'department:id,name', 'category:id,name', 'subcategory:id,name'])
            ->where('status', '!=', 'Completed')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date') // paling lama di atas
            ->take(10)
            ->get([
                'id','number','title','created_at','pic_user_id',
                'department_id','category_id','subcategory_id',
                'status','due_date',
            ]);

        // ===============================
        // Render ke View
        // ===============================
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
