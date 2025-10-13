<?php
// app/Http/Controllers/PTKController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{PTK, Department, Category};
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PTKImport;
use App\Services\AttachmentService;
use App\Support\DeptScope;

class PTKController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    public function index(Request $request)
    {
        $q = PTK::with([
            'pic:id,name',
            'department:id,name',
            'category:id,name',
            'subcategory:id,name',
        ])->latest();

        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', '%'.$term.'%')
                  ->orWhere('number', 'like', '%'.$term.'%');
            });
        }

        $ptk = $q->paginate(20)->withQueryString();

        return view('ptk.index', compact('ptk'));
    }

    public function create()
    {
        return view('ptk.create', [
            'departments' => Department::all(),
            'categories'  => Category::all(),
            'users'       => \App\Models\User::select('id', 'name')->get(),
        ]);
    }

    /**
     * STORE — buang attachments dari insert PTK,
     * upload file diproses terpisah via AttachmentService.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'department_id'  => 'required|exists:departments,id',
            'pic_user_id'    => 'required|exists:users,id',
            'due_date'       => 'nullable|date',
            'status'         => 'nullable|in:Not Started,In Progress,Completed',
            'attachments.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Hapus key "attachments" agar tidak ikut create PTK
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
        $ptk->load(['attachments', 'pic', 'department', 'category', 'subcategory']);
        return view('ptk.show', compact('ptk'));
    }

    public function edit(PTK $ptk)
    {
        return view('ptk.edit', [
            'ptk'         => $ptk,
            'departments' => Department::all(),
            'categories'  => Category::all(),
            'users'       => \App\Models\User::select('id', 'name')->get(),
        ]);
    }

    /**
     * UPDATE — buang attachments dari update data PTK,
     * upload file tetap via AttachmentService.
     */
    public function update(Request $request, PTK $ptk)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'department_id'  => 'required|exists:departments,id',
            'pic_user_id'    => 'required|exists:users,id',
            'due_date'       => 'nullable|date',
            'status'         => 'nullable|in:Not Started,In Progress,Completed',
            'attachments.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Exclude attachments saat update
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

    public function queue(Request $request, ?string $stage = null)
    {
        $q = PTK::with(['pic:id,name','department:id,name'])
            ->whereIn('status', ['Not Started', 'In Progress']);

        if ($stage === 'approver') {
            $q->whereNull('approver_id');
        } elseif ($stage === 'director') {
            $q->whereNotNull('approver_id')->whereNull('director_id');
        }

        $allowed = DeptScope::allowedDeptIds($request->user());
        if (!empty($allowed)) {
            $q->whereIn('department_id', $allowed);
        }

        $items = $q->latest()->paginate(20)->withQueryString();

        return view('ptk.queue', compact('items', 'stage'));
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
}
