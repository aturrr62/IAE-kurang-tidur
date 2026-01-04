<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JwtAuthentication Middleware
 * 
 * Memverifikasi JWT token dan menambahkan user ke request
 */
class JwtAuthentication
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $token = $this->jwtService->extractTokenFromHeader($authHeader);

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'No token provided'
            ], 401);
        }

        try {
            $user = $this->jwtService->getAuthenticatedUser($token);

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid token or user not found'
                ], 401);
            }

            // Attach user to request
            $request->merge(['auth_user' => $user]);
            $request->setUserResolver(fn () => $user);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
