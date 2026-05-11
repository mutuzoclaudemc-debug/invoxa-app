<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'INVOXA',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public route - no auth needed
Route::get('public/invoices/{token}', [App\Http\Controllers\Api\InvoiceController::class, 'publicView']);
// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::get('plan/current', [App\Http\Controllers\Api\PlanController::class, 'current']);
    Route::post('plan/upgrade', [App\Http\Controllers\Api\PlanController::class, 'upgrade']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::get('invoices/{invoice}/share-token', [App\Http\Controllers\Api\InvoiceController::class, 'getShareToken']);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('products', ProductController::class);
    
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);
});