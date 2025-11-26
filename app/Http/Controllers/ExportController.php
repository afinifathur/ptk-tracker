<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\{PTK, Department, Category, Subcategory};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class ExportController extends Controller
{
    /**
     * Basis builder: selalu include relasi penting dan filter visibilitas user.
     */
    private function base(): Builder
    {
        return PTK::with(['department','category','subcategory','pic'])
            ->visibleTo(auth()->user());
    }

    /**
     * Bangun query laporan range yang SUDAH terfilter visibilitas user.
     * (Periode berbasis form_date)
     */
    private function buildRangeQuery(Request $r): Builder
    {
        $q = (clone $this->base());

        // Periode (form_date)
        $start = $r->input('start');
        $end   = $r->input('end');

        if ($start && $end) {
            $q->whereBetween('form_date', [$start, $end]);
        } elseif ($start) {
            $q->whereDate('form_date', '>=', $start);
        } elseif ($end) {
            $q->whereDate('form_date', '<=', $end);
        }

        // Filter entity
        if ($r->filled('category_id'))    $q->where('category_id', $r->category_id);
        if ($r->filled('subcategory_id')) $q->where('subcategory_id', $r->subcategory_id);
        if ($r->filled('department_id'))  $q->where('department_id', $r->department_id);

        // Status (termasuk Overdue) — pakai due_date/status
        if ($r->filled('status')) {
            if ($r->status === 'Overdue') {
                $q->where('status', '!=', 'Completed')
                  ->whereDate('due_date', '<', Carbon::today());
            } else {
                $q->where('status', $r->status);
            }
        }

        return $q;
    }

    private function rangeMeta(Request $r): array
    {
        return [
            'start'             => $r->input('start'),
            'end'               => $r->input('end'),
            'category_name'     => optional(Category::find($r->category_id))->name ?: 'Semua',
            'subcategory_name'  => optional(Subcategory::find($r->subcategory_id))->name ?: 'Semua',
            'department_name'   => optional(Department::find($r->department_id))->name ?: 'Semua',
            'status_label'      => $r->status ?: 'Semua',
        ];
    }

    /** 🔹 Export SELURUH PTK yang terlihat oleh user ke Excel (bukan range) */
    public function excel(Request $request)
    {
        $items = (clone $this->base())
            ->orderBy('created_at', 'desc')
            ->get();

        return Excel::download(new class($items) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle
        {
            public function __construct(public $items) {}
            public function array(): array {
                $rows = [];
                foreach ($this->items as $i) {
                    $rows[] = [
                        $i->number ?? '-',
                        $i->created_at?->format('Y-m-d'),
                        $i->title,
                        optional($i->pic)->name,
                        optional($i->department)->name,
                        optional($i->category)->name . ($i->subcategory ? ' / '.$i->subcategory->name : ''),
                        $i->status,
                        $i->due_date?->format('Y-m-d'),
                    ];
                }
                return $rows;
            }
            public function headings(): array {
                return ['Nomor','Tanggal','Judul','PIC','Departemen','Kategori','Status','Due'];
            }
            public function title(): string { return 'PTK'; }
        }, 'ptk.xlsx');
    }

    /**
     * Helper: bangun PDF DomPDF untuk sebuah PTK (mengembalikan instance PDF yang siap stream/download)
     *
     * @param PTK $ptk
     * @return \Barryvdh\DomPDF\PDF
     */
    private function buildPdfFor(PTK $ptk)
    {
        $ptk->load([
            'attachments', 'pic', 'department', 'category', 'subcategory',
            'creator', 'approver', 'director',
        ]);

        $docHash = hash('sha256', json_encode([
            'id'          => $ptk->id,
            'number'      => $ptk->number,
            'status'      => $ptk->status,
            'due'         => $ptk->due_date?->format('Y-m-d'),
            'approved_at' => $ptk->approved_at?->format('c'),
            'updated_at'  => $ptk->updated_at?->format('c'),
        ]));

        $verifyUrl = route('verify.show', ['ptk' => $ptk->id, 'hash' => $docHash]);

        try {
            $png = QrCode::format('png')->size(120)->generate($verifyUrl);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable $e) {
            $qrBase64 = null;
        }

        $companyLogoBase64 = $this->b64(public_path('brand/logo.png'));
        $signAdmin    = $this->b64(public_path('brand/signatures/admin.png'));
        $signApprover = $this->b64(public_path('brand/signatures/approver.png'));
        $signDirector = $this->b64(public_path('brand/signatures/director.png'));

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

        return $pdf;
    }

    /** 🔹 PREVIEW satu PTK (inline stream) */
    public function preview(PTK $ptk)
    {
        $this->authorize('download', $ptk);

        $pdf = $this->buildPdfFor($ptk);

        $name = preg_replace('/[\/\\\\]+/', '-', $ptk->number ?: 'PTK-'.$ptk->id) . '.pdf';
        return $pdf->stream($name);
    }

    /** 🔹 DOWNLOAD satu PTK (PDF) */
    public function pdf(PTK $ptk)
    {
        $this->authorize('download', $ptk);

        $pdf = $this->buildPdfFor($ptk);

        $name = preg_replace('/[\/\\\\]+/', '-', $ptk->number ?: 'PTK-'.$ptk->id) . '.pdf';
        return $pdf->download($name);
    }

    /**
     * Preview PDF (inline) berbasis ID — route: /exports/ptk/{id}/preview
     * Berguna ketika kamu ingin membuka PDF di tab baru berdasarkan id numerik
     */
    public function previewPdf($id)
    {
        $ptk = PTK::findOrFail($id);
        $this->authorize('download', $ptk);

        $pdf = $this->buildPdfFor($ptk);

        $name = preg_replace('/[\/\\\\]+/', '-', $ptk->number ?: 'PTK-'.$ptk->id) . '.pdf';
        return $pdf->stream($name);
    }

    /** Laporan Periode (form) */
    public function rangeForm()
    {
        $categories    = Category::orderBy('name')->get(['id', 'name']);
        $departments   = Department::orderBy('name')->get(['id', 'name']);
        $subcategories = Subcategory::orderBy('name')->get(['id', 'name', 'category_id']);

        return view('exports.range_form', compact('categories', 'departments', 'subcategories'));
    }

    // ====== RANGE REPORT — pakai buildRangeQuery (visibleTo & form_date) ======
    public function rangeReport(Request $r)
    {
        $items = $this->buildRangeQuery($r)
            ->orderBy('form_date','desc')
            ->get();

        return view('exports.range_report', [
            'items'         => $items,
            'from'          => $r->start ? Carbon::parse($r->start) : null,
            'to'            => $r->end   ? Carbon::parse($r->end)   : null,
            'categories'    => Category::all(),
            'subcategories' => Subcategory::all(),
            'departments'   => Department::all(),
            'selected'      => [
                'category_id'    => $r->category_id,
                'subcategory_id' => $r->subcategory_id,
                'department_id'  => $r->department_id,
                'status'         => $r->status,
            ],
        ] + $this->rangeMeta($r));
    }

    // ====== RANGE PDF — pakai buildRangeQuery (visibleTo & form_date) ======
    public function rangePdf(Request $r)
    {
        $items = $this->buildRangeQuery($r)
            ->orderBy('form_date','desc')
            ->get();
        $meta  = $this->rangeMeta($r);

        $pdf = Pdf::loadView('exports.range_pdf', [
            'items' => $items,
        ] + $meta)->setPaper('a4', 'portrait');

        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('defaultFont', 'DejaVu Sans');

        $fname = 'PTK-Range-'.($meta['start'] ?: 'all').'-'.($meta['end'] ?: 'all').'.pdf';
        return $pdf->download($fname);
    }

    // ====== RANGE EXCEL — pakai buildRangeQuery (visibleTo & form_date) ======
    public function rangeExcel(Request $r)
    {
        $items = $this->buildRangeQuery($r)
            ->orderBy('form_date','desc')
            ->get();
        $meta  = $this->rangeMeta($r);

        return Excel::download(new class($items, $meta) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle
        {
            public function __construct(public $items, public $meta) {}
            public function array(): array {
                $rows = [];
                foreach ($this->items as $i) {
                    $rows[] = [
                        $i->number ?? '-',
                        $i->form_date?->format('Y-m-d'),
                        $i->title,
                        optional($i->pic)->name,
                        optional($i->department)->name,
                        optional($i->category)->name . ($i->subcategory ? ' / '.$i->subcategory->name : ''),
                        $i->status,
                        $i->due_date?->format('Y-m-d'),
                    ];
                }
                return $rows;
            }
            public function headings(): array {
                return ['Nomor','Tanggal','Judul','PIC','Departemen','Kategori','Status','Due'];
            }
            public function title(): string { return 'PTK'; }
        }, 'PTK-Range-'.($meta['start'] ?: 'all').'-'.($meta['end'] ?: 'all').'.xlsx');
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
