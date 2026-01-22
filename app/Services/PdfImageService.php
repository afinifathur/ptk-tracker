<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class PdfImageService
{
    protected $disk = 'local'; // storage/app
    protected $pdfImageDir = 'pdf-images';
    protected $maxWidth = 1200;
    protected $quality = 75;

    /**
     * Compress and resize image for PDF, return absolute path for Dompdf.
     *
     * @param string $originalPath Absolute path to original image
     * @return string Absolute path to compressed image
     */
    public function getCompressedImagePath(string $originalPath): string
    {
        if (!file_exists($originalPath)) {
            throw new \InvalidArgumentException("Image not found: $originalPath");
        }

        $hash = md5($originalPath . filemtime($originalPath));
        $ext = 'jpg';
        $filename = $hash . '.' . $ext;
        $pdfImagePath = storage_path("app/{$this->pdfImageDir}/$filename");

        if (file_exists($pdfImagePath)) {
            return $pdfImagePath;
        }

        // Ensure directory exists
        if (!is_dir(storage_path("app/{$this->pdfImageDir}"))) {
            mkdir(storage_path("app/{$this->pdfImageDir}"), 0755, true);
        }

        $img = Image::make($originalPath);
        if ($img->width() > $this->maxWidth) {
            $img->resize($this->maxWidth, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        $img->encode('jpg', $this->quality);
        $img->save($pdfImagePath);

        return $pdfImagePath;
    }
}
