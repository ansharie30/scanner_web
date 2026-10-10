<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FolderController;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});
Route::middleware(['auth', 'verified', EnsureSuperAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('folders', FolderController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::post('folders/{folder}/documents', [DocumentController::class, 'store']);
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
});

require __DIR__.'/settings.php';
