<?php
// app/Http/Controllers/PTKController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\{PTK, Department, Category};
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PTKImport;
use App\Services\AttachmentService;
use App\Support\DeptScope;

class PTKController extends Controller
{
    /**
     * Roles admin per-departemen yang dipaksa mengikuti department_id user
     */
    private array $deptAdminRoles = ['admin_qc', 'admin_hr', 'admin_k3'];

    public function __construct()
    {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    /**
     * INDEX — daftar PTK (kirim $ptks ke view)
     */
    public function index(Request $request)
    {
        $q = PTK::with([
            'pic:id,name',
            'department:id,name',
            'category:id,name',
            'subcategory:id,name',
            'creator:id,name',
        ])->latest();

        // DeptScope
        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        // Filter status
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        // Pencarian judul / nomor
        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                  ->orWhere('number', 'like', "%{$term}%");
            });
        }

        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $ptks */
        $ptks = $q->paginate(20)->withQueryString();

        return view('ptk.index', compact('ptks')); // <-- pakai $ptks
    }

    public function create(Request $request)
    {
        $departments = $this->departmentsFor($request);

        return view('ptk.create', [
            'departments' => $departments,
            'categories'  => Category::all(),
            'users'       => \App\Models\User::select('id', 'name')->get(),
        ]);
    }

    /**
     * STORE — attachment diproses via AttachmentService.
     * Memaksa admin dept ke department_id miliknya.
     */
    public function store(Request $request)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());

        $rules = [
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'description_nc'    => 'nullable|string',
            'evaluation'        => 'nullable|string',
            'correction_action' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'category_id'       => 'required|exists:categories,id',
            'subcategory_id'    => 'nullable|exists:subcategories,id',
            'department_id'     => ['required', 'exists:departments,id'],
            'pic_user_id'       => 'required|exists:users,id',
            'due_date'          => 'nullable|date',
            'status'            => 'nullable|in:Not Started,In Progress,Completed',
            'attachments.*'     => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        // batasi department sesuai DeptScope
        if (!empty($allowed)) {
            $rules['department_id'][] = Rule::in($allowed);
        }

        $data = $request->validate($rules);

        // Paksa admin dept hanya ke departemennya
        if ($request->user()->hasAnyRole($this->deptAdminRoles)) {
            $data['department_id'] = $request->user()->department_id;
        }

        // set pembuat PTK
        $data['created_by'] = auth()->id();

        // simpan tanpa attachments
        $ptk = PTK::create(collect($data)->except(['attachments'])->toArray());

        // Proses upload (jika ada)
        if ($request->hasFile('attachments')) {
            $svc = app(AttachmentService::class);
            foreach ($request->file('attachments') as $file) {
                $svc->handle($file, $ptk->id);
            }
        }

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK dibuat.');
    }

    public function show(PTK $ptk)
    {
        // Lengkapi relasi untuk tampilan detail
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
        $departments = $this->departmentsFor($request);

        return view('ptk.edit', [
            'ptk'         => $ptk,
            'departments' => $departments,
            'categories'  => Category::all(),
            'users'       => \App\Models\User::select('id', 'name')->get(),
        ]);
    }

    /**
     * UPDATE — attachment tetap via AttachmentService.
     * Memaksa admin dept ke department_id miliknya.
     */
    public function update(Request $request, PTK $ptk)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());

        $rules = [
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'description_nc'    => 'nullable|string',
            'evaluation'        => 'nullable|string',
            'correction_action' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'category_id'       => 'required|exists:categories,id',
            'subcategory_id'    => 'nullable|exists:subcategories,id',
            'department_id'     => ['required', 'exists:departments,id'],
            'pic_user_id'       => 'required|exists:users,id',
            'due_date'          => 'nullable|date',
            'status'            => 'nullable|in:Not Started,In Progress,Completed',
            'attachments.*'     => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        if (!empty($allowed)) {
            $rules['department_id'][] = Rule::in($allowed);
        }

        $data = $request->validate($rules);

        // Paksa admin dept
        if ($request->user()->hasAnyRole($this->deptAdminRoles)) {
            $data['department_id'] = $request->user()->department_id;
        }

        // ---------- Opsi perapihan status otomatis ----------
        // Jika evaluation diisi (di update ini) dan status lama Not Started,
        // set status menjadi In Progress
        if ($request->filled('evaluation') && $ptk->status === 'Not Started') {
            $data['status'] = 'In Progress';
        }
        // -----------------------------------------------------

        $ptk->update(collect($data)->except(['attachments'])->toArray());

        if ($request->hasFile('attachments')) {
            $svc = app(AttachmentService::class);
            foreach ($request->file('attachments') as $file) {
                $svc->handle($file, $ptk->id);
            }
        }

        return back()->with('ok', 'PTK diperbarui.');
    }

    public function destroy(PTK $ptk)
    {
        $ptk->delete();
        return back()->with('ok', 'PTK dipindah ke Recycle Bin.');
    }

    public function kanban(Request $request)
    {
        $cols = ['Not Started', 'In Progress', 'Completed'];

        $q = PTK::with(['pic:id,name','department:id,name'])
            ->orderBy('updated_at', 'desc');

        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        $items = $q->get()->groupBy('status');

        return view('ptk.kanban', compact('cols', 'items'));
    }

    public function quickStatus(Request $request, PTK $ptk)
    {
        $this->authorize('update', $ptk);

        $request->validate([
            'status' => 'required|in:Not Started,In Progress,Completed',
        ]);

        $ptk->update(['status' => $request->status]);

        return response()->json(['ok' => true]);
    }

    /**
     * QUEUE — antrian persetujuan
     * Hanya tampil PTK yang sudah selesai (Completed) dan belum punya nomor.
     */
    public function queue(Request $request)
{
    // Tampilkan hanya PTK yang sudah Completed dan belum punya nomor
    $q = PTK::with(['pic:id,name', 'department:id,name'])
        ->where('status', 'Completed')
        ->whereNull('number')
        ->latest();

    // Kembalikan ke pola lama: kirim ke view sebagai $items + paginate
    $items = $q->paginate(20)->withQueryString();

    return view('ptk.queue', compact('items'));
}

    public function recycle(Request $request)
    {
        $q = PTK::onlyTrashed()->latest('deleted_at');

        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        $items = $q->paginate(20);

        return view('ptk.recycle', compact('items'));
    }

    public function restore(Request $request, $id)
    {
        $ptk = PTK::withTrashed()->findOrFail($id);
        $this->authorize('delete', $ptk);
        $ptk->restore();

        return back()->with('ok', 'PTK dipulihkan.');
    }

    /**
     * SUBMIT — ubah status ke Completed jika evaluasi sudah ada.
     */
    public function submit(PTK $ptk)
    {
        // (opsional tapi direkomendasikan) pastikan user boleh mengupdate PTK ini
        $this->authorize('update', $ptk);

        // hanya bisa submit jika sudah isi evaluasi
        if (empty($ptk->evaluation)) {
            return back()->with('error', 'PTK belum bisa disubmit — evaluasi masalah belum diisi.');
        }

        $ptk->update(['status' => 'Completed']);

        return redirect()
            ->route('ptk.show', $ptk)
            ->with('ok', 'PTK telah disubmit dan siap untuk approval.');
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        Excel::import(new PTKImport, $request->file('file'));

        return back()->with('ok', 'Import selesai.');
    }

    /**
     * Helper: daftar departemen sesuai DeptScope user
     */
    private function departmentsFor(Request $request)
    {
        $allowed = DeptScope::allowedDeptIds($request->user());

        return empty($allowed)
            ? Department::all()
            : Department::whereIn('id', $allowed)->get();
    }
}
