<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    /**
     * Proses upload lampiran untuk PTK tertentu.
     * File disimpan di: storage/app/public/ptk/{ptkId}/...
     */
    public function handle(UploadedFile $file, int $ptkId): Attachment
    {
        $path = $file->store("ptk/{$ptkId}", 'public');

        return Attachment::create([
            'ptk_id'        => $ptkId,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'mime'          => $file->getClientMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => auth()->id(),
        ]);
    }
}
