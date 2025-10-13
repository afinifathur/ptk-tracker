<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    PTKController,
    ApprovalController,
    ExportController,
    AuditController
};
use App\Http\Controllers\Settings\CategorySettingsController;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    // ======================
    // Dashboard
    // ======================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ======================
    // PTK CRUD
    // ======================
    Route::resource('ptk', PTKController::class);

    // Kanban + update status cepat
    Route::get('ptk-kanban', [PTKController::class, 'kanban'])->name('ptk.kanban');
    Route::post('ptk/{ptk}/status', [PTKController::class, 'quickStatus'])->name('ptk.status');

    // Antrian persetujuan (stage optional: approver|director) + proteksi permission
    Route::get('ptk-queue/{stage?}', [PTKController::class, 'queue'])
        ->whereIn('stage', ['approver', 'director'])
        ->name('ptk.queue')
        ->middleware('permission:menu.queue');

    // Recycle bin + restore + hapus permanen + proteksi permission
    Route::get('ptk-recycle', [PTKController::class, 'recycle'])
        ->name('ptk.recycle')
        ->middleware('permission:menu.recycle');

    Route::post('ptk/{id}/restore', [PTKController::class, 'restore'])->name('ptk.restore');
    Route::delete('ptk/{id}/force', [PTKController::class, 'forceDelete'])->name('ptk.force');

    // Import PTK (dibatasi rate khusus upload)
    Route::post('ptk-import', [PTKController::class, 'import'])
        ->name('ptk.import')
        ->middleware('throttle:uploads');

    // ======================
    // Approval + proteksi permission
    // ======================
    Route::post('ptk/{ptk}/approve', [ApprovalController::class, 'approve'])
        ->name('ptk.approve')
        ->middleware('permission:ptk.approve');

    Route::post('ptk/{ptk}/reject',  [ApprovalController::class, 'reject'])
        ->name('ptk.reject')
        ->middleware('permission:ptk.reject');

    // ======================
    // Settings: Kategori & Subkategori
    // ======================
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

    // ======================
    // API: Dependent dropdown (Subcategories by Category)
    // ======================
    Route::get('api/subcategories', [CategorySettingsController::class, 'apiSubcategories'])
        ->name('api.subcategories');

    // ======================
    // Exports (+ Audit)
    // ======================
    Route::prefix('exports')->name('exports.')->group(function () {
        // Form & laporan rentang tanggal
        Route::get('range',  [ExportController::class, 'rangeForm'])->name('range.form');
        Route::post('range', [ExportController::class, 'rangeReport'])->name('range.report');

        // File exports
        Route::get('excel',        [ExportController::class, 'excel'])->name('excel');
        Route::get('pdf/{ptk}',    [ExportController::class, 'pdf'])->name('pdf');
        Route::post('range/excel', [ExportController::class, 'rangeExcel'])->name('range.excel');
        Route::post('range/pdf',   [ExportController::class, 'rangePdf'])->name('range.pdf');

        // Audit (dengan proteksi permission)
        Route::get('/audits', [AuditController::class, 'index'])
            ->name('audits.index')
            ->middleware('permission:menu.audit');
    });
});

require __DIR__ . '/auth.php';
