<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Resources\AuthUserResource;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:verification');
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->middleware('throttle:login');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->middleware('throttle:login');

use App\Http\Controllers\ClientController;

use App\Http\Controllers\DashboardController;

use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\PublicInvoicePaymentController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\LaunchController;

Route::middleware('db.context')->group(function () {
    // Public Routes
    Route::get('public/contracts/{uuid}', [ContractController::class, 'getPublicContract'])->middleware('throttle:public-documents');
    Route::post('public/contracts/{uuid}/sign', [ContractController::class, 'signPublicContract'])->middleware('throttle:public-signing');
    Route::get('public/invoices/{token}', [PublicInvoicePaymentController::class, 'show'])->middleware('throttle:public-documents');
    Route::post('public/invoices/{token}/pay', [PublicInvoicePaymentController::class, 'initiate'])->middleware(['throttle:public-payments', 'throttle:paid-api']);
    Route::post('public/invoices/{token}/verify', [PublicInvoicePaymentController::class, 'verify'])->middleware(['throttle:public-payments', 'throttle:paid-api']);

    // Payment gateway webhooks (no auth — verified by signature)
    Route::post('webhooks/paystack', [PaymentController::class, 'paystackWebhook']);
    Route::post('webhooks/flutterwave', [PaymentController::class, 'flutterwaveWebhook']);
    Route::post('telemetry/events', [TelemetryController::class, 'captureEvent'])->middleware('throttle:telemetry');
    Route::post('telemetry/frontend-errors', [TelemetryController::class, 'captureFrontendError'])->middleware('throttle:frontend-errors');
    Route::post('waitlist', [WaitlistController::class, 'store'])->middleware('throttle:waitlist');
    Route::get('launch', [LaunchController::class, 'show']);
});

// Authenticated Routes
Route::middleware(['auth:sanctum', 'db.context'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:verification');
    Route::get('/company', [CompanyController::class, 'show']);
    Route::post('/company', [CompanyController::class, 'update']);
    Route::get('/user', function (Request $request) {
        return response()->json((new AuthUserResource($request->user()))->resolve());
    });
    Route::get('/billing', [BillingController::class, 'index']);
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware(['throttle:payments', 'throttle:paid-api']);
    Route::post('/billing/verify', [BillingController::class, 'verify'])->middleware(['throttle:payments', 'throttle:paid-api']);
    Route::post('/billing/cancel', [BillingController::class, 'cancel']);
    Route::get('/analytics/summary', [TelemetryController::class, 'summary'])->middleware(['throttle:api', 'subscription.feature:analytics']);
    Route::get('/issues', [IssueController::class, 'index'])->middleware('throttle:api');
    Route::post('/issues', [IssueController::class, 'store'])->middleware('throttle:api');
    Route::put('/issues/{issue}', [IssueController::class, 'update'])->middleware('throttle:api');
    Route::delete('/issues/{issue}', [IssueController::class, 'destroy'])->middleware('throttle:api');

    Route::apiResource('clients', ClientController::class);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Proposals
    Route::apiResource('proposals', ProposalController::class);
    Route::post('proposals/{proposal}/convert-to-contract', [ProposalController::class, 'convertToContract']);
    Route::get('proposals/{proposal}/pdf', [ProposalController::class, 'generatePdf'])->middleware('throttle:documents');

    // Contracts
    Route::apiResource('contracts', ContractController::class);
    Route::post('contracts/{contract}/convert-to-invoice', [ContractController::class, 'convertToInvoice']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send-payment-link', [InvoiceController::class, 'sendPaymentLink'])->middleware('throttle:payments');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->middleware('throttle:documents');

    // Projects & Tasks
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks']);
    Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask']);
    Route::put('projects/{project}/tasks/{task}', [ProjectController::class, 'updateTask']);
    Route::delete('projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask']);
    Route::post('projects/{project}/tasks/reorder', [ProjectController::class, 'reorderTasks']);

    // Payments
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
    Route::post('payments/manual', [PaymentController::class, 'storeManual'])->middleware('throttle:payments');
    Route::post('payments/initiate/paystack', [PaymentController::class, 'initiatePaystack'])->middleware(['throttle:payments', 'throttle:paid-api']);
    Route::post('payments/initiate/flutterwave', [PaymentController::class, 'initiateFlutterwave'])->middleware(['throttle:payments', 'throttle:paid-api']);
    Route::post('payments/verify', [PaymentController::class, 'verify'])->middleware(['throttle:payments', 'throttle:paid-api']);

    // Files
    Route::get('files', [FileController::class, 'index']);
    Route::post('files', [FileController::class, 'store'])->middleware('throttle:uploads');
    Route::get('files/{projectFile}/download', [FileController::class, 'download'])->middleware('throttle:documents');
    Route::delete('files/{projectFile}', [FileController::class, 'destroy']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->middleware('throttle:notifications');
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->middleware('throttle:notifications');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->middleware('throttle:notifications');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->middleware('throttle:notifications');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->middleware('throttle:notifications');

    // Admin console
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index']);
        Route::post('/impersonate/{user}', [AdminController::class, 'startImpersonation']);
        Route::post('/waitlist/{waitlistSignup}/approve', [AdminController::class, 'approveWaitlistSignup']);
        Route::post('/waitlist/{waitlistSignup}/reject', [AdminController::class, 'rejectWaitlistSignup']);
        Route::post('/invite-mode', [AdminController::class, 'setInviteMode']);
    });

    Route::post('/admin/impersonation/stop', [AdminController::class, 'stopImpersonation']);

    // Reserve this limiter for any future LLM/image/AI generation endpoints.
    // Route::post('ai/generate', ...)->middleware(['throttle:ai-generation', 'throttle:paid-api']);
});
