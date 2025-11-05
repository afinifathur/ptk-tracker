<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Imports\PTKImport;
use App\Models\{PTK, Department, Category, User};
use App\Services\AttachmentService;
use App\Support\DeptScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PTKController extends Controller
{
    private const PER_PAGE     = 20;
    private const KANBAN_LIMIT = 30;
    private const STATUSES     = ['Not Started', 'In Progress', 'Completed'];

    public function __construct(private readonly AttachmentService $attachments)
    {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    # =========================================================
    # INDEX — daftar PTK (pakai scope visibleTo)
    # =========================================================
    public function index(Request $request): View
    {
        $base = PTK::with(['department','category','subcategory','pic'])
            ->visibleTo(auth()->user());

        $ptks = $base->latest('created_at')->paginate(self::PER_PAGE);

        return view('ptk.index', compact('ptks'));
    }

    # =========================================================
    # CREATE / STORE
    # =========================================================
    public function create(Request $request): View
    {
        return view('ptk.create', [
            'departments'   => Department::orderBy('name')->pluck('name', 'id'),
            'categories'    => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['created_by'] = $request->user()->id;

        $ptk = PTK::create(collect($data)->except('attachments')->toArray());
        $this->handleAttachments($request, $ptk->id);

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK dibuat.');
    }

    # =========================================================
    # SHOW / EDIT / UPDATE
    # =========================================================
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
            'departments'   => Department::orderBy('name')->pluck('name', 'id'),
            'categories'    => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    public function update(Request $request, PTK $ptk): RedirectResponse
    {
        $data = $this->validatePayload($request);

        // Auto set In Progress ketika evaluation diisi dari Not Started
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
    public function destroy(PTK $ptk): RedirectResponse
    {
        try {
            $ptk->delete();
            return redirect()->route('ptk.index')->with('ok', 'PTK dipindahkan ke Recycle Bin.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus PTK: ' . $e->getMessage());
        }
    }

    # =========================================================
    # KANBAN — pakai scope visibleTo
    # =========================================================
    public function kanban(): View
    {
        $base = PTK::with(['department','category','subcategory','pic'])
            ->visibleTo(auth()->user());

        $notStarted = (clone $base)->where('status','Not Started')
            ->orderBy('created_at')->limit(self::KANBAN_LIMIT)->get();

        $inProgress = (clone $base)->where('status','In Progress')
            ->orderBy('created_at')->limit(self::KANBAN_LIMIT)->get();

        $completed = (clone $base)->where('status','Completed')
            ->latest()->limit(self::KANBAN_LIMIT)->get();

        return view('ptk.kanban', compact('notStarted','inProgress','completed'));
    }

    # =========================================================
    # QUICK STATUS (AJAX)
    # =========================================================
    public function quickStatus(Request $request, PTK $ptk): JsonResponse
    {
        $this->authorize('update', $ptk);

        $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);
        $ptk->update(['status' => $request->string('status')]);

        return response()->json(['ok' => true]);
    }

    # =========================================================
    # QUEUE — antrian approval (pakai scope visibleTo)
    #   - route: Route::get('/ptk-queue/{stage?}', ...)->name('ptk.queue');
    #   - stage: null/all | approver | director
    # =========================================================
    public function queue(Request $request, ?string $stage = null): View
    {
        $base = PTK::with(['department','category','subcategory','pic'])
            ->visibleTo(auth()->user())
            ->where('status','Completed')
            ->whereNull('number');

        $stage = $stage ? strtolower($stage) : null;
        if ($stage === 'approver') {
            $base->whereNull('approved_at');        // menunggu approver
        } elseif ($stage === 'director') {
            $base->whereNotNull('approved_at');     // menunggu director
        }

        $items = $base->latest('updated_at')->get();

        return view('ptk.queue', [
            'items' => $items,
            'stage' => $stage ?? 'all',
        ]);
    }

    # =========================================================
    # RECYCLE BIN (pakai scope visibleTo) — kirim $items ke Blade
    # =========================================================
    public function recycle(Request $request): View
    {
    $items = PTK::onlyTrashed()
        ->visibleTo(auth()->user())
        ->with(['department:id,name','category:id,name','subcategory:id,name','pic:id,name'])
        ->latest('deleted_at')
        ->paginate(self::PER_PAGE); // <-- penting: pakai paginate, bukan get()

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
            if ($a->path) Storage::disk('public')->delete($a->path);
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
            return back()->with('error', 'PTK belum bisa disubmit — evaluasi belum diisi.');
        }

        $ptk->update(['status' => 'Completed']);

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK telah disubmit dan siap approval.');
    }

    # =========================================================
    # IMPORT
    # =========================================================
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,csv,txt','max:10240'],
        ]);

        Excel::import(new PTKImport, $request->file('file'));

        return back()->with('ok', 'Import selesai.');
    }

    # =========================================================
    # HELPERS
    # =========================================================
    private function picCandidatesFor(Request $request)
    {
        $builder = User::query()->orderBy('name')->select(['id','name']);
        $allowedDeptIds = DeptScope::allowedDeptIds($request->user());

        if (!empty($allowedDeptIds)) {
            $builder->whereIn('department_id', $allowedDeptIds);
        }

        return $builder->get();
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate($this->rules());
    }

    private function rules(): array
    {
        return [
            'title'             => ['required','string','max:255'],
            'description'       => ['nullable','string'],
            'desc_nc'           => ['nullable','string'],
            'evaluation'        => ['nullable','string'],
            'action_correction' => ['nullable','string'],
            'action_corrective' => ['nullable','string'],
            'category_id'       => ['required','exists:categories,id'],
            'subcategory_id'    => ['nullable','exists:subcategories,id'],
            'department_id'     => ['required','exists:departments,id'],
            'pic_user_id'       => ['required','exists:users,id'],
            'due_date'          => ['nullable','date'],
            'form_date'         => ['required','date'],
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
