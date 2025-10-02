<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    PTKController,
    ApprovalController,
    ExportController
};

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PTK CRUD
    Route::resource('ptk', PTKController::class);

    // Kanban + status cepat
    Route::get('ptk-kanban', [PTKController::class, 'kanban'])->name('ptk.kanban');
    Route::post('ptk/{ptk}/status', [PTKController::class, 'quickStatus'])->name('ptk.status');

    // Antrian persetujuan (stage optional: approver|director)
    Route::get('ptk-queue/{stage?}', [PTKController::class, 'queue'])
        ->whereIn('stage', ['approver', 'director'])
        ->name('ptk.queue');

    // Recycle bin + restore + hapus permanen
    Route::get('ptk-recycle', [PTKController::class, 'recycle'])->name('ptk.recycle');
    Route::post('ptk/{id}/restore', [PTKController::class, 'restore'])->name('ptk.restore');
    Route::delete('ptk/{id}/force', [PTKController::class, 'forceDelete'])->name('ptk.force');

    // PTK Import (dibatasi rate khusus upload)
    Route::post('ptk-import', [PTKController::class, 'import'])
        ->name('ptk.import')
        ->middleware('throttle:uploads');

    // Approval
    Route::post('ptk/{ptk}/approve', [ApprovalController::class, 'approve'])->name('ptk.approve');
    Route::post('ptk/{ptk}/reject',  [ApprovalController::class, 'reject'])->name('ptk.reject');

    // Exports
    Route::prefix('exports')->name('exports.')->group(function () {
        // Form & laporan rentang tanggal
        Route::get('range',  [ExportController::class, 'rangeForm'])->name('range.form');
        Route::post('range', [ExportController::class, 'rangeReport'])->name('range.report');

        // File exports
        Route::get('excel',        [ExportController::class, 'excel'])->name('excel');
        Route::get('pdf/{ptk}',    [ExportController::class, 'pdf'])->name('pdf');
        Route::post('range/excel', [ExportController::class, 'rangeExcel'])->name('range.excel');
        Route::post('range/pdf',   [ExportController::class, 'rangePdf'])->name('range.pdf');

        // routes/web.php (dalam group auth)
        Route::get('/audits', [\App\Http\Controllers\AuditController::class,'index'])->name('audits.index');

    });
});

// routes/web.php (di luar middleware auth agar bisa dipindai publik di LAN)
Route::get('/verify/{ptk}/{hash}', [\App\Http\Controllers\VerifyController::class,'show'])->name('verify.show');


require __DIR__ . '/auth.php';
