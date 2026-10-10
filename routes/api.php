<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScannerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/folders', [ScannerController::class, 'folders']);
    Route::post('/folders', [ScannerController::class, 'storeFolder']);
    Route::post('/folders/{folder}/documents', [ScannerController::class, 'upload']);
});
