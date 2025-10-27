<?php
// app/Http/Controllers/PTKController.php

namespace App\Http\Controllers;

use App\Imports\PTKImport;
use App\Models\{PTK, Department, Category, User};
use App\Services\AttachmentService;
use App\Support\DeptScope;
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
     */
    private array $deptAdminRoles = ['admin_qc', 'admin_hr', 'admin_k3'];

    public function __construct(
        private readonly AttachmentService $attachments
    ) {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    # =========================================================
    # INDEX — daftar PTK (sudah pakai visibleTo)
    # =========================================================
    public function index(Request $request)
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

    public function create(Request $request)
    {
        return view('ptk.create', [
            'departments' => $this->departmentsFor($request),
            'categories'  => Category::all(),
            'users'       => User::select('id', 'name')->get(),
        ]);
    }

    # =========================================================
    # STORE
    # =========================================================
    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        // Paksa admin dept hanya ke departemennya
        if ($request->user()->hasAnyRole($this->deptAdminRoles)) {
            $data['department_id'] = $request->user()->department_id;
        }

        $data['created_by'] = $request->user()->id;

        $ptk = PTK::create(collect($data)->except('attachments')->toArray());

        $this->handleAttachments($request, $ptk->id);

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK dibuat.');
    }

    public function show(PTK $ptk)
    {
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

    public function edit(Request $request, PTK $ptk)
    {
        return view('ptk.edit', [
            'ptk'         => $ptk,
            'departments' => $this->departmentsFor($request),
            'categories'  => Category::all(),
            'users'       => User::select('id', 'name')->get(),
        ]);
    }

    # =========================================================
    # UPDATE
    # =========================================================
    public function update(Request $request, PTK $ptk)
    {
        $data = $this->validatePayload($request);

        if ($request->user()->hasAnyRole($this->deptAdminRoles)) {
            $data['department_id'] = $request->user()->department_id;
        }

        // Opsi perapihan status otomatis
        if ($request->filled('evaluation') && $ptk->status === 'Not Started') {
            $data['status'] = 'In Progress';
        }

        $ptk->update(collect($data)->except('attachments')->toArray());

        $this->handleAttachments($request, $ptk->id);

        return back()->with('ok', 'PTK diperbarui.');
    }

    # =========================================================
    # DESTROY (soft delete)
    # =========================================================
    public function destroy(PTK $ptk)
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
    # KANBAN — batasi 30 & urutan (sudah pakai visibleTo)
    # =========================================================
    public function kanban()
    {
        $user = auth()->user();

        $base = PTK::visibleTo($user)
            ->with(['department:id,name','category:id,name','pic:id,name']);

        $notStarted = (clone $base)
            ->where('status', 'Not Started')
            ->orderBy('created_at', 'asc')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        $inProgress = (clone $base)
            ->where('status', 'In Progress')
            ->orderBy('created_at', 'asc')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        $completed = (clone $base)
            ->where('status', 'Completed')
            ->orderByRaw('COALESCE(approved_at, updated_at, created_at) DESC')
            ->limit(self::KANBAN_LIMIT)
            ->get();

        return view('ptk.kanban', compact('notStarted','inProgress','completed'));
    }

    public function quickStatus(Request $request, PTK $ptk)
    {
        $this->authorize('update', $ptk);

        $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $ptk->update(['status' => $request->string('status')]);

        return response()->json(['ok' => true]);
    }

    # =========================================================
    # QUEUE — PTK Completed tanpa nomor (sudah pakai visibleTo)
    # =========================================================
    public function queue(Request $request)
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
    # RECYCLE BIN & RESTORE/FORCE DELETE (sudah pakai visibleTo)
    # =========================================================
    public function recycle(Request $request)
    {
        $q = PTK::onlyTrashed()
            ->visibleTo($request->user())
            ->latest('deleted_at');

        $items = $q->paginate(self::PER_PAGE)->withQueryString();

        return view('ptk.recycle', compact('items'));
    }

    public function restore(Request $request, $id)
    {
        $ptk = PTK::withTrashed()->findOrFail($id);
        $this->authorize('delete', $ptk);

        $ptk->restore();

        return back()->with('ok', 'PTK dipulihkan.');
    }

    public function forceDelete(Request $request, $id)
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
    public function submit(PTK $ptk)
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
    public function import(Request $request)
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
    private function departmentsFor(Request $request)
    {
        // Tetap gunakan DeptScope untuk dropdown departemen di form
        $allowed = DeptScope::allowedDeptIds($request->user());

        return empty($allowed)
            ? Department::all()
            : Department::whereIn('id', $allowed)->get();
    }

    private function validatePayload(Request $request): array
    {
        $allowed = DeptScope::allowedDeptIds($request->user());

        $rules = $this->rules();

        // batasi department sesuai DeptScope (untuk input form)
        if (!empty($allowed)) {
            /** @var array<string, mixed> $deptRule */
            $deptRule = $rules['department_id'];
            $deptRule[] = Rule::in($allowed);
            $rules['department_id'] = $deptRule;
        }

        return $request->validate($rules);
    }

    /**
     * Aturan validasi bersama untuk store/update.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title'             => ['required','string','max:255'],
            'description'       => ['nullable','string'],
            'description_nc'    => ['nullable','string'],
            'evaluation'        => ['nullable','string'],
            'correction_action' => ['nullable','string'],
            'corrective_action' => ['nullable','string'],
            'category_id'       => ['required','exists:categories,id'],
            'subcategory_id'    => ['nullable','exists:subcategories,id'],
            'department_id'     => ['required','exists:departments,id'],
            'pic_user_id'       => ['required','exists:users,id'],
            'due_date'          => ['nullable','date'],
            'status'            => ['nullable', Rule::in(self::STATUSES)],
            'attachments.*'     => ['file','mimes:jpg,jpeg,png,pdf','max:5120'],
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
