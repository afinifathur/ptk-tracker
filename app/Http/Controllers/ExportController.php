<?php

namespace App\Http\Controllers;

use App\Exports\{PTKExport, RangeExport};
use App\Models\{PTK, Department, Category, Subcategory};
use App\Support\DeptScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExportController extends Controller
{
    /** Terapkan DeptScope ke query builder */
    private function applyDeptScope(Request $request, $query)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $query->whereIn('department_id', $allowed);
        }
        return $query;
    }

    /** Export seluruh PTK ke Excel */
    public function excel(Request $request)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());
        return Excel::download(new PTKExport($request, $allowed), 'ptk.xlsx');
    }

    /** 🔹 Preview satu PTK ke PDF (inline, tanpa download) + set session flag */
    public function preview(Request $request, $ptkId)
    {
        // Ambil PTK + relasi yang diperlukan di template PDF
        $ptk = PTK::with([
            'attachments', 'pic', 'department', 'category', 'subcategory',
            'creator', 'approver', 'director',
        ])->findOrFail($ptkId);

        // Autorisasi & DeptScope check (konsisten dengan pdf())
        $this->authorize('view', $ptk);
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed) && !in_array($ptk->department_id, $allowed, true)) {
            abort(403);
        }

        // Hash dokumen (untuk QR + verifikasi)
        $docHash = hash('sha256', json_encode([
            'id'          => $ptk->id,
            'number'      => $ptk->number,
            'status'      => $ptk->status,
            'due'         => $ptk->due_date?->format('Y-m-d'),
            'approved_at' => $ptk->approved_at?->format('c'),
            'updated_at'  => $ptk->updated_at?->format('c'),
        ]));

        // URL verifikasi + QR code (opsional bila dipakai di view)
        $verifyUrl = route('verify.show', ['ptk' => $ptk->id, 'hash' => $docHash]);
        try {
            $png = QrCode::format('png')->size(120)->generate($verifyUrl);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable $e) {
            $qrBase64 = null;
        }

        // Logo & tanda tangan (opsional; sesuaikan dengan isi view)
        $companyLogoBase64 = $this->b64(public_path('brand/logo.png'));
        $signAdmin    = $this->b64(public_path('brand/signatures/admin.png'));
        $signApprover = $this->b64(public_path('brand/signatures/approver.png'));
        $signDirector = $this->b64(public_path('brand/signatures/director.png'));

        // Embed lampiran gambar (maks 6)
        $embeds = [];
        foreach ($ptk->attachments->take(6) as $att) {
            $mime = strtolower($att->mime ?? '');
            if (str_starts_with($mime, 'image/')) {
                $full = Storage::disk('public')->path($att->path);
                if (is_file($full)) {
                    $embeds[] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full));
                }
            }
        }

        // Render ke PDF (inline, tanpa download)
        $pdf = Pdf::loadView('exports.ptk_pdf', [
            'ptk'               => $ptk,
            'docHash'           => $docHash,
            'qrBase64'          => $qrBase64,
            'verifyUrl'         => $verifyUrl,
            'companyLogoBase64' => $companyLogoBase64,
            'signAdmin'         => $signAdmin,
            'signApprover'      => $ptk->approved_at ? $signApprover : null,
            'signDirector'      => $signDirector,
            'embeds'            => $embeds,
        ])->setPaper('a4', 'portrait');

        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('defaultFont', 'DejaVu Sans');

        // ✅ set flag "sudah preview" (dipakai untuk enable tombol Approve)
        session()->put("previewed_ptk.{$ptk->id}", now()->toDateTimeString());

        // Stream inline (Attachment=false → tampil di tab baru)
        $safe = $ptk->number ? preg_replace('/[\\\\\/]+/','-', $ptk->number) : "PTK-{$ptk->id}";
        return $pdf->stream("{$safe}.pdf", ['Attachment' => false]);
    }

    /** Export satu PTK ke PDF (download) */
    public function pdf(Request $request, PTK $ptk)
    {
        $this->authorize('view', $ptk);

        // DeptScope check
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed) && !in_array($ptk->department_id, $allowed, true)) {
            abort(403);
        }

        // Eager load relasi
        $ptk->load([
            'attachments', 'pic', 'department', 'category', 'subcategory',
            'creator', 'approver', 'director',
        ]);

        // Hash dokumen
        $docHash = hash('sha256', json_encode([
            'id'          => $ptk->id,
            'number'      => $ptk->number,
            'status'      => $ptk->status,
            'due'         => $ptk->due_date?->format('Y-m-d'),
            'approved_at' => $ptk->approved_at?->format('c'),
            'updated_at'  => $ptk->updated_at?->format('c'),
        ]));

        // URL verifikasi + QR code
        $verifyUrl = route('verify.show', ['ptk' => $ptk->id, 'hash' => $docHash]);
        try {
            $png = QrCode::format('png')->size(120)->generate($verifyUrl);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable $e) {
            $qrBase64 = null;
        }

        // Logo & tanda tangan
        $companyLogoBase64 = $this->b64(public_path('brand/logo.png'));
        $signAdmin    = $this->b64(public_path('brand/signatures/admin.png'));
        $signApprover = $this->b64(public_path('brand/signatures/approver.png'));
        $signDirector = $this->b64(public_path('brand/signatures/director.png'));

        // Embed lampiran gambar (maks 6)
        $embeds = [];
        foreach ($ptk->attachments->take(6) as $att) {
            $mime = strtolower($att->mime ?? '');
            if (str_starts_with($mime, 'image/')) {
                $full = Storage::disk('public')->path($att->path);
                if (is_file($full)) {
                    $embeds[] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full));
                }
            }
        }

        // Render PDF
        $pdf = Pdf::loadView('exports.ptk_pdf', [
            'ptk'               => $ptk,
            'docHash'           => $docHash,
            'qrBase64'          => $qrBase64,
            'verifyUrl'         => $verifyUrl,
            'companyLogoBase64' => $companyLogoBase64,
            'signAdmin'         => $signAdmin,
            'signApprover'      => $ptk->approved_at ? $signApprover : null,
            'signDirector'      => $signDirector,
            'embeds'            => $embeds,
        ])->setPaper('a4', 'portrait');

        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('defaultFont', 'DejaVu Sans');

        // Nama file aman
        $base = $ptk->number ?: Str::slug($ptk->title ?: '') ?: 'laporan-ptk';
        $safe = preg_replace('/[\\\\\/]+/', '-', $base);
        $fname = "ptk-{$safe}.pdf";

        return $pdf->download($fname);
    }

    /** Laporan Periode (form) */
    public function rangeForm()
    {
        $categories    = Category::orderBy('name')->get(['id', 'name']);
        $departments   = Department::orderBy('name')->get(['id', 'name']);
        $subcategories = Subcategory::orderBy('name')->get(['id', 'name', 'category_id']);

        return view('exports.range_form', compact('categories', 'departments', 'subcategories'));
    }

    /** Laporan HTML untuk rentang tanggal — kirim lookup lists + $items + Top 3 */
    public function rangeReport(Request $r)
    {
        $start = $r->input('start');
        $end   = $r->input('end');

        // Base query (lengkapi relasi untuk mapping nama)
        $q = PTK::with(['department', 'category', 'subcategory', 'pic']);

        // Filter tanggal
        if ($start) {
            $q->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $q->whereDate('created_at', '<=', $end);
        }

        // Filter lain (opsional)
        if ($r->filled('status'))        $q->where('status', $r->status);
        if ($r->filled('department_id')) $q->where('department_id', $r->department_id);
        if ($r->filled('category_id'))   $q->where('category_id', $r->category_id);
        if ($r->filled('subcategory_id'))$q->where('subcategory_id', $r->subcategory_id);

        // Scope per user
        $allowed = DeptScope::allowedDeptIds(auth()->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        // Data utama (urutkan by created_at untuk tabel)
        $ptks  = $q->orderBy('created_at')->get();
        $items = $ptks; // alias untuk kompatibilitas view lama

        // ===== Top 3 (reset order agar lolos ONLY_FULL_GROUP_BY) =====
        $qBase = clone $q;

        $topCategories = (clone $qBase)
            ->reorder()
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'name'  => optional($r->category)->name ?? '-',
                'total' => $r->total,
            ]);

        $topDepartments = (clone $qBase)
            ->reorder()
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'name'  => optional($r->department)->name ?? '-',
                'total' => $r->total,
            ]);

        $topSubcategories = (clone $qBase)
            ->reorder()
            ->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as total')
            ->groupBy('subcategory_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn($r) => [
                'name'  => optional($r->subcategory)->name ?? '-',
                'total' => $r->total,
            ]);

        // Lookup lists untuk view
        $categories    = Category::orderBy('name')->get(['id', 'name']);
        $departments   = Department::orderBy('name')->get(['id', 'name']);
        $subcategories = Subcategory::orderBy('name')->get(['id', 'name', 'category_id']);

        return view('exports.range_report', compact(
            'ptks',
            'items',
            'start',
            'end',
            'categories',
            'departments',
            'subcategories',
            'topCategories',
            'topDepartments',
            'topSubcategories'
        ));
    }

    /** Export Excel laporan range */
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
                $allowed                ?? []
            ),
            'ptk-range.xlsx'
        );
    }

    /** Export PDF laporan range */
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

        $pdf = Pdf::loadView('exports.range_pdf', compact('items', 'data', 'docHash'))
            ->setPaper('a4', 'portrait');

        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('defaultFont', 'DejaVu Sans');

        return $pdf->download('ptk-range-' . $data['start'] . '_to_' . $data['end'] . '.pdf');
    }

    /** Helper: baca file & kembalikan base64 */
    private function b64(?string $path): ?string
    {
        if (!$path || !is_file($path)) {
            return null;
        }

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            default       => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
