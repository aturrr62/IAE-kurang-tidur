<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // Extract token from Authorization header
        $authHeader = $request->header('Authorization');
        $token = JwtHelper::extractTokenFromHeader($authHeader);

        if (!$token) {
            return \response()->json([
                'error' => 'No token provided',
                'message' => 'Authorization header with Bearer token is required',
            ], 401);
        }

        // Verify token
        $userData = JwtHelper::getUserFromToken($token);

        if (!$userData) {
            return \response()->json([
                'error' => 'Invalid token',
                'message' => 'Token is invalid, expired, or malformed',
            ], 401);
        }

        // Inject user data into request for use in resolvers
        $request->merge(['auth_user' => $userData]);

        return $next($request);
    }
}
