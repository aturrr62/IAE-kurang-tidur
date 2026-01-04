<?php

namespace App\Services;

use App\Models\WarehouseStaff;
use App\Models\JwtBlacklist;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * JwtService
 * 
 * Service untuk menangani JWT token generation, validation, dan blacklisting
 * Menggunakan algoritma HS256 dengan shared secret
 */
class JwtService
{
    /**
     * Generate JWT token for authenticated user
     * 
     * Token claims:
     * - sub: user_id
     * - username: username
     * - role: admin/manager/staff
     * - department: inventory/shipping/both
     * - exp: expiration time (30 minutes)
     * - iat: issued at
     * - jti: unique token ID
     * 
     * @param WarehouseStaff $staff
     * @return array [token, expiresIn]
     */
    public function generateToken(WarehouseStaff $staff): array
    {
        $secret = config('app.jwt_secret');
        
        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET is not configured');
        }

        $issuedAt = time();
        $expiresIn = 30 * 60; // 30 minutes
        $expiresAt = $issuedAt + $expiresIn;
        
        // Generate unique JTI
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss' => config('app.name', 'Stock Service'), // Issuer
            'sub' => (string) $staff->id, // Subject (user ID)
            'username' => $staff->username,
            'role' => $staff->role,
            'department' => $staff->department,
            'iat' => $issuedAt, // Issued at
            'exp' => $expiresAt, // Expiration
            'jti' => $jti, // JWT ID
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'token' => $token,
            'expiresIn' => $expiresIn,
        ];
    }

    /**
     * Verify and decode JWT token
     * 
     * @param string $token
     * @return object Decoded token payload
     * @throws \Exception
     */
    public function verifyToken(string $token): object
    {
        $secret = config('app.jwt_secret');

        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET is not configured');
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // Check if token is blacklisted
            if (isset($decoded->jti) && JwtBlacklist::isBlacklisted($decoded->jti)) {
                throw new \Exception('Token has been revoked');
            }

            return $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired');
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Invalid token signature');
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Get authenticated user from token
     * 
     * @param string $token
     * @return WarehouseStaff|null
     */
    public function getAuthenticatedUser(string $token): ?WarehouseStaff
    {
        try {
            $decoded = $this->verifyToken($token);
            return WarehouseStaff::find($decoded->sub);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Blacklist token (for logout)
     * 
     * @param string $token
     * @return bool
     */
    public function blacklistToken(string $token): bool
    {
        try {
            $decoded = $this->verifyToken($token);
            
            if (!isset($decoded->jti) || !isset($decoded->exp)) {
                return false;
            }

            $expiresAt = new \DateTime();
            $expiresAt->setTimestamp($decoded->exp);

            JwtBlacklist::addToBlacklist($token, $decoded->jti, $expiresAt);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract token from Authorization header
     * 
     * @param string|null $authHeader
     * @return string|null
     */
    public function extractTokenFromHeader(?string $authHeader): ?string
    {
        if (empty($authHeader)) {
            return null;
        }

        // Format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Clean up expired blacklisted tokens
     * 
     * @return int Number of deleted records
     */
    public function cleanupBlacklist(): int
    {
        return JwtBlacklist::cleanup();
    }
}
