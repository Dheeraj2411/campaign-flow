<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\MessageTemplateController;
use App\Models\Campaign;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Root redirect ─────────────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard'));

// ── Webhooks (Unauthenticated) ────────────────────────────
Route::get('/health', \App\Http\Controllers\HealthCheckController::class);
Route::get('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);
Route::post('/telegram/webhook/{workspace:slug}', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);
Route::get('/connect/{workspace:slug}', [\App\Http\Controllers\PublicContactController::class, 'show'])->name('public.connect');

// ── Dashboard ─────────────────────────────────────────────
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified', \App\Http\Middleware\CheckUserIsActive::class])->name('dashboard');

// ── Authenticated group ───────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\CheckUserIsActive::class])->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Contacts
    Route::get('/contacts/import', [ContactController::class, 'importForm'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'importCsv'])->name('contacts.import.process');
    Route::post('/contacts/clear-import-error', [ContactController::class, 'clearImportError'])->name('contacts.clear-import-error');
    Route::resource('contacts', ContactController::class);
    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::get('/campaigns/{campaign}/details', [CampaignController::class, 'show'])->name('campaigns.details'); // Dedicated details view

    // Templates
    Route::resource('templates', MessageTemplateController::class);
    Route::post('/templates/sync', [MessageTemplateController::class, 'sync'])->name('templates.sync');
    Route::post('/templates/{template}/test', [MessageTemplateController::class, 'sendTest'])->name('templates.test');

    // Inbox (Real-Time Messaging)
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox');
    Route::get('/inbox/{conversation}', [\App\Http\Controllers\InboxController::class, 'show'])->name('inbox.show');
    Route::post('/inbox/{conversation}/reply', [\App\Http\Controllers\InboxController::class, 'reply'])->name('inbox.reply');
    Route::post('/inbox/{conversation}/assign', [\App\Http\Controllers\InboxController::class, 'assign'])->name('inbox.assign');
    Route::post('/inbox/{conversation}/status', [\App\Http\Controllers\InboxController::class, 'updateStatus'])->name('inbox.status');
    Route::get('/inbox/{conversation}/notes', [\App\Http\Controllers\InboxController::class, 'fetchNotes'])->name('inbox.notes');
    Route::post('/inbox/{conversation}/notes', [\App\Http\Controllers\InboxController::class, 'addNote'])->name('inbox.notes.store');

    // Canned Responses
    Route::apiResource('canned-responses', \App\Http\Controllers\CannedResponseController::class);

    // Contact Segments
    Route::resource('contact-segments', \App\Http\Controllers\ContactSegmentController::class)->only(['index', 'store', 'update', 'destroy']);

    // Workflows
    Route::resource('workflows', \App\Http\Controllers\WorkflowController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/workflows/{workflow}/toggle', [\App\Http\Controllers\WorkflowController::class, 'toggleStatus'])->name('workflows.toggle');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/general', [\App\Http\Controllers\SettingsController::class, 'updateGeneral'])->name('settings.update.general');
    Route::put('/settings/api', [\App\Http\Controllers\SettingsController::class, 'updateApi'])->name('settings.update.api');

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');

    // Billing
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [\App\Http\Controllers\PaymentController::class, 'createCheckout'])->name('billing.checkout');
    Route::post('/billing/razorpay/callback', [\App\Http\Controllers\PaymentController::class, 'razorpayCallback'])->name('billing.razorpay.callback');

    // Admin
    Route::get('/admin/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
    Route::post('/admin/users/{user}/toggle', [\App\Http\Controllers\AdminController::class, 'toggleUserStatus'])->name('admin.users.toggle');
    Route::put('/admin/users/{user}/plan', [\App\Http\Controllers\AdminController::class, 'updateUserPlan'])->name('admin.users.plan');
    Route::get('/admin/transactions', [\App\Http\Controllers\AdminController::class, 'transactions'])->name('admin.transactions');
    Route::post('/admin/transactions/{transaction}/activate', [\App\Http\Controllers\AdminController::class, 'activateTransaction'])->name('admin.transactions.activate');

    // New Admin controls
    Route::get('/admin/plans', [\App\Http\Controllers\AdminController::class, 'plans'])->name('admin.plans');
    Route::put('/admin/plans/{plan}', [\App\Http\Controllers\AdminController::class, 'updatePlan'])->name('admin.plans.update');
    Route::post('/admin/users/{user}/profile-permission', [\App\Http\Controllers\AdminController::class, 'toggleProfilePermission'])->name('admin.users.profile-permission');

    // Workspaces
    Route::post('/workspaces/{workspace}/switch', [\App\Http\Controllers\WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::post('/workspaces/{workspace}/invite', [\App\Http\Controllers\InvitationController::class, 'send'])->name('workspaces.invite');
    Route::get('/workspaces/invitation/{token}', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('workspaces.invitation.accept');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

require __DIR__ . '/auth.php';
