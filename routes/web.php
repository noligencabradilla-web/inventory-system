<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH CONTROLLERS
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\LogoutController;

/*
|--------------------------------------------------------------------------
| ADMIN CONTROLLERS
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Admin\InboundController;
use App\Http\Controllers\Admin\OutboundController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\PasswordResetController as AdminPasswordResetController;
use App\Http\Controllers\Admin\NotificationPreferenceController;
use App\Http\Controllers\Admin\UserManagementController;

/*
|--------------------------------------------------------------------------
| CLIENT CONTROLLERS
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Client\AccountController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\StockController as ClientStockController;
use App\Http\Controllers\Client\ClientRequestController;
use App\Http\Controllers\Client\ClientOutboundController;
use App\Http\Controllers\Client\ClientNotificationController;
use App\Http\Controllers\Client\ClientNotificationPreferenceController;
use App\Http\Controllers\Client\ClientSubaccountController;
use App\Http\Controllers\Client\PasswordResetController as ClientPasswordResetController;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect('/login'));

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES
|--------------------------------------------------------------------------
*/
// Unified login routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

// Keep existing routes for backward compatibility
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.submit');

Route::get('/client/login', [LoginController::class, 'showLoginForm'])->name('client.login');
Route::post('/client/login', [LoginController::class, 'login'])->name('client.login.submit');

// Admin self-password reset (public, no auth required)
Route::get('/admin/password-reset-self', [AdminPasswordResetController::class, 'showSelfResetForm'])->name('admin.password-reset-self.form');
Route::post('/admin/password-reset-self', [AdminPasswordResetController::class, 'sendResetLink'])->name('admin.password-reset-self.send');

Route::get('/password-reset', [ClientPasswordResetController::class, 'showRequestForm'])->name('password-reset.request');
Route::post('/password-reset', [ClientPasswordResetController::class, 'submitRequest'])->name('password-reset.submit');

