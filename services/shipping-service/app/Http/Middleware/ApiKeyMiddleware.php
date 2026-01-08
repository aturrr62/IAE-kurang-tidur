<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ExternalApiKey;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request - validates API Key + HMAC signature
     */
    public function handle($request, Closure $next)
    {
        // Extract headers
        $apiKey = $request->header('X-API-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        // Validate required headers
        if (!$apiKey || !$signature || !$timestamp) {
            return \Illuminate\Support\Facades\Response::json([
                'error' => 'Missing authentication headers',
                'message' => 'X-API-Key, X-Signature, and X-Timestamp headers are required',
            ], 403);
        }

        // Timestamp must be unix seconds
        if (!ctype_digit((string) $timestamp)) {
            return \Illuminate\Support\Facades\Response::json([
                'error' => 'Invalid timestamp format',
            ], 403);
        }

        // Validate timestamp (prevent replay attack - max 5 minutes)
        $requestTime = (int) $timestamp;
        $currentTime = time();
        $maxAge = 300; // 5 minutes

        if (abs($currentTime - $requestTime) > $maxAge) {
            return \Illuminate\Support\Facades\Response::json([
                'error' => 'Invalid timestamp',
                'message' => 'Request timestamp is too old or invalid',
            ], 403);
        }

        // Lookup API key record
        $client = ExternalApiKey::where('api_key', $apiKey)->where('is_active', true)->first();
        if (!$client) {
            return \Illuminate\Support\Facades\Response::json([
                'error' => 'Unknown API key',
            ], 403);
        }

        // Build signature base: queryString + jsonVariables + timestamp
        $queryString = (string) $request->input('query', '');
        $variables = $request->input('variables', []);
        $jsonVariables = json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($jsonVariables === false) {
            $jsonVariables = '{}';
        }

        $message = $queryString . $jsonVariables . $timestamp;
        $expectedSignature = hash_hmac('sha256', $message, $client->secret_key);

        if (!hash_equals($expectedSignature, $signature)) {
            return \Illuminate\Support\Facades\Response::json([
                'error' => 'Invalid signature',
                'message' => 'HMAC signature verification failed',
            ], 403);
        }

        // Pass client metadata to the request
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
