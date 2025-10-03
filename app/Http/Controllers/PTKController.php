<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{PTK, Department, Category, Attachment};
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PTKImport;
use App\Services\AttachmentService;

class PTKController extends Controller
{
    /**
     * List PTK + filter status & pencarian.
     */
    public function index(Request $request)
    {
        $q = PTK::with([
            'pic:id,name',
            'department:id,name',
            'category:id,name',
            'subcategory:id,name',
        ])->latest();

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

    /**
     * Form create.
     */
    public function create()
    {
        return view('ptk.create', [
            'departments' => Department::all(),
            'categories'  => Category::all(),
            'users'       => \App\Models\User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Simpan PTK baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'number'         => 'required|unique:ptks,number',
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'department_id'  => 'required|exists:departments,id',
            'pic_user_id'    => 'required|exists:users,id',
            'due_date'       => 'required|date',
            'attachments.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $data['status'] = 'Not Started';

        $ptk = PTK::create($data);

        // Handle attachments via service
        if ($request->hasFile('attachments')) {
            $svc = app(AttachmentService::class);
            foreach ($request->file('attachments') as $file) {
                $svc->handle($file, $ptk->id); // auto-skip jika tidak valid
            }
        }

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK dibuat.');
    }

    /**
     * Detail PTK.
     */
    public function show(PTK $ptk)
    {
        $ptk->load(['attachments', 'pic', 'department', 'category', 'subcategory']);

        return view('ptk.show', compact('ptk'));
    }

    /**
     * Form edit.
     */
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
     * Update PTK.
     */
    public function update(Request $request, PTK $ptk)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'department_id'  => 'required|exists:departments,id',
            'pic_user_id'    => 'required|exists:users,id',
            'due_date'       => 'required|date',
            'status'         => 'required|in:Not Started,In Progress,Completed',
            'attachments.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $ptk->update($data);

        // Handle attachments baru (opsional)
        if ($request->hasFile('attachments')) {
            $svc = app(AttachmentService::class);
            foreach ($request->file('attachments') as $file) {
                $svc->handle($file, $ptk->id);
            }
        }

        return redirect()->route('ptk.show', $ptk)->with('ok', 'PTK diperbarui.');
    }

    /**
     * Soft delete ke Recycle Bin.
     */
    public function destroy(PTK $ptk)
    {
        $ptk->delete();

        return back()->with('ok', 'PTK dipindah ke Recycle Bin.');
    }

    /**
     * Board Kanban (Not Started, In Progress, Completed).
     */
    public function kanban()
    {
        $cols = ['Not Started', 'In Progress', 'Completed'];

        $items = PTK::with(['pic:id,name','department:id,name'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('status');

        return view('ptk.kanban', compact('cols', 'items'));
    }

    /**
     * Update status cepat via AJAX.
     */
    public function quickStatus(Request $request, PTK $ptk)
    {
        $request->validate([
            'status' => 'required|in:Not Started,In Progress,Completed',
        ]);

        $ptk->update(['status' => $request->status]);

        return response()->json(['ok' => true]);
    }

    /**
     * /ptk-queue             -> gabungan semua stage (default)
     * /ptk-queue/approver    -> khusus menunggu approver
     * /ptk-queue/director    -> khusus menunggu director
     */
    public function queue(?string $stage = null)
    {
        $q = PTK::with(['pic:id,name','department:id,name'])
            ->whereIn('status', ['Not Started', 'In Progress']);

        if ($stage === 'approver') {
            $q->whereNull('approver_id');
        } elseif ($stage === 'director') {
            $q->whereNotNull('approver_id')
              ->whereNull('director_id');
        }

        $items = $q->latest()->paginate(20)->withQueryString();

        return view('ptk.queue', compact('items', 'stage'));
    }

    /**
     * Recycle Bin.
     */
    public function recycle()
    {
        $items = PTK::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(20);

        return view('ptk.recycle', compact('items'));
    }

    /**
     * Pulihkan dari Recycle Bin.
     */
    public function restore($id)
    {
        $ptk = PTK::withTrashed()->findOrFail($id);
        $ptk->restore();

        return back()->with('ok', 'PTK dipulihkan.');
    }

    /**
     * Hapus permanen (termasuk file lampiran di storage).
     * NOTE: idealnya dibatasi role (director/superadmin).
     */
    public function forceDelete($id)
    {
        $ptk = PTK::withTrashed()->with('attachments')->findOrFail($id);

        // Hapus file lampiran fisik + record attachment
        foreach ($ptk->attachments as $a) {
            if ($a->path) {
                Storage::disk('public')->delete($a->path);
            }
            $a->delete();
        }

        $ptk->forceDelete();

        return back()->with('ok', 'PTK dihapus permanen.');
    }

    // =================
    // Import (Excel/CSV)
    // =================
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        Excel::import(new PTKImport, $request->file('file'));

        return back()->with('ok', 'Import selesai.');
    }
}
