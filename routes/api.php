<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'app'       => 'INVOXA',
        'version'   => '2.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Public auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Public invoice share view & PDF
Route::get('public/invoices/{token}',        [InvoiceController::class,   'publicView']);
Route::get('public/invoices/{invoice}/pdf',  [InvoiceController::class,   'downloadPdf']);

// Public quotation share view & PDF
Route::get('public/quotations/{token}',       [QuotationController::class, 'publicView']);
Route::get('public/quotations/{quotation}/pdf', [QuotationController::class, 'downloadPdf']);

// All authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout',   [AuthController::class, 'logout']);
    Route::get('/auth/me',        [AuthController::class, 'me']);
    Route::put('/auth/profile',   [AuthController::class, 'updateProfile']);

    // Workspace
    Route::get('workspace',            [App\Http\Controllers\Api\WorkspaceController::class, 'show']);
    Route::put('workspace',            [App\Http\Controllers\Api\WorkspaceController::class, 'update']);
    Route::post('workspace/logo',      [App\Http\Controllers\Api\WorkspaceController::class, 'uploadLogo']);
    Route::delete('workspace/logo',    [App\Http\Controllers\Api\WorkspaceController::class, 'removeLogo']);

    // Plan
    Route::get('plan/current', [App\Http\Controllers\Api\PlanController::class, 'current']);
    Route::post('plan/upgrade', [App\Http\Controllers\Api\PlanController::class, 'upgrade']);

    // Dashboard
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);

    // Invoices (full CRUD + share/email/PDF)
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send-email',  [InvoiceController::class, 'sendEmail']);
    Route::get('invoices/{invoice}/share-token',  [InvoiceController::class, 'getShareToken']);
    Route::get('invoices/{invoice}/pdf',          [InvoiceController::class, 'downloadPdf']);

    // Invoice payments
    Route::get('invoices/{invoice}/payments',         [PaymentController::class, 'index']);
    Route::post('invoices/{invoice}/payments',        [PaymentController::class, 'store']);
    Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy']);

    // Quotations (full CRUD + share/email/PDF — parity with invoices)
    Route::apiResource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/convert-to-invoice', [QuotationController::class, 'convertToInvoice']);
    Route::get('quotations/{quotation}/share-token',         [QuotationController::class, 'getShareToken']);
    Route::post('quotations/{quotation}/send-email',         [QuotationController::class, 'sendEmail']);
    Route::get('quotations/{quotation}/pdf',                 [QuotationController::class, 'downloadPdf']);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    // Admin
    Route::get('admin/dashboard',                        [App\Http\Controllers\Api\AdminController::class, 'dashboard']);
    Route::get('admin/subscribers',                      [App\Http\Controllers\Api\AdminController::class, 'subscribers']);
    Route::post('admin/billing/send/{workspaceId}',      [App\Http\Controllers\Api\AdminController::class, 'sendBillingInvoice']);
    Route::post('admin/billing/send-all',                [App\Http\Controllers\Api\AdminController::class, 'sendBillingToAll']);
    Route::put('admin/users/{userId}/role',              [App\Http\Controllers\Api\AdminController::class, 'updateUserRole']);
});
