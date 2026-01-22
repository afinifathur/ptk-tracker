<?php

namespace App\Http\Controllers;

use App\Services\PdfImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExamplePdfController extends Controller
{
    public function generate(Request $request, PdfImageService $pdfImageService)
    {
        // Example: $images = array of absolute paths (e.g. from storage/app/public or public_path)
        $images = [
            public_path('uploads/image1.png'),
            public_path('uploads/image2.jpg'),
            // ...
        ];

        $compressedImages = [];
        foreach ($images as $imgPath) {
            $compressedImages[] = $pdfImageService->getCompressedImagePath($imgPath);
        }

        // Pass $compressedImages to the Blade view
        $pdf = Pdf::loadView('pdf.example', [
            'images' => $compressedImages,
        ]);
        return $pdf->download('example.pdf');
    }
}
