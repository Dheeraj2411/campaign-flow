<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('whatsapp', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->workspace_id ?: $request->ip());
        });

        RateLimiter::for('telegram', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->workspace_id ?: $request->ip());
        });

        Gate::define('view-campaign', fn($user, $campaign) => (new \App\Policies\CampaignPolicy)->view($user, $campaign));
        Gate::define('create-campaign', fn($user) => (new \App\Policies\CampaignPolicy)->create($user));
        Gate::define('view-contact-segment', fn($user, $segment) => (new \App\Policies\ContactSegmentPolicy)->view($user, $segment));
        Gate::define('create-contact-segment', fn($user) => (new \App\Policies\ContactSegmentPolicy)->create($user));
        Gate::define('delete-campaign', fn($user, $campaign) => (new \App\Policies\CampaignPolicy)->delete($user, $campaign));
    }
}
