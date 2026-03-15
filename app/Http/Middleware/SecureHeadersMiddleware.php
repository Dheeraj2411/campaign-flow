<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeadersMiddleware
{
    /**
     * Security headers to add to every response.
     */
    private array $headers = [
        'X-Frame-Options'        => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection'      => '1; mode=block',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ];

    /**
     * Headers to remove from every response.
     */
    private array $removeHeaders = [
        'X-Powered-By',
        'Server',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        foreach ($this->removeHeaders as $header) {
            $response->headers->remove($header);
        }

        return $response;
    }
}
