<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Generate or extract X-Request-Id header and add it to log context
     * and response headers for correlation tracking.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract or generate request ID
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();

        // Share request ID in log context so it appears in all log entries
        Log::shareContext(['request_id' => $requestId]);

        // Process the request
        $response = $next($request);

        // Add X-Request-Id to response headers
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
