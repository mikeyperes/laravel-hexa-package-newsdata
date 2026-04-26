<?php

use hexa_package_newsdata\Http\Controllers\NewsDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| NewsData Package Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'locked', 'system_lock', 'two_factor', 'role'])->group(function () {
    // Raw dev view
    Route::get('/newsdata', [NewsDataController::class, 'raw'])->name('newsdata.index');
    Route::get('/raw-newsdata', [NewsDataController::class, 'raw'])->name('newsdata.raw');

    // Settings
    Route::get('/settings/newsdata', [NewsDataController::class, 'settings'])->name('settings.newsdata');
    Route::post('/settings/newsdata/save', [NewsDataController::class, 'saveSettings'])->name('settings.newsdata.save');
    Route::post('/settings/newsdata/test', [NewsDataController::class, 'testApiKey'])->name('settings.newsdata.test');

    // API endpoints
    Route::post('/newsdata/search', [NewsDataController::class, 'searchArticles'])->name('newsdata.search');
});
