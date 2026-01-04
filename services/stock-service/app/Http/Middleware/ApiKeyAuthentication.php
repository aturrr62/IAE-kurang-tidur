<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiKeyAuthentication Middleware
 * 
 * Untuk autentikasi eksternal (Product Service dari kelompok Toko)
 * Menggunakan header X-API-Key
 */
class ApiKeyAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $validApiKey = config('app.external_api_key');

        // Jika tidak ada API key dan tidak ada JWT, reject
        $authHeader = $request->header('Authorization');
        
        if (!$apiKey && !$authHeader) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'No authentication provided. Use X-API-Key header or JWT token.'
            ], 401);
        }

        // Jika ada API key, validasi
        if ($apiKey) {
            if (empty($validApiKey)) {
                return response()->json([
                    'error' => 'Configuration Error',
                    'message' => 'API Key authentication is not configured'
                ], 500);
            }

            if ($apiKey !== $validApiKey) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid API Key'
                ], 401);
            }

            // Mark request as API key authenticated (external)
            $request->merge(['api_key_auth' => true, 'is_external' => true]);
        }

        return $next($request);
    }
}
