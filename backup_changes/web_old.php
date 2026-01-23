<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    DashboardController,
    PTKController,
    ApprovalController,
    ExportController,
    AuditController,
    PTKAttachmentController
};
use App\Http\Controllers\Settings\CategorySettingsController;
use App\Models\{PTK, Attachment};
use App\Http\Controllers\VerifyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | PTK CRUD
    |--------------------------------------------------------------------------
    */
    Route::resource('ptk', PTKController::class);

    // Kanban + update status cepat
    Route::get('ptk-kanban', [PTKController::class, 'kanban'])->name('ptk.kanban');
    Route::post('ptk/{ptk}/status', [PTKController::class, 'quickStatus'])->name('ptk.status');

    // Antrian approval
    Route::get('ptk-queue/{stage?}', [PTKController::class, 'queue'])
        ->whereIn('stage', ['approver', 'director'])
        ->name('ptk.queue')
        ->middleware('permission:menu.queue');

    // Recycle bin
    Route::get('ptk-recycle', [PTKController::class, 'recycle'])
        ->name('ptk.recycle')
        ->middleware('permission:menu.recycle');

    Route::post('ptk/{id}/restore', [PTKController::class, 'restore'])->name('ptk.restore');
    Route::delete('ptk/{id}/force', [PTKController::class, 'forceDelete'])->name('ptk.force');

    // Import PTK
    Route::post('ptk-import', [PTKController::class, 'import'])
        ->name('ptk.import')
        ->middleware('throttle:uploads');


    /*
    |--------------------------------------------------------------------------
    | Approval & Submit
    |--------------------------------------------------------------------------
    */
    Route::post('ptk/{ptk}/approve', [ApprovalController::class, 'approve'])
        ->name('ptk.approve')
        ->middleware('permission:ptk.approve');

    Route::post('ptk/{ptk}/reject', [ApprovalController::class, 'reject'])
        ->name('ptk.reject')
        ->middleware('permission:ptk.reject');

    Route::post('ptk/{ptk}/submit', [PTKController::class, 'submit'])
        ->name('ptk.submit');


    /*
    |--------------------------------------------------------------------------
    | Settings: Kategori & Subkategori
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')
        ->name('settings.')
        ->controller(CategorySettingsController::class)
        ->group(function () {

            // Kategori
            Route::get('categories', 'index')->name('categories');
            Route::post('categories', 'storeCategory')->name('categories.store');
            Route::patch('categories/{category}', 'updateCategory')->name('categories.update');
            Route::delete('categories/{category}', 'deleteCategory')->name('categories.delete');

            // Subkategori
            Route::post('subcategories', 'storeSubcategory')->name('subcategories.store');
            Route::patch('subcategories/{subcategory}', 'updateSubcategory')->name('subcategories.update');
            Route::delete('subcategories/{subcategory}', 'deleteSubcategory')->name('subcategories.delete');
        });


    /*
    |--------------------------------------------------------------------------
    | API Dropdown Dinamis
    |--------------------------------------------------------------------------
    */
    Route::get('api/subcategories', [CategorySettingsController::class, 'apiSubcategories'])
        ->name('api.subcategories');


    /*
    |--------------------------------------------------------------------------
    | Export (Preview, PDF, Range Report)
    |--------------------------------------------------------------------------
    */

    // existing preview (route-model binding)
    Route::get('exports/preview/{ptk}', [ExportController::class, 'preview'])
        ->name('exports.preview');

    // menampilkan PDF inline di tab baru (preview)
    // route baru sesuai permintaan: /exports/ptk/{id}/preview
    Route::get('/exports/ptk/{id}/preview', [ExportController::class, 'previewPdf'])
        ->name('exports.pdf.preview');

    Route::get('exports/pdf/{ptk}', [ExportController::class, 'pdf'])
        ->name('exports.pdf');

    Route::prefix('exports')->name('exports.')->group(function () {

        // Range laporan
        Route::get('range', [ExportController::class, 'rangeForm'])->name('range.form');
        Route::post('range', [ExportController::class, 'rangeReport'])->name('range.report');

        // Export Excel & PDF
        Route::get('excel', [ExportController::class, 'excel'])->name('excel');
        Route::post('range/excel', [ExportController::class, 'rangeExcel'])->name('range.excel');
        Route::post('range/pdf', [ExportController::class, 'rangePdf'])->name('range.pdf');

        // Audit Log (General)
        Route::get('audits', [AuditController::class, 'index'])
            ->name('audits.index')
            ->middleware('permission:menu.audit');

        // Approval Log (Admin View of Rejections/Approvals)
        Route::get('approval-log', [AuditController::class, 'approvalLog'])
            ->name('approval_log');
    });


    /*
    |--------------------------------------------------------------------------
    | Caption Attachment (Inline Update)
    |--------------------------------------------------------------------------
    */
    Route::patch('attachments/{attachment}/caption', function (Request $r, Attachment $attachment) {

        abort_unless(auth()->user()->can('update', $attachment->ptk), 403);

        $data = $r->validate([
            'caption' => 'nullable|string|max:255'
        ]);

        $attachment->update($data);

        return back()->with('ok', 'Caption tersimpan.');
    })->name('attachments.caption');


    /*
    |--------------------------------------------------------------------------
    | Hapus Lampiran PTK (Route Baru)
    |--------------------------------------------------------------------------
    */
    Route::delete('/ptk-attachment/{id}', [PTKAttachmentController::class, 'destroy'])
        ->name('ptk.attachment.delete');

});

require __DIR__ . '/auth.php';


/*
|--------------------------------------------------------------------------
| Verifikasi Dokumen (Publik)
|--------------------------------------------------------------------------
*/
Route::get('/verify/{ptk}/{hash}', [VerifyController::class, 'show'])->name('verify.show');
