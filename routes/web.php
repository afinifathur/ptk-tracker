<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{DashboardController, PTKController, ApprovalController, ExportController};
Route::get('/', fn()=>redirect()->route('dashboard'));
Route::middleware(['auth'])->group(function(){
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
    Route::resource('ptk', PTKController::class);
    Route::get('ptk-kanban',[PTKController::class,'kanban'])->name('ptk.kanban');
    Route::get('ptk-queue',[PTKController::class,'queue'])->name('ptk.queue');
    Route::get('ptk-recycle',[PTKController::class,'recycle'])->name('ptk.recycle');
    Route::post('ptk/{ptk}/restore',[PTKController::class,'restore'])->name('ptk.restore');
    Route::post('ptk/{ptk}/status',[PTKController::class,'quickStatus'])->name('ptk.status');
    Route::post('ptk/{ptk}/approve',[ApprovalController::class,'approve'])->name('ptk.approve');
    Route::post('ptk/{ptk}/reject',[ApprovalController::class,'reject'])->name('ptk.reject');
    Route::get('exports/range',[ExportController::class,'rangeForm'])->name('exports.range.form');
    Route::post('exports/range',[ExportController::class,'rangeReport'])->name('exports.range.report');
});
require __DIR__.'/auth.php';
