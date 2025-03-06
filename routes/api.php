<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/{orderNumber}/submit', [OrderController::class, 'submit']);
    Route::get('/{orderNumber}/history', [OrderController::class, 'history']);
});

Route::prefix('approvals')->group(function () {
    Route::post('/{orderNumber}/approve', [ApprovalController::class, 'approve']);
    Route::post('/{orderNumber}/reject', [ApprovalController::class, 'reject']);
});
