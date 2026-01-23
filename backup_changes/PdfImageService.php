<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class PdfImageService
{
    private ImageManager $manager;
    private string $cacheDir;

    public function __construct()
    {
        // Setup Intervention Image with GD driver
        $this->manager = new ImageManager(new Driver());

        // Define cache directory
        $this->cacheDir = storage_path('app/public/pdf-images'); // Accessible storage path

        // Ensure directory exists
        if (!File::exists($this->cacheDir)) {
            File::makeDirectory($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get the local path to a compressed version of the image.
     * If not cached, it compresses and saves it first.
     *
     * @param string $originalPath Absolute path to the original image
     * @return string Absolute path to the compressed image
     */
    public function getCompressedPath(string $originalPath): string
    {
        // 1. Validate existence
        if (!File::exists($originalPath)) {
            return $originalPath; // Fallback to original if file missing
        }

        // 2. Generate a unique filename based on modification time + path hash
        // This ensures if the original image changes, we re-generate.
        $hash = md5($originalPath . filemtime($originalPath));
        $filename = "{$hash}.jpg";
        $destination = "{$this->cacheDir}/{$filename}";

        // 3. Check cache
        if (File::exists($destination)) {
            return $destination;
        }

        try {
            // 4. Compress & Resize
            $image = $this->manager->read($originalPath);

            // Resize to max width 1200px, constrain aspect ratio
            $image->scaleDown(width: 1200);

            // Encode to JPEG quality 75
            $image->toJpeg(quality: 75)->save($destination);

            return $destination;
        } catch (\Throwable $e) {
            // Fallback: in case of error (e.g. unsupported format), return original
            // Log::error("Image compression failed: " . $e->getMessage());
            return $originalPath;
        }
    }
}
