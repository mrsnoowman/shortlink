<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortlinkAnalyticsExportController;

Route::get('/', function () {
    return view('welcome');
});

// Export redirect logs of a shortlink as CSV (Excel compatible)
Route::get('/admin/shortlinks/{shortlink}/analytics/export', ShortlinkAnalyticsExportController::class)
    ->middleware(['web', 'auth'])
    ->name('shortlinks.analytics.export');

