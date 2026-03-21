<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            
            'auth' => [
                'user' => fn() => $request->user()?->only(
                    'id', 'name', 'email',
                    'active_workspace_id', 'is_admin', 'is_super_admin', 'is_active'
                ),
                'workspace' => fn() => $request->user()?->activeWorkspace ? [
                    'id'   => $request->user()->activeWorkspace->id,
                    'name' => $request->user()->activeWorkspace->name,
                    'slug' => $request->user()->activeWorkspace->slug,
                    'plan' => $request->user()->activeWorkspace->plan,
                ] : null,
                'available_workspaces' => fn() => $request->user() ? $request->user()->workspaces->map(fn($w) => [
                    'id'   => $w->id,
                    'name' => $w->name,
                    'slug' => $w->slug,
                ]) : [],
            ],
            
            'workspace' => fn() => $request->user()?->activeWorkspace?->only(
                'id', 'name', 'subscription_status',
                'messages_sent_this_month', 'monthly_message_limit',
                'trial_ends_at', 'slug', 'plan'
            ),
            
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            
            'notifications_count' => fn() => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
                
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
