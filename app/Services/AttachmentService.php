<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class AttachmentService
{
    /** @var array ekstensi yang diizinkan */
    protected array $allowedExt = ['jpg','jpeg','png','pdf'];

    /** @var array mime yang diizinkan (signature-based) */
    protected array $allowedMime = ['image/jpeg','image/png','application/pdf'];

    /** Ukuran maksimal 5MB (di validasi form juga) */
    protected int $maxBytes = 5 * 1024 * 1024;

    public function handle(UploadedFile $file, int $ptkId): ?Attachment
    {
        // Cek ukuran
        if ($file->getSize() > $this->maxBytes) return null;

        // Verifikasi ekstensi
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $this->allowedExt, true)) return null;

        // Verifikasi MIME berdasar signature (bukan cuma header client)
        $mime = $this->detectMime($file);
        if (!in_array($mime, $this->allowedMime, true)) return null;

        // Gambar → re-encode (hapus EXIF), resize max 1920px sisi terpanjang, kompres 80
        if (str_starts_with($mime, 'image/')) {
            $img = Image::read($file->getRealPath())->orientate();
            $w = $img->width(); $h = $img->height();
            $max = 1920;
            if (max($w,$h) > $max) {
                $img->scaleDown(width: $w > $h ? $max : null, height: $h >= $w ? $max : null);
            }
            // Re-encode ke JPEG untuk strip metadata (EXIF) + kompres
            $binary = (string) $img->toJpeg(quality: 80);
            $path = 'attachments/'.uniqid('img_', true).'.jpg';
            Storage::disk('public')->put($path, $binary, 'public');

            return Attachment::create([
                'ptk_id'        => $ptkId,
                'path'          => $path,
                'mime'          => 'image/jpeg',
                'size'          => strlen($binary),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        // PDF → simpan apa adanya (opsi: jalankan AV jika tersedia)
        if ($mime === 'application/pdf') {
            $path = $file->store('attachments', 'public');
            return Attachment::create([
                'ptk_id'        => $ptkId,
                'path'          => $path,
                'mime'          => $mime,
                'size'          => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return null;
    }

    /** Deteksi MIME berbasis signature (finfo) */
    protected function detectMime(UploadedFile $file): string
    {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($f, $file->getRealPath()) ?: $file->getMimeType();
        finfo_close($f);
        return $mime;
    }
}
