<?php

namespace App\Http\Controllers;

use App\Models\{PTK, Department, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\{PTKExport, RangeExport};
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExportController extends Controller
{
    public function excel(Request $request)
    {
        return Excel::download(new PTKExport($request), 'ptk.xlsx');
    }

    public function pdf(PTK $ptk)
    {
        $ptk->load(['attachments', 'pic', 'department', 'category']);

        $docHash = hash('sha256', json_encode([
            'id'          => $ptk->id,
            'number'      => $ptk->number,
            'status'      => $ptk->status,
            'due'         => $ptk->due_date?->format('Y-m-d'),
            'approved_at' => $ptk->approved_at?->format('c'),
            'updated_at'  => $ptk->updated_at?->format('c'),
        ]));

        // URL verifikasi + QR (data URI) untuk di-embed pada PDF
        $verifyUrl = route('verify.show', ['ptk' => $ptk->id, 'hash' => $docHash]);
        $qrBinary  = QrCode::format('png')->size(140)->generate($verifyUrl);
        $qrBase64  = 'data:image/png;base64,' . base64_encode($qrBinary);

        $pdf = Pdf::loadView('exports.ptk_pdf', [
            'ptk'       => $ptk,
            'docHash'   => $docHash,
            'qrBase64'  => $qrBase64,
            'verifyUrl' => $verifyUrl,
        ]);

        return $pdf->download('ptk-' . $ptk->number . '.pdf');
    }

    /**
     * FORM: kirim daftar kategori & departemen ke view
     */
    public function rangeForm()
    {
        $categories  = Category::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('exports.range_form', compact('categories', 'departments'));
    }

    /**
     * REPORT (HTML): dukung filter + rekap
     */
    public function rangeReport(Request $request)
    {
        $data = $request->validate([
            'start'         => ['required', 'date'],
            'end'           => ['required', 'date', 'after_or_equal:start'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status'        => ['nullable', 'in:Not Started,In Progress,Completed'],
        ]);

        // Query utama (list item)
        $q = PTK::with(['pic', 'department', 'category'])
            ->whereBetween('created_at', [$data['start'], $data['end']]);

        if (!empty($data['category_id']))   $q->where('category_id',   $data['category_id']);
        if (!empty($data['department_id'])) $q->where('department_id', $data['department_id']);
        if (!empty($data['status']))        $q->where('status',        $data['status']);

        $items = $q->get();

        // Rekap Top 3 Kategori (hormati filter lain)
        $byCategory = PTK::select('category_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']])
            ->when(!empty($data['department_id']), fn ($w) => $w->where('department_id', $data['department_id']))
            ->when(!empty($data['status']),        fn ($w) => $w->where('status', $data['status']))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Rekap Top 3 Departemen (hormati filter lain)
        $byDepartment = PTK::select('department_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$data['start'], $data['end']])
            ->when(!empty($data['category_id']),   fn ($w) => $w->where('category_id', $data['category_id']))
            ->when(!empty($data['status']),        fn ($w) => $w->where('status', $data['status']))
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Mapping nama
        $catNames  = Category::pluck('name', 'id');
        $deptNames = Department::pluck('name', 'id');

        $topCategories = $byCategory->map(fn ($r) => [
            'name'  => $catNames[$r->category_id] ?? '-',
            'total' => $r->total,
        ]);

        $topDepartments = $byDepartment->map(fn ($r) => [
            'name'  => $deptNames[$r->department_id] ?? '-',
            'total' => $r->total,
        ]);

        // SLA compliance: Completed dengan approved_at <= due_date
        $completed  = (clone $q)->where('status', 'Completed');
        $nCompleted = (clone $completed)->count();
        $nSlaOk     = (clone $completed)->whereColumn('approved_at', '<=', 'due_date')->count();
        $sla        = $nCompleted ? round($nSlaOk / max(1, $nCompleted) * 100, 1) : 0;

        // Overdue: Not Started / In Progress & due_date < today
        $overdue = (clone $q)
            ->whereIn('status', ['Not Started', 'In Progress'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        // Untuk label filter di view
        $categories  = Category::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('exports.range_report', compact(
            'items',
            'data',
            'topCategories',
            'topDepartments',
            'sla',
            'overdue',
            'categories',
            'departments'
        ));
    }

    /**
     * EXPORT EXCEL: dukung filter
     */
    public function rangeExcel(Request $request)
    {
        $data = $request->validate([
            'start'         => ['required', 'date'],
            'end'           => ['required', 'date', 'after_or_equal:start'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status'        => ['nullable', 'in:Not Started,In Progress,Completed'],
        ]);

        return Excel::download(
            new RangeExport(
                $data['start'],
                $data['end'],
                $data['category_id']   ?? null,
                $data['department_id'] ?? null,
                $data['status']        ?? null
            ),
            'ptk-range.xlsx'
        );
    }

    /**
     * EXPORT PDF: dukung filter + doc hash
     */
    public function rangePdf(Request $request)
    {
        $data = $request->validate([
            'start'         => ['required', 'date'],
            'end'           => ['required', 'date', 'after_or_equal:start'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status'        => ['nullable', 'in:Not Started,In Progress,Completed'],
        ]);

        $q = PTK::with(['pic', 'department', 'category'])
            ->whereBetween('created_at', [$data['start'], $data['end']]);

        if (!empty($data['category_id']))   $q->where('category_id',   $data['category_id']);
        if (!empty($data['department_id'])) $q->where('department_id', $data['department_id']);
        if (!empty($data['status']))        $q->where('status',        $data['status']);

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
