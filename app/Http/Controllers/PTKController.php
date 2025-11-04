<?php
// app/Http/Controllers/PTKController.php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Imports\PTKImport;
use App\Models\{PTK, Department, Category, User};
use App\Services\AttachmentService;
use App\Support\DeptScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PTKController extends Controller
{
    private const PER_PAGE     = 20;
    private const KANBAN_LIMIT = 30;
    private const STATUSES     = ['Not Started', 'In Progress', 'Completed'];

    public function __construct(
        private readonly AttachmentService $attachments
    ) {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    # =========================================================
    # INDEX — daftar PTK (pakai visibleTo)
    # =========================================================
    public function index(Request $request): View
    {
        $ptks = PTK::with(['department','category','subcategory','pic'])
            ->visibleTo(auth()->user())
            ->latest('created_at')
            ->paginate(self::PER_PAGE);

        return view('ptk.index', compact('ptks'));
    }

    public function create(Request $request): View
    {
        return view('ptk.create', [
            // Semua departemen (admin bebas pilih)
            'departments'   => Department::orderBy('name')->pluck('name', 'id'),
            'categories'    => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    # =========================================================
    # STORE
    # =========================================================
    public function store(Request $request): RedirectResponse
    {
        // validasi (form_date wajib) — tidak membatasi department via DeptScope
        $data = $this->validatePayload($request);
        $data['created_by'] = $request->user()->id;

        // simpan (form_date ikut mass-assign)
        $ptk = PTK::create(collect($data)->except('attachments')->toArray());

        $this->handleAttachments($request, $ptk->id);

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK dibuat.');
    }

    public function show(PTK $ptk): View
    {
        $this->authorize('view', $ptk);

        $ptk->load([
            'pic:id,name',
            'department:id,name',
            'category:id,name',
            'subcategory:id,name',
            'creator:id,name',
            'attachments',
        ]);

        return view('ptk.show', compact('ptk'));
    }

    public function edit(Request $request, PTK $ptk): View
    {
        return view('ptk.edit', [
            'ptk'           => $ptk,
            // Semua departemen (admin bebas pilih)
            'departments'   => Department::orderBy('name')->pluck('name', 'id'),
            'categories'    => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    # =========================================================
    # UPDATE
    # =========================================================
    public function update(Request $request, PTK $ptk): RedirectResponse
    {
        // validasi (form_date wajib)
        $data = $this->validatePayload($request);

        // Auto-move ke In Progress saat evaluation terisi dan status masih Not Started
        if ($request->filled('evaluation') && $ptk->status === 'Not Started') {
            $data['status'] = 'In Progress';
        }

        // update (form_date ikut mass-assign)
        $ptk->update(collect($data)->except('attachments')->toArray());

        $this->handleAttachments($request, $ptk->id);

        return back()->with('ok', 'PTK diperbarui.');
    }

    # =========================================================
    # DESTROY (soft delete)
    # =========================================================
    public function destroy(PTK $ptk): RedirectResponse
    {
        try {
            $ptk->delete();

            return redirect()
                ->route('ptk.index')
                ->with('ok', 'PTK berhasil dipindahkan ke Recycle Bin.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus PTK: ' . $e->getMessage());
        }
    }

    # =========================================================
    # KANBAN — batasi 30 & urutan (pakai visibleTo)
    # =========================================================
    public function kanban(): View
    {
        $base = PTK::with(['department','pic'])
            ->visibleTo(auth()->user());

        $notStarted = (clone $base)
            ->where('status','Not Started')
            ->orderBy('created_at')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        $inProgress = (clone $base)
            ->where('status','In Progress')
            ->orderBy('created_at')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        $completed  = (clone $base)
            ->where('status','Completed')
            ->latest()
            ->limit(self::KANBAN_LIMIT)
            ->get();

        return view('ptk.kanban', compact('notStarted','inProgress','completed'));
    }

    public function quickStatus(Request $request, PTK $ptk): JsonResponse
    {
        $this->authorize('update', $ptk);

        $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $ptk->update(['status' => $request->string('status')]);

        return response()->json(['ok' => true]);
    }

    # =========================================================
    # QUEUE — antrian persetujuan (pakai visibleTo)
    # =========================================================
    public function queue(Request $request): View
    {
        $queue = PTK::with(['department','pic'])
            ->visibleTo(auth()->user())
            ->where('status','Completed')
            ->whereNull('number')      // belum dinomori = belum approve
            ->latest('updated_at')
            ->get();

        return view('ptk.queue', compact('queue'));
    }

    # =========================================================
    # RECYCLE BIN & RESTORE/FORCE DELETE (pakai visibleTo)
    # =========================================================
    public function recycle(Request $request): View
    {
        $trash = PTK::onlyTrashed()
            ->visibleTo(auth()->user())
            ->with(['department','pic'])
            ->latest('deleted_at')
            ->get();

        return view('ptk.recycle', compact('trash'));
    }

    public function restore(Request $request, string $id): RedirectResponse
    {
        $ptk = PTK::withTrashed()->findOrFail($id);
        $this->authorize('delete', $ptk);

        $ptk->restore();

        return back()->with('ok', 'PTK dipulihkan.');
    }

    public function forceDelete(Request $request, string $id): RedirectResponse
    {
        $ptk = PTK::withTrashed()->with('attachments')->findOrFail($id);
        $this->authorize('delete', $ptk);

        foreach ($ptk->attachments as $a) {
            if ($a->path) {
                Storage::disk('public')->delete($a->path);
            }
            $a->delete();
        }

        $ptk->forceDelete();

        return back()->with('ok', 'PTK dihapus permanen.');
    }

    # =========================================================
    # SUBMIT — ubah status ke Completed jika evaluasi sudah ada
    # =========================================================
    public function submit(PTK $ptk): RedirectResponse
    {
        $this->authorize('update', $ptk);

        if (empty($ptk->evaluation)) {
            return back()->with('error', 'PTK belum bisa disubmit — evaluasi masalah belum diisi.');
        }

        $ptk->update(['status' => 'Completed']);

        return redirect()
            ->route('ptk.show', $ptk)
            ->with('ok', 'PTK telah disubmit dan siap untuk approval.');
    }

    # =========================================================
    # IMPORT
    # =========================================================
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        Excel::import(new PTKImport, $request->file('file'));

        return back()->with('ok', 'Import selesai.');
    }

    # =========================================================
    # Helpers (private)
    # =========================================================

    /**
     * Kandidat PIC untuk form create/edit.
     * Jika DeptScope membatasi, hanya tampilkan user pada departemen yang diizinkan.
     */
    private function picCandidatesFor(Request $request)
    {
        $builder = User::query()
            ->orderBy('name')
            ->select(['id', 'name']);

        $allowedDeptIds = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowedDeptIds)) {
            $builder->whereIn('department_id', $allowedDeptIds);
        }

        return $builder->get();
    }

    private function validatePayload(Request $request): array
    {
        // Tidak menambah Rule::in($allowed) pada department_id (pilihan bebas).
        return $request->validate($this->rules());
    }

    /** Aturan validasi bersama untuk store/update. */
    private function rules(): array
    {
        return [
            'title'              => ['required','string','max:255'],
            'description'        => ['nullable','string'],
            'desc_nc'            => ['nullable','string'],        // renamed
            'evaluation'         => ['nullable','string'],
            'action_correction'  => ['nullable','string'],        // renamed
            'action_corrective'  => ['nullable','string'],        // renamed
            'category_id'        => ['required','exists:categories,id'],
            'subcategory_id'     => ['nullable','exists:subcategories,id'],
            'department_id'      => ['required','exists:departments,id'],
            'pic_user_id'        => ['required','exists:users,id'],
            'due_date'           => ['nullable','date'],
            'form_date'          => ['required','date'],          // <<< WAJIB
            'status'             => ['nullable', Rule::in(self::STATUSES)],
            'attachments.*'      => ['file','mimes:jpg,jpeg,png,pdf','max:5120'],
        ];
    }

    private function handleAttachments(Request $request, int $ptkId): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ((array) $request->file('attachments') as $file) {
            if ($file) {
                $this->attachments->handle($file, $ptkId);
            }
        }
    }
}
