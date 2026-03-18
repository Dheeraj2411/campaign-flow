<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->active_workspace_id) {
            $workspace = $user->activeWorkspace;
            
            if ($workspace) {
                TenantContext::setWorkspace($workspace);
            }
        }

        return $next($request);
    }
}
