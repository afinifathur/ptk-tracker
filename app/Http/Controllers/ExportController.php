<?php

namespace App\Http\Controllers;

use App\Models\{PTK, Department, Category, Subcategory};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\{PTKExport, RangeExport};
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    /**
     * Export seluruh PTK dalam Excel (dengan dukungan filter via request, jika ada).
     */
    public function excel(Request $request)
    {
        return Excel::download(new PTKExport($request), 'ptk.xlsx');
    }

    /**
     * Export satu PTK ke PDF.
     */
    public function pdf(PTK $ptk)
    {
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
     */
    public function rangeForm()
    {
        $categories    = Category::orderBy('name')->get(['id', 'name']);
        $departments   = Department::orderBy('name')->get(['id', 'name']);
        // bisa dipakai untuk render semua subkategori di form (opsional, karena ada API dependent dropdown juga)
        $subcategories = Subcategory::orderBy('name')->get(['id', 'name', 'category_id']);

        return view('exports.range_form', compact('categories', 'departments', 'subcategories'));
    }

    /**
     * Laporan HTML untuk rentang tanggal dengan filter + rekap Top 3.
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

        if (!empty($data['category_id']))    $q->where('category_id',    $data['category_id']);
        if (!empty($data['subcategory_id'])) $q->where('subcategory_id', $data['subcategory_id']);
        if (!empty($data['department_id']))  $q->where('department_id',  $data['department_id']);
        if (!empty($data['status']))         $q->where('status',         $data['status']);

        $items = $q->get();

        // Rekap Top 3 Category (terpengaruh filter lain)
        $byCategory = PTK::select('category_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']])
            ->when(!empty($data['department_id']),  fn ($w) => $w->where('department_id',  $data['department_id']))
            ->when(!empty($data['status']),         fn ($w) => $w->where('status',         $data['status']))
            ->when(!empty($data['subcategory_id']), fn ($w) => $w->where('subcategory_id', $data['subcategory_id']))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Rekap Top 3 Department (terpengaruh filter lain)
        $byDepartment = PTK::select('department_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']])
            ->when(!empty($data['category_id']),    fn ($w) => $w->where('category_id',    $data['category_id']))
            ->when(!empty($data['subcategory_id']), fn ($w) => $w->where('subcategory_id', $data['subcategory_id']))
            ->when(!empty($data['status']),         fn ($w) => $w->where('status',         $data['status']))
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Rekap Top 3 Subcategory (hanya jika ada subcategory di data, dan masih terpengaruh filter lain)
        $bySubcategory = PTK::select('subcategory_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']])
            ->when(!empty($data['category_id']),   fn ($w) => $w->where('category_id',   $data['category_id']))
            ->when(!empty($data['department_id']), fn ($w) => $w->where('department_id', $data['department_id']))
            ->when(!empty($data['status']),        fn ($w) => $w->where('status',        $data['status']))
            ->whereNotNull('subcategory_id')
            ->groupBy('subcategory_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Mapping id -> nama
        $catNames = Category::pluck('name', 'id');
        $deptNames = Department::pluck('name', 'id');
        $subNames = Subcategory::pluck('name', 'id');

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
        $base = PTK::whereBetween('created_at', [$data['start'], $data['end']])
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

        // Data referensi untuk filter di view (dropdown)
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
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

        return Excel::download(
            new RangeExport(
                $data['start'],
                $data['end'],
                $data['category_id']    ?? null,
                $data['department_id']  ?? null,
                $data['status']         ?? null,
                $data['subcategory_id'] ?? null
            ),
            'ptk-range.xlsx'
        );
    }

    /**
     * Export PDF laporan range + filter.
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
