<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request - validates API Key + HMAC signature
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract headers
        $apiKey = $request->header('X-API-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        // Validate required headers
        if (!$apiKey || !$signature || !$timestamp) {
            return response()->json([
                'error' => 'Missing authentication headers',
                'message' => 'X-API-Key, X-Signature, and X-Timestamp headers are required',
            ], 401);
        }

        // Validate timestamp (prevent replay attack - max 5 minutes)
        $requestTime = strtotime($timestamp);
        $currentTime = time();
        $maxAge = 300; // 5 minutes

        if (!$requestTime || abs($currentTime - $requestTime) > $maxAge) {
            return response()->json([
                'error' => 'Invalid timestamp',
                'message' => 'Request timestamp is too old or invalid',
            ], 401);
        }

        // Get request body
        $body = $request->getContent();

        // Calculate expected signature: HMAC-SHA256(API_KEY + TIMESTAMP + BODY, SECRET)
        $secret = env('API_SECRET_KEY', 'shared_secret_with_toko_12345');
        $expectedSignature = hash_hmac('sha256', $apiKey . $timestamp . $body, $secret);

        // Verify signature
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'error' => 'Invalid signature',
                'message' => 'HMAC signature verification failed',
            ], 401);
        }

        // Inject API key info into request for logging/tracking
        $request->merge(['api_client' => $apiKey]);

        return $next($request);
    }
}
