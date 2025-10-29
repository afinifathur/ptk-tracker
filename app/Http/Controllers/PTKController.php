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

    /**
     * Roles admin per-departemen yang dipaksa mengikuti department_id user.
     * Juga dipakai untuk membatasi kandidat PIC.
     */
    private array $deptAdminRoles = ['admin_qc_flange', 'admin_qc_fitting', 'admin_hr', 'admin_k3'];

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
        $q = PTK::visibleTo($request->user())
            ->with([
                'pic:id,name',
                'department:id,name',
                'category:id,name',
                'subcategory:id,name',
                'creator:id,name',
            ])
            ->latest();

        $q->when($request->filled('status'), fn ($w) =>
            $w->where('status', $request->string('status'))
        );

        $q->when($request->filled('q'), function ($w) use ($request) {
            $term = trim((string) $request->q);
            $w->where(fn ($s) =>
                $s->where('title', 'like', "%{$term}%")
                  ->orWhere('number', 'like', "%{$term}%")
            );
        });

        $ptks = $q->paginate(self::PER_PAGE)->withQueryString();

        return view('ptk.index', compact('ptks'));
    }

    public function create(Request $request): View
    {
        return view('ptk.create', [
            'departments'   => $this->departmentsFor($request),
            'categories'    => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    # =========================================================
    # STORE
    # =========================================================
    public function store(Request $request): RedirectResponse
    {
        // validasi (form_date wajib)
        $data = $this->validatePayload($request);

        // Paksa admin dept hanya ke departemennya
        if ($request->user()->hasAnyRole($this->deptAdminRoles)) {
            $data['department_id'] = $request->user()->department_id;
        }

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
            'departments'   => $this->departmentsFor($request),
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

        if ($request->user()->hasAnyRole($this->deptAdminRoles)) {
            $data['department_id'] = $request->user()->department_id;
        }

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
        $user = auth()->user();

        $base = PTK::visibleTo($user)
            ->with(['department:id,name','category:id,name','pic:id,name']);

        $notStarted = (clone $base)
            ->where('status', 'Not Started')
            ->orderBy('form_date', 'asc')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        $inProgress = (clone $base)
            ->where('status', 'In Progress')
            ->orderBy('form_date', 'asc')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        $completed = (clone $base)
            ->where('status', 'Completed')
            ->orderByRaw('COALESCE(approved_at, updated_at, created_at) DESC')
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
    # QUEUE — PTK Completed tanpa nomor (pakai visibleTo)
    # =========================================================
    public function queue(Request $request): View
    {
        $q = PTK::visibleTo($request->user())
            ->with(['pic:id,name', 'department:id,name'])
            ->where('status', 'Completed')
            ->whereNull('number')
            ->latest();

        $items = $q->paginate(self::PER_PAGE)->withQueryString();

        return view('ptk.queue', compact('items'));
    }

    # =========================================================
    # RECYCLE BIN & RESTORE/FORCE DELETE (pakai visibleTo)
    # =========================================================
    public function recycle(Request $request): View
    {
        $q = PTK::onlyTrashed()
            ->visibleTo($request->user())
            ->latest('deleted_at');

        $items = $q->paginate(self::PER_PAGE)->withQueryString();

        return view('ptk.recycle', compact('items'));
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
    /** Dropdown departemen di form (menghormati DeptScope). */
    private function departmentsFor(Request $request)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());

        return empty($allowed)
            ? Department::all()
            : Department::whereIn('id', $allowed)->get();
    }

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
        $allowed = DeptScope::allowedDeptIds($request->user());

        $rules = $this->rules();

        // batasi department sesuai DeptScope (untuk input form)
        if (!empty($allowed)) {
            /** @var array<int, mixed> $deptRule */
            $deptRule = $rules['department_id'];
            $deptRule[] = Rule::in($allowed);
            $rules['department_id'] = $deptRule;
        }

        return $request->validate($rules);
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
