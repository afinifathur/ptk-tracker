<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\{
    DashboardController,
    PTKController,
    ApprovalController,
    ExportController,
    AuditController,
    PTKAttachmentController,
    ApprovalLogController
};

use App\Http\Controllers\Settings\CategorySettingsController;
use App\Models\{PTK, Attachment};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


// Override all routes: always show maintenance view
Route::any('{any}', function () {
    return view('maintenance');
})->where('any', '.*');
