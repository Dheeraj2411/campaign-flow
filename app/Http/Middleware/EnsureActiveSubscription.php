<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = auth()->user()?->activeWorkspace;

        if (!$workspace) {
            return $next($request);
        }

        $status = $workspace->subscription_status ?? 'trial';

        if ($status === 'trial' && $workspace->trial_ends_at && $workspace->trial_ends_at < now()) {
            return redirect('/billing')->with('error', 'Your trial has expired. Please upgrade to continue.');
        }

        if ($status === 'expired') {
            return redirect('/billing')->with('error', 'Your subscription has expired.');
        }

        if ($status === 'cancelled') {
            return redirect('/billing')->with('error', 'Your subscription was cancelled.');
        }

        // Only enforce message limit when both values are set and limit is positive
        $sent  = $workspace->messages_sent_this_month ?? 0;
        $limit = $workspace->monthly_message_limit ?? 0;

        if ($limit > 0 && $sent >= $limit) {
            return redirect('/billing')->with('error', 'Monthly message limit reached. Please upgrade your plan.');
        }

        return $next($request);
    }
}
