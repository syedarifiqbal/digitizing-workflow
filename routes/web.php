<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderFileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\CommissionRuleController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\WebhookLogController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ForceTenantContext;
use Illuminate\Support\Facades\Route;

// Public marketing pages
Route::middleware([ForceTenantContext::class])->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('home');
    Route::get('/features', [PublicController::class, 'features'])->name('features');
    Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');
    Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
    
    // Public form submissions
    Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
});

// Guest routes
Route::middleware('guest')->group(function () {
    if (! config('app.forced_tenant_id')) {
        Route::get('/register', [RegisterController::class, 'create'])->name('register');
        Route::post('/register', [RegisterController::class, 'store']);
    }

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Notification routes (no verified middleware needed)
    Route::get('/notifications/poll', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $notifications = $user->unreadNotifications()->latest()->limit(20)->get();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ])->toArray(),
        ]);
    })->name('notifications.poll');

    Route::post('/notifications/mark-all-read', function (\Illuminate\Http\Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.mark-all-read');

    Route::post('/notifications/{notification}/mark-read', function (\Illuminate\Http\Request $request, string $notification) {
        $request->user()->notifications()->where('id', $notification)->update(['read_at' => now()]);
        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        return back();
    })->name('notifications.mark-read');

    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/settings', [TenantSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [TenantSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/api-key', [ApiKeyController::class, 'store'])->name('settings.api-key.generate');
        Route::post('/settings/test-email', [TenantSettingsController::class, 'sendTestEmail'])->name('settings.test-email');
        Route::post('/settings/test-webhook', [TenantSettingsController::class, 'sendTestWebhook'])->name('settings.test-webhook');
        Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
        Route::delete('clients/bulk', [ClientController::class, 'bulkDestroy'])->name('clients.bulk-destroy');
        Route::resource('clients', ClientController::class);
        Route::patch('clients/{client}/status', [ClientController::class, 'toggleStatus'])->name('clients.status');
        Route::delete('orders/bulk', [OrderController::class, 'bulkDestroy'])->name('orders.bulk-destroy');
        Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::post('orders/{order}/assign', [OrderController::class, 'assign'])->name('orders.assign');
        Route::delete('orders/{order}/assign', [OrderController::class, 'unassign'])->name('orders.unassign');
        Route::post('orders/{order}/assign-sales', [OrderController::class, 'assignSales'])->name('orders.assign-sales');
        Route::delete('orders/{order}/assign-sales', [OrderController::class, 'unassignSales'])->name('orders.unassign-sales');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('orders/{order}/create-revision', [OrderController::class, 'createRevision'])->name('orders.create-revision');
        Route::post('orders/{order}/deliver', [OrderController::class, 'deliverOrder'])->name('orders.deliver');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('orders/{order}/submit-work', [OrderController::class, 'submitWork'])->name('orders.submit-work');
        Route::post('orders/{order}/comments', [OrderController::class, 'storeComment'])->name('orders.comments.store');
        Route::get('orders/files/{file}/download', [OrderFileController::class, 'download'])
            ->name('orders.files.download')
            ->middleware('signed');
        Route::get('orders/{order}/download-files/{type}', [OrderFileController::class, 'downloadZip'])
            ->name('orders.files.download-zip')
            ->middleware('signed');
        Route::delete('orders/files/{file}', [OrderFileController::class, 'destroy'])
            ->name('orders.files.destroy');
        Route::delete('users/bulk', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', UserController::class)->except(['show']);

        // Commission Rules
        Route::get('/commission-rules/sales', [CommissionRuleController::class, 'salesIndex'])->name('commission-rules.sales.index');
        Route::get('/commission-rules/designer', [CommissionRuleController::class, 'designerIndex'])->name('commission-rules.designer.index');
        Route::post('/commission-rules', [CommissionRuleController::class, 'store'])->name('commission-rules.store');
        Route::put('/commission-rules/{commissionRule}', [CommissionRuleController::class, 'update'])->name('commission-rules.update');
        Route::delete('/commission-rules/{commissionRule}', [CommissionRuleController::class, 'destroy'])->name('commission-rules.destroy');

        // Commissions
        Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
        Route::get('/commissions/export', [CommissionController::class, 'export'])->name('commissions.export');
        Route::post('/commissions/{commission}/mark-paid', [CommissionController::class, 'markAsPaid'])->name('commissions.mark-paid');
        Route::post('/commissions/bulk-mark-paid', [CommissionController::class, 'bulkMarkAsPaid'])->name('commissions.bulk-mark-paid');
        Route::post('/commissions/{commission}/update-tip', [CommissionController::class, 'updateExtraAmount'])->name('commissions.update-tip');

        Route::get('/invoices/report', [InvoiceController::class, 'report'])->name('invoices.report');
        Route::get('/invoices/export', [InvoiceController::class, 'exportCsv'])->name('invoices.export');
        Route::get('/invoices/eligible-orders', [InvoiceController::class, 'eligibleOrders'])->name('invoices.eligible-orders');
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

        // My Earnings (Sales & Designer)
        Route::get('/my-earnings', [CommissionController::class, 'myCommissions'])->name('commissions.my');
        Route::get('/my-earnings/export', [CommissionController::class, 'exportMy'])->name('commissions.my.export');

        // Designer Portal
        Route::get('/my-work', [DesignerController::class, 'dashboard'])->name('designer.dashboard');

        // Sales Portal
        Route::get('/my-orders', [SalesController::class, 'orders'])->name('sales.orders');

        // Client Portal
        Route::get('/client/dashboard', [ClientPortalController::class, 'dashboard'])->name('client.dashboard');
        Route::get('/client/orders', [ClientPortalController::class, 'orders'])->name('client.orders.index');
        Route::get('/client/orders/create', [ClientPortalController::class, 'createOrder'])->name('client.orders.create');
        Route::post('/client/orders', [ClientPortalController::class, 'storeOrder'])->name('client.orders.store');
        Route::get('/client/orders/{order}', [ClientPortalController::class, 'showOrder'])->name('client.orders.show');
        Route::post('/client/orders/{order}/comments', [ClientPortalController::class, 'storeComment'])->name('client.orders.comments.store');

        // Client Invoices
        Route::get('/client/invoices', [ClientPortalController::class, 'invoices'])->name('client.invoices.index');
        Route::get('/client/invoices/{invoice}', [ClientPortalController::class, 'showInvoice'])->name('client.invoices.show');
        Route::get('/client/invoices/{invoice}/pdf', [ClientPortalController::class, 'downloadInvoicePdf'])->name('client.invoices.pdf');
    });
});

// In forced-tenant mode, redirect root to login so route('home') doesn't break
if (config('app.forced_tenant_id')) {
    Route::get('/', fn () => redirect()->route('login'))->name('home');
}
