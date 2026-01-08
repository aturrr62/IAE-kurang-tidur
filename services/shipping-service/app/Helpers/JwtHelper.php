<?php

namespace App\Helpers;

use Exception;

class JwtHelper
{
    /**
     * Verify and decode JWT token
     *
     * @param string $token
     * @return object|null
     */
    public static function verifyToken(string $token): ?object
    {
        try {
            $secret = \env('JWT_SECRET', 'supersecretkey123');
            if (!class_exists('\\Firebase\\JWT\\JWT') || !class_exists('\\Firebase\\JWT\\Key')) {
                return null;
            }

            /** @var object $decoded */
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));

            return $decoded;
        } catch (Exception $e) {
            // Token invalid, expired, or malformed
            return null;
        }
    }

    /**
     * Extract token from Authorization header
     *
     * @param string|null $authHeader
     * @return string|null
     */
    public static function extractTokenFromHeader(?string $authHeader): ?string
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7); // Remove "Bearer " prefix
    }

    /**
     * Get user data from token
     *
     * @param string $token
     * @return array|null
     */
    public static function getUserFromToken(string $token): ?array
    {
        $decoded = self::verifyToken($token);
        
        if (!$decoded || !isset($decoded->data)) {
            return null;
        }

        return [
            'id' => $decoded->data->id ?? null,
            'username' => $decoded->data->username ?? null,
            'email' => $decoded->data->email ?? null,
            'role' => $decoded->data->role ?? null,
            'department' => $decoded->data->department ?? null,
        ];
    }
}
