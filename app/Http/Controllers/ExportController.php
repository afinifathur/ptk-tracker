<?php

namespace App\Http\Controllers;

use App\Models\{PTK, Department, Category, Subcategory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\{PTKExport, RangeExport};
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\DeptScope; // <— tambahkan

class ExportController extends Controller
{
    /** Helper: terapkan scope department yang diizinkan ke query builder */
    private function applyDeptScope(Request $request, $query)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $query->whereIn('department_id', $allowed);
        }
        return $query;
    }

    /**
     * Export seluruh PTK dalam Excel (filter diteruskan ke PTKExport).
     * Pastikan PTKExport menerapkan DeptScope juga (lihat catatan di bawah).
     */
    public function excel(Request $request)
    {
        // Disarankan: modifikasi PTKExport agar menerima user/allowed dept
        $allowed = DeptScope::allowedDeptIds($request->user());
        return Excel::download(new PTKExport($request, $allowed), 'ptk.xlsx');
    }

    /**
     * Export satu PTK ke PDF.
     */
    public function pdf(Request $request, PTK $ptk)
    {
        // Pastikan user berhak melihat PTK ini (via policy) + cek dept scope
        $this->authorize('view', $ptk);

        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed) && !in_array($ptk->department_id, $allowed, true)) {
            abort(403);
        }

        $ptk->load(['attachments', 'pic', 'department', 'category', 'subcategory']);

        $docHash = hash('sha256', json_encode([
            'id'         => $ptk->id,
            'number'     => $ptk->number,
            'status'     => $ptk->status,
            'due'        => $ptk->due_date?->format('Y-m-d'),
            'approved_at'=> $ptk->approved_at?->format('c'),
            'updated_at' => $ptk->updated_at?->format('c'),
        ]));

        $pdf = Pdf::loadView('exports.ptk_pdf', compact('ptk', 'docHash'));

        return $pdf->download('ptk-' . $ptk->number . '.pdf');
    }

    /**
     * Form laporan rentang tanggal + filter tambahan.
     * Batasi daftar department sesuai DeptScope.
     */
    public function rangeForm(Request $request)
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);

        $deptQ = Department::orderBy('name')->select(['id', 'name']);
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $deptQ->whereIn('id', $allowed);
        }
        $departments = $deptQ->get();

        $subcategories = Subcategory::orderBy('name')->get(['id', 'name', 'category_id']);

        return view('exports.range_form', compact('categories', 'departments', 'subcategories'));
    }

    /**
     * Laporan HTML untuk rentang tanggal dengan filter + rekap Top 3.
     * Semua query dibatasi DeptScope.
     */
    public function rangeReport(Request $request)
    {
        $data = $request->validate([
            'start'          => ['required', 'date'],
            'end'            => ['required', 'date', 'after_or_equal:start'],
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'department_id'  => ['nullable', 'integer', 'exists:departments,id'],
            'status'         => ['nullable', 'in:Not Started,In Progress,Completed'],
        ]);

        // Data utama
        $q = PTK::with(['pic', 'department', 'category', 'subcategory'])
            ->whereBetween('created_at', [$data['start'], $data['end']]);

        $this->applyDeptScope($request, $q);

        if (!empty($data['category_id']))    $q->where('category_id',    $data['category_id']);
        if (!empty($data['subcategory_id'])) $q->where('subcategory_id', $data['subcategory_id']);
        if (!empty($data['department_id']))  $q->where('department_id',  $data['department_id']);
        if (!empty($data['status']))         $q->where('status',         $data['status']);

        $items = $q->get();

        // Rekap Top 3 Category
        $byCategory = PTK::select('category_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']]);
        $this->applyDeptScope($request, $byCategory);
        $byCategory
            ->when(!empty($data['department_id']),  fn ($w) => $w->where('department_id',  $data['department_id']))
            ->when(!empty($data['status']),         fn ($w) => $w->where('status',         $data['status']))
            ->when(!empty($data['subcategory_id']), fn ($w) => $w->where('subcategory_id', $data['subcategory_id']))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3);
        $byCategory = $byCategory->get();

        // Rekap Top 3 Department
        $byDepartment = PTK::select('department_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']]);
        $this->applyDeptScope($request, $byDepartment);
        $byDepartment
            ->when(!empty($data['category_id']),    fn ($w) => $w->where('category_id',    $data['category_id']))
            ->when(!empty($data['subcategory_id']), fn ($w) => $w->where('subcategory_id', $data['subcategory_id']))
            ->when(!empty($data['status']),         fn ($w) => $w->where('status',         $data['status']))
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->limit(3);
        $byDepartment = $byDepartment->get();

        // Rekap Top 3 Subcategory
        $bySubcategory = PTK::select('subcategory_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']]);
        $this->applyDeptScope($request, $bySubcategory);
        $bySubcategory
            ->when(!empty($data['category_id']),   fn ($w) => $w->where('category_id',   $data['category_id']))
            ->when(!empty($data['department_id']), fn ($w) => $w->where('department_id', $data['department_id']))
            ->when(!empty($data['status']),        fn ($w) => $w->where('status',        $data['status']))
            ->whereNotNull('subcategory_id')
            ->groupBy('subcategory_id')
            ->orderByDesc('total')
            ->limit(3);
        $bySubcategory = $bySubcategory->get();

        // Mapping id -> nama
        $catNames  = Category::pluck('name', 'id');
        $deptNames = Department::pluck('name', 'id');
        $subNames  = Subcategory::pluck('name', 'id');

        $topCategories = $byCategory->map(fn ($r) => [
            'name'  => $catNames[$r->category_id] ?? '-',
            'total' => $r->total,
        ])->values();

        $topDepartments = $byDepartment->map(fn ($r) => [
            'name'  => $deptNames[$r->department_id] ?? '-',
            'total' => $r->total,
        ])->values();

        $topSubcategories = $bySubcategory->map(fn ($r) => [
            'name'  => $subNames[$r->subcategory_id] ?? '-',
            'total' => $r->total,
        ])->values();

        // KPI sederhana: SLA & Overdue
        $base = PTK::whereBetween('created_at', [$data['start'], $data['end']]);
        $this->applyDeptScope($request, $base);
        $base
            ->when(!empty($data['category_id']),    fn ($w) => $w->where('category_id',    $data['category_id']))
            ->when(!empty($data['subcategory_id']), fn ($w) => $w->where('subcategory_id', $data['subcategory_id']))
            ->when(!empty($data['department_id']),  fn ($w) => $w->where('department_id',  $data['department_id']))
            ->when(!empty($data['status']),         fn ($w) => $w->where('status',         $data['status']));

        $nCompleted = (clone $base)->where('status', 'Completed')->count();
        $nSlaOk     = (clone $base)->where('status', 'Completed')
            ->whereColumn('approved_at', '<=', 'due_date')
            ->count();

        $sla = $nCompleted ? round($nSlaOk / max(1, $nCompleted) * 100, 1) : 0;

        $overdue = (clone $base)
            ->whereIn('status', ['Not Started', 'In Progress'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        // Data referensi untuk filter di view (dropdown) — batasi departments juga
        $categories = Category::orderBy('name')->get(['id', 'name']);

        $deptQ = Department::orderBy('name')->select(['id','name']);
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $deptQ->whereIn('id', $allowed);
        }
        $departments = $deptQ->get();

        $subcategories = Subcategory::when(!empty($data['category_id']), fn ($q) => $q->where('category_id', $data['category_id']))
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        return view('exports.range_report', compact(
            'items',
            'data',
            'topCategories',
            'topDepartments',
            'topSubcategories',
            'sla',
            'overdue',
            'categories',
            'departments',
            'subcategories'
        ));
    }

    /**
     * Export Excel laporan range + filter.
     * Disarankan menambahkan $allowed ke RangeExport dan terapkan di query export.
     */
    public function rangeExcel(Request $request)
    {
        $data = $request->validate([
            'start'          => ['required', 'date'],
            'end'            => ['required', 'date', 'after_or_equal:start'],
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'department_id'  => ['nullable', 'integer', 'exists:departments,id'],
            'status'         => ['nullable', 'in:Not Started,In Progress,Completed'],
        ]);

        $allowed = DeptScope::allowedDeptIds($request->user());

        return Excel::download(
            new RangeExport(
                $data['start'],
                $data['end'],
                $data['category_id']    ?? null,
                $data['department_id']  ?? null,
                $data['status']         ?? null,
                $data['subcategory_id'] ?? null,
                $allowed                ?? [] // <— tambahkan argumen opsional di RangeExport
            ),
            'ptk-range.xlsx'
        );
    }

    /**
     * Export PDF laporan range + filter (dibatasi DeptScope).
     */
    public function rangePdf(Request $request)
    {
        $data = $request->validate([
            'start'          => ['required', 'date'],
            'end'            => ['required', 'date', 'after_or_equal:start'],
            'category_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'department_id'  => ['nullable', 'integer', 'exists:departments,id'],
            'status'         => ['nullable', 'in:Not Started,In Progress,Completed'],
        ]);

        $q = PTK::with(['pic', 'department', 'category', 'subcategory'])
            ->whereBetween('created_at', [$data['start'], $data['end']]);
        $this->applyDeptScope($request, $q);

        if (!empty($data['category_id']))    $q->where('category_id',    $data['category_id']);
        if (!empty($data['subcategory_id'])) $q->where('subcategory_id', $data['subcategory_id']);
        if (!empty($data['department_id']))  $q->where('department_id',  $data['department_id']);
        if (!empty($data['status']))         $q->where('status',         $data['status']);

        $items = $q->get();

        $docHash = hash('sha256', json_encode([
            'range' => $data,
            'count' => $items->count(),
            'ts'    => now()->format('c'),
        ]));

        $pdf = Pdf::loadView('exports.range_pdf', compact('items', 'data', 'docHash'));

        return $pdf->download('ptk-range-' . $data['start'] . '_to_' . $data['end'] . '.pdf');
    }
}
