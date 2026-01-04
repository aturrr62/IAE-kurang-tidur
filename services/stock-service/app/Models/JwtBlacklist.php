<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model JwtBlacklist
 * 
 * Untuk mekanisme logout dan invalidasi JWT token
 */
class JwtBlacklist extends Model
{
    use HasFactory;

    protected $table = 'jwt_blacklist';

    // No updated_at
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'token',
        'jti',
        'expires_at',
        'blacklisted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'blacklisted_at' => 'datetime',
    ];

    /**
     * Check if a token is blacklisted by JTI
     */
    public static function isBlacklisted(string $jti): bool
    {
        return self::where('jti', $jti)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Add token to blacklist
     */
    public static function addToBlacklist(string $token, string $jti, \DateTime $expiresAt): self
    {
        return self::create([
            'token' => $token,
            'jti' => $jti,
            'expires_at' => $expiresAt,
            'blacklisted_at' => now(),
        ]);
    }

    /**
     * Clean up expired tokens from blacklist
     */
    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
