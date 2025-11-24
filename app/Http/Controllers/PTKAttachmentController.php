<?php

namespace App\Http\Controllers;

use App\Models\PTKAttachment;
use Illuminate\Support\Facades\Storage;

class PTKAttachmentController extends Controller
{
    public function destroy($id)
    {
        $att = PTKAttachment::findOrFail($id);

        // Hapus file dari storage
        if (Storage::disk('public')->exists($att->path)) {
            Storage::disk('public')->delete($att->path);
        }

        // Hapus row di database
        $ptkId = $att->ptk_id;
        $att->delete();

        return redirect()
            ->route('ptk.edit', $ptkId)
            ->with('success', 'Lampiran berhasil dihapus.');
    }
}
