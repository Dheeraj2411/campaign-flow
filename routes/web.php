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
Route::get('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);
Route::post('/telegram/webhook/{workspace:slug}', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);

// ── Dashboard ─────────────────────────────────────────────
Route::get('/dashboard', function () {
    $workspaceId = auth()->user()?->active_workspace_id ?? 0;
    return Inertia::render('Dashboard', [
        'stats' => [
            'contacts'  => \App\Models\Contact::where('workspace_id', $workspaceId)->count(),
            'campaigns' => \App\Models\Campaign::where('workspace_id', $workspaceId)->count(),
            'messages'  => \App\Models\MessageLog::whereHas('campaign', fn($q) => $q->where('workspace_id', $workspaceId))->count(),
            'inbox'     => \App\Models\Conversation::where('workspace_id', $workspaceId)->where('status', 'open')->count(),
        ],
        'recentCampaigns' => \App\Models\Campaign::where('workspace_id', $workspaceId)
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'platform', 'status', 'scheduled_at', 'created_at']),
    ]);
})->middleware(['auth', 'verified', \App\Http\Middleware\CheckUserIsActive::class])->name('dashboard');

// ── Authenticated group ───────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\CheckUserIsActive::class])->group(function () {

    // Profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Contacts
    Route::get('/contacts/import', [ContactController::class, 'importForm'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'importCsv'])->name('contacts.import.process');
    Route::resource('contacts', ContactController::class);

    // Campaigns
    Route::resource('campaigns', CampaignController::class);

    // Templates
    Route::resource('templates', MessageTemplateController::class);

    // Inbox (Real-Time Messaging)
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox');
    Route::get('/inbox/{conversation}', [\App\Http\Controllers\InboxController::class, 'show'])->name('inbox.show');
    Route::post('/inbox/{conversation}/reply', [\App\Http\Controllers\InboxController::class, 'reply'])->name('inbox.reply');

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

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

require __DIR__.'/auth.php';

