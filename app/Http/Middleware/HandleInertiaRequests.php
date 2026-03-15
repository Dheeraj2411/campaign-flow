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
        $user = $request->user();

        // Auto-create workspace for users (e.g. seeded admins) who lack one
        if ($user && !$user->active_workspace_id) {
            $workspace = \App\Models\Workspace::create([
                'owner_id' => $user->id,
                'name'     => explode(' ', $user->name)[0] . "'s Workspace",
                'slug'     => \Illuminate\Support\Str::slug($user->name . '-' . uniqid()),
            ]);
            $workspace->members()->attach($user->id, ['role' => 'owner']);
            $user->update(['active_workspace_id' => $workspace->id]);
            $user->refresh();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'email'               => $user->email,
                    'active_workspace_id' => $user->active_workspace_id,
                    'is_admin'            => $user->id === 1,
                ] : null,
                'workspace' => $user?->activeWorkspace ? [
                    'id'   => $user->activeWorkspace->id,
                    'name' => $user->activeWorkspace->name,
                    'slug' => $user->activeWorkspace->slug,
                    'plan' => $user->activeWorkspace->plan,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
