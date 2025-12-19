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

    private const STATUSES     = [
        PTK::STATUS_NOT_STARTED,
        PTK::STATUS_IN_PROGRESS,
        PTK::STATUS_SUBMITTED,
        PTK::STATUS_WAITING_DIRECTOR,
        PTK::STATUS_COMPLETED,
    ];

    public function __construct(private readonly AttachmentService $attachments)
    {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    // =========================================================
    // INDEX
    // =========================================================
    public function index(Request $request): View
    {
        $base = PTK::with(['department','category','subcategory','pic'])
            ->visibleTo($request->user());

        if ($q = $request->query('q')) {
            $base->where(function ($qb) use ($q) {
                $qb->where('number', 'like', "%{$q}%")
                   ->orWhere('title', 'like', "%{$q}%");
            });
        }

        if ($status = $request->query('status')) {
            $base->where('status', $status);
        }

        if ($role = $request->query('role_filter')) {
            $userIds = User::role($role)->pluck('id')->toArray();
            if (empty($userIds)) {
                $base->whereRaw('0 = 1');
            } else {
                $base->where(function ($qb) use ($userIds) {
                    $qb->whereIn('created_by', $userIds)
                       ->orWhereIn('pic_user_id', $userIds);
                });
            }
        }

        $ptks = $base->latest('created_at')->paginate(self::PER_PAGE)->withQueryString();
        return view('ptk.index', compact('ptks'));
    }

    // =========================================================
    // CREATE / STORE
    // =========================================================
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
        $data = $request->validate($this->rulesForStore());
        $data['created_by'] = $request->user()->id;

        $ptk = PTK::create(collect($data)->except('attachments')->toArray());
        $this->handleAttachments($request, $ptk->id);

        return redirect()->route('ptk.index')->with('ok', 'PTK tersimpan.');
    }

    // =========================================================
    // SHOW / EDIT / UPDATE (🔒 HARD LOCK)
    // =========================================================
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
        $this->authorize('update', $ptk);

        if ($ptk->isLocked()) {
            abort(403, 'PTK sudah dikunci dan tidak dapat diedit.');
        }

        return view('ptk.edit', [
            'ptk'           => $ptk,
            'departments'   => Department::orderBy('name')->pluck('name', 'id'),
            'categories'    => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    public function update(Request $request, PTK $ptk): RedirectResponse
    {
        $this->authorize('update', $ptk);

        if ($ptk->isLocked()) {
            abort(403, 'PTK sudah dikunci dan tidak dapat diubah.');
        }

        $data = $request->validate($this->rulesForUpdate($ptk));

        // Auto naik status ke In Progress saat evaluasi diisi
        if ($request->filled('evaluation') && $ptk->status === PTK::STATUS_NOT_STARTED) {
            $data['status'] = PTK::STATUS_IN_PROGRESS;
        }

        $ptk->update(collect($data)->except('attachments')->toArray());
        $this->handleAttachments($request, $ptk->id);

        return back()->with('ok', 'Perubahan disimpan.');
    }

    // =========================================================
    // DESTROY (🔒 HARD LOCK)
    // =========================================================
    public function destroy(PTK $ptk): RedirectResponse
    {
        $this->authorize('delete', $ptk);

        if ($ptk->isLocked()) {
            abort(403, 'PTK sudah dikunci dan tidak dapat dihapus.');
        }

        try {
            $ptk->delete();
            return redirect()->route('ptk.index')->with('ok', 'PTK dipindahkan ke Recycle Bin.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus PTK: ' . $e->getMessage());
        }
    }

    // =========================================================
    // KANBAN
    // =========================================================
    public function kanban(): View
    {
        $base = PTK::with(['department','category','subcategory','pic'])
            ->visibleTo(auth()->user());

        $notStarted = (clone $base)->where('status', PTK::STATUS_NOT_STARTED)
            ->orderBy('created_at')->limit(self::KANBAN_LIMIT)->get();

        $inProgress = (clone $base)->where('status', PTK::STATUS_IN_PROGRESS)
            ->orderBy('created_at')->limit(self::KANBAN_LIMIT)->get();

        $completed = (clone $base)->where('status', PTK::STATUS_COMPLETED)
            ->latest()->limit(self::KANBAN_LIMIT)->get();

        return view('ptk.kanban', compact('notStarted','inProgress','completed'));
    }
// =========================================================
// QUEUE — antrian approval
// =========================================================
public function queue(Request $request, ?string $stage = null): View
{
    $user  = auth()->user();
    $stage = $stage ? strtolower($stage) : null;

    $base = PTK::with(['department','pic'])->visibleTo($user);

    if ($user->hasRole('director') || $stage === 'director') {
        // Stage 2: menunggu Direktur
        $items = (clone $base)
            ->where('status', PTK::STATUS_WAITING_DIRECTOR)
            ->whereNull('approved_stage2_at')
            ->latest('updated_at')
            ->get();

        $stage = 'director';
    } else {
        // Stage 1: menunggu Kabag / Manager
        $items = (clone $base)
            ->where('status', PTK::STATUS_SUBMITTED)
            ->whereNull('approved_stage1_at')
            ->latest('updated_at')
            ->get();

        $stage = 'approver';
    }

    return view('ptk.queue', compact('items', 'stage'));
}

    // =========================================================
    // QUICK STATUS (AJAX) — hormati LOCK
    // =========================================================
    public function quickStatus(Request $request, PTK $ptk): JsonResponse
    {
        $this->authorize('update', $ptk);

        if ($ptk->isLocked()) {
            abort(403, 'PTK sudah dikunci.');
        }

        $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);
        $ptk->update(['status' => $request->string('status')]);

        return response()->json(['ok' => true]);
    }

    // =========================================================
    // SUBMIT (🔒 HARD GUARD)
    // =========================================================
    public function submit(PTK $ptk): RedirectResponse
    {
        $this->authorize('update', $ptk);

        if (!$ptk->canSubmit()) {
            abort(403, 'PTK hanya bisa disubmit dari status In Progress.');
        }

        if (empty($ptk->number)) {
            return back()->with('ok', 'Isi Nomor PTK dulu sebelum Submit.');
        }

        $ptk->update([
            'status'             => PTK::STATUS_SUBMITTED,
            'approved_stage1_by' => null,
            'approved_stage1_at' => null,
            'approved_stage2_by' => null,
            'approved_stage2_at' => null,
            'last_reject_stage'  => null,
            'last_reject_reason' => null,
            'last_reject_by'     => null,
            'last_reject_at'     => null,
        ]);

        return back()->with('ok', 'PTK dikirim ke antrian Kabag/Manager.');
    }

    // =========================================================
    // RECYCLE / RESTORE / FORCE DELETE
    // =========================================================
    public function recycle(Request $request): View
    {
        $items = PTK::onlyTrashed()
            ->visibleTo(auth()->user())
            ->with(['department:id,name','category:id,name','subcategory:id,name','pic:id,name'])
            ->latest('deleted_at')
            ->paginate(self::PER_PAGE);

        return view('ptk.recycle', compact('items'));
    }

    public function restore(Request $request, string $id): RedirectResponse
    {
        $ptk = PTK::withTrashed()->findOrFail($id);
        $this->authorize('delete', $ptk);

        if ($ptk->isLocked()) {
            abort(403, 'PTK sudah dikunci dan tidak dapat dipulihkan.');
        }

        $ptk->restore();
        return back()->with('ok', 'PTK dipulihkan.');
    }

    public function forceDelete(Request $request, string $id): RedirectResponse
    {
        $ptk = PTK::withTrashed()->with('attachments')->findOrFail($id);
        $this->authorize('delete', $ptk);

        if ($ptk->isLocked()) {
            abort(403, 'PTK sudah dikunci dan tidak dapat dihapus permanen.');
        }

        foreach ($ptk->attachments as $a) {
            if ($a->path) Storage::disk('public')->delete($a->path);
            $a->delete();
        }

        $ptk->forceDelete();
        return back()->with('ok', 'PTK dihapus permanen.');
    }

    // =========================================================
    // IMPORT
    // =========================================================
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,csv,txt','max:10240'],
        ]);

        Excel::import(new PTKImport, $request->file('file'));
        return back()->with('ok', 'Import selesai.');
    }

    // =========================================================
    // HELPERS
    // =========================================================
    private function picCandidatesFor(Request $request)
    {
        $builder = User::query()->orderBy('name')->select(['id','name']);
        $allowedDeptIds = DeptScope::allowedDeptIds($request->user());

        if (!empty($allowedDeptIds)) {
            $builder->whereIn('department_id', $allowedDeptIds);
        }

        return $builder->get();
    }

    private function rulesForStore(): array
    {
        return [
            'number'             => ['required','string','max:50','unique:ptks,number'],
            'title'              => ['required','string','max:200'],
            'description'        => ['nullable','string'],
            'desc_nc'            => ['nullable','string'],
            'evaluation'         => ['nullable','string'],
            'action_correction'  => ['nullable','string'],
            'action_corrective'  => ['nullable','string'],
            'category_id'        => ['required','exists:categories,id'],
            'subcategory_id'     => ['nullable','exists:subcategories,id'],
            'department_id'      => ['required','exists:departments,id'],
            'pic_user_id'        => ['required','exists:users,id'],
            'due_date'           => ['required','date'],
            'form_date'          => ['required','date'],
            'status'             => ['nullable', Rule::in(self::STATUSES)],
            'attachments.*'      => ['file','mimes:jpg,jpeg,png,pdf','max:5120'],
        ];
    }

    private function rulesForUpdate(PTK $ptk): array
    {
        return [
            'number'             => [
                'required','string','max:50',
                Rule::unique('ptks','number')->ignore($ptk->id),
            ],
            'title'              => ['required','string','max:200'],
            'description'        => ['nullable','string'],
            'desc_nc'            => ['nullable','string'],
            'evaluation'         => ['nullable','string'],
            'action_correction'  => ['nullable','string'],
            'action_corrective'  => ['nullable','string'],
            'category_id'        => ['required','exists:categories,id'],
            'subcategory_id'     => ['nullable','exists:subcategories,id'],
            'department_id'      => ['required','exists:departments,id'],
            'pic_user_id'        => ['required','exists:users,id'],
            'due_date'           => ['required','date'],
            'form_date'          => ['required','date'],
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