// Public token-based reset links (user clicks link to reset their own password)
Route::get('/password-reset/{token}/form', [ClientPasswordResetController::class, 'showResetForm'])->name('password-reset.reset');
Route::post('/password-reset/{token}/update', [ClientPasswordResetController::class, 'resetPassword'])->name('password-reset.update');

Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Admin summary / transactions
        Route::get('/summary', [AdminDashboardController::class, 'summary'])->name('admin.summary');
        Route::get('/summary/report/pdf', [AdminDashboardController::class, 'generateSummaryReportPdf'])->name('admin.summary.report.pdf');

        // AJAX endpoint for monthly chart analytics
        Route::get('/dashboard/chart-data', [AdminDashboardController::class, 'chartData'])->name('admin.dashboard.chartdata');

        // Client monitoring (inventory + members)
        Route::get('/client-monitoring', [AdminDashboardController::class, 'clientMonitoring'])->name('admin.client.monitoring');

        Route::resource('categories', CategoryController::class);
        Route::resource('stocks', AdminStockController::class);
        Route::get('/stocks/generate-id/{categoryId}', [AdminStockController::class, 'generateId'])->name('stocks.generateId');
        Route::post('/stocks/{stock}/assign-category', [AdminStockController::class, 'assignCategory'])->name('stocks.assignCategory');
        Route::put('/stocks/{stock}/edit-modal', [AdminStockController::class, 'editModal'])->name('stocks.editModal');
        Route::get('/inbound/template', [InboundController::class, 'template'])->name('inbound.template');
        Route::post('/inbound/import', [InboundController::class, 'import'])->name('inbound.import');
        Route::get('/inbound/suggestions', [InboundController::class, 'suggestions'])->name('inbound.suggestions');
        Route::get('/inbound/report/pdf', [InboundController::class, 'generateReportPdf'])->name('admin.inbound.report.pdf');
        Route::resource('inbound', InboundController::class);
        Route::get('/outbound/report/pdf', [OutboundController::class, 'generateReportPdf'])->name('admin.outbound.report.pdf');
        Route::resource('outbound', OutboundController::class);
        Route::get('/outbound/search-recipients', [OutboundController::class, 'searchRecipients'])
            ->name('outbound.search-recipients');

        /*
        |--------------------------------------------------------------------------
        | PASSWORD RESET REQUESTS
        |--------------------------------------------------------------------------
        */
        Route::get('/password-reset', [AdminPasswordResetController::class, 'index'])->name('password-reset.index');
        Route::post('/password-reset/{id}/approve', [AdminPasswordResetController::class, 'approve'])->name('password-reset.approve');
        Route::post('/password-reset/{id}/reject', [AdminPasswordResetController::class, 'reject'])->name('password-reset.reject');

        // Admin-only token reset form and submission
        Route::get('/password-reset/{token}/form', [AdminPasswordResetController::class, 'showResetForm'])
            ->name('password-reset.admin.form');
        Route::post('/password-reset/reset', [AdminPasswordResetController::class, 'resetPassword'])
            ->name('password-reset.admin.reset');

        /*
        |--------------------------------------------------------------------------
        | REQUESTS WORKFLOW
        |--------------------------------------------------------------------------
        */

        // ✅ MAIN requests page (this gives route('requests.index'))
        Route::get('/requests', [AdminRequestController::class, 'index'])
            ->name('requests.index');

        // Admin notifications page
        Route::get('/notifications', [AdminDashboardController::class, 'notifications'])
            ->name('admin.notifications');

        // Notification preferences
        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index'])
            ->name('admin.notification-preferences.index');
        Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])
            ->name('admin.notification-preferences.update');
        Route::get('/notifications/counts', [NotificationPreferenceController::class, 'getFilteredCounts'])
            ->name('admin.notifications.counts');

        // Notification actions
        Route::post('/notifications/{id}/read', [AdminDashboardController::class, 'markNotificationAsRead'])
            ->name('admin.notifications.read');
        Route::post('/notifications/read-all', [AdminDashboardController::class, 'markAllNotificationsAsRead'])
            ->name('admin.notifications.readAll');

        // ✅ ALIAS name (so route('admin.requests') also works)
        Route::get('/requests-alias', function () {
            return redirect()->route('requests.index');
        })->name('admin.requests');

        // ✅ Buttons from your blade use this
        Route::put('/requests/{stockRequest}/decision', [AdminRequestController::class, 'decision'])
            ->name('admin.requests.decision');

        // ✅ Release -> Outbound
        Route::put('/requests/{stockRequest}/release', [AdminRequestController::class, 'release'])
            ->name('admin.requests.release');

        // Keep ONLY if your controller really has decide()
        Route::put('/requests/{stockRequest}/decide', [AdminRequestController::class, 'decide'])
            ->name('admin.requests.decide');

        /*
        |--------------------------------------------------------------------------
        | USER MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{id}/edit', [UserManagementController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    });

/*
|--------------------------------------------------------------------------
| CLIENT ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('client')
    ->middleware(['auth', 'role:client'])
    ->group(function () {

        Route::get('/', [ClientDashboardController::class, 'index'])->name('client.dashboard');

        // Client summary/overview page
        Route::get('/summary', [ClientDashboardController::class, 'summary'])->name('client.summary');

        // Client inventory page
        Route::get('/inventory', [ClientStockController::class, 'inventory'])->name('client.inventory');
        Route::post('/inventory/deduct', [ClientStockController::class, 'deduct'])->name('client.inventory.deduct');

        Route::get('/stocks', [ClientStockController::class, 'index'])->name('client.stocks');

        // ✅ Multi item request submit
        Route::post('/requests', [ClientRequestController::class, 'store'])->name('client.requests.store');

        // ✅ View requests
        Route::get('/requests', [ClientRequestController::class, 'index'])->name('client.requests');
        
        // ✅ Cancel pending request
        Route::post('/requests/{id}/cancel', [ClientRequestController::class, 'cancel'])->name('client.requests.cancel');

        // ✅ Client outbound management
        Route::get('/outbounds', [ClientOutboundController::class, 'index'])->name('client.outbounds.index');
        Route::get('/outbounds/create', [ClientOutboundController::class, 'create'])->name('client.outbounds.create');
        Route::post('/outbounds', [ClientOutboundController::class, 'store'])->name('client.outbounds.store');
        Route::get('/outbounds/{outbound}', [ClientOutboundController::class, 'show'])->name('client.outbounds.show');

        // ✅ Notifications
        Route::get('/notifications', [ClientNotificationController::class, 'index'])->name('client.notifications');
        Route::get('/notifications/counts', [ClientNotificationController::class, 'counts'])->name('client.notifications.counts');
        Route::post('/notifications/{id}/read', [ClientNotificationController::class, 'markAsRead'])->name('client.notifications.read');
        Route::post('/notifications/read-all', [ClientNotificationController::class, 'markAllAsRead'])->name('client.notifications.readAll');

        // ✅ Notification Preferences
        Route::get('/notification-preferences', [ClientNotificationPreferenceController::class, 'index'])->name('client.notification-preferences.index');
        Route::put('/notification-preferences', [ClientNotificationPreferenceController::class, 'update'])->name('client.notification-preferences.update');

        // ✅ Account settings
        Route::get('/account', [AccountController::class, 'index'])->name('client.account');
        Route::post('/account/email', [AccountController::class, 'updateEmail'])->name('client.account.updateEmail');
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('client.account.updatePassword');
        Route::post('/account/distribute-to-subaccounts', [AccountController::class, 'distributeToSubaccounts'])->name('client.account.distributeToSubaccounts');
        Route::get('/account/report/pdf', [AccountController::class, 'generateReportPdf'])->name('client.account.report.pdf');
        Route::post('/account/members', [AccountController::class, 'storeMember'])->name('client.account.members.store');
        Route::put('/account/members/{id}', [AccountController::class, 'updateMember'])->name('client.account.members.update');
        Route::delete('/account/members/{id}', [AccountController::class, 'destroyMember'])->name('client.account.members.destroy');
        Route::post('/account/distribute-to-member', [AccountController::class, 'distributeToMember'])->name('client.account.distributeToMember');
        Route::post('/account/deduct-items', [AccountController::class, 'deductItems'])->name('client.account.deductItems');
        Route::post('/account/subaccounts', [ClientSubaccountController::class, 'store'])->name('client.account.subaccounts.store');
        Route::get('/account/subaccounts/{subaccount}', [ClientSubaccountController::class, 'show'])->name('client.account.subaccounts.show');
        Route::post('/account/subaccounts/{subaccount}/members', [ClientSubaccountController::class, 'storeMember'])->name('client.account.subaccounts.members.store');
        Route::post('/account/subaccounts/{subaccount}/distributions', [ClientSubaccountController::class, 'storeDistribution'])->name('client.account.subaccounts.distributions.store');
        Route::put('/account/subaccounts/{subaccount}/distributions/{distribution}', [ClientSubaccountController::class, 'updateDistribution'])->name('client.account.subaccounts.distributions.update');
        Route::delete('/account/subaccounts/{subaccount}/distributions/{distribution}', [ClientSubaccountController::class, 'destroyDistribution'])->name('client.account.subaccounts.distributions.destroy');
        Route::post('/account/subaccounts/{subaccount}/allocations/{allocation}/update-used', [ClientSubaccountController::class, 'updateUsedQty'])->name('client.account.subaccounts.allocations.updateUsed');
    });
