<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Hash;
use GraphQL\Error\Error;

class AuthMutation
{
    /**
     * User login - generates JWT token
     *
     * @param null $_
     * @param array{email: string, password: string} $args
     * @return array
     */
    public function login($_, array $args): array
    {
        $user = User::where('email', $args['email'])->first();

        if (!$user || !Hash::check($args['password'], $user->password)) {
            throw new Error('Invalid email or password');
        }

        $token = JwtHelper::generateToken($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * Register new user
     *
     * @param null $_
     * @param array{input: array} $args
     * @return \App\Models\User
     */
    public function register($_, array $args): User
    {
        $input = $args['input'];

        // Validate unique username and email
        if (User::where('username', $input['username'])->exists()) {
            throw new Error('Username already taken');
        }

        if (User::where('email', $input['email'])->exists()) {
            throw new Error('Email already registered');
        }

        // Create user with auto-hashed password (via casts)
        $user = User::create([
            'username' => $input['username'],
            'name' => $input['name'] ?? $input['username'],
            'email' => $input['email'],
            'password' => $input['password'], // Will be auto-hashed
            'role' => $input['role'] ?? 'STAFF_GUDANG',
        ]);

        return $user;
    }

    /**
     * Get current authenticated user from JWT token
     *
     * @param null $_
     * @param array $args
     * @param array $context
     * @return \App\Models\User|null
     */
    public function me($_, array $args, array $context): ?User
    {
        // Extract token from request header
        $authHeader = $context['request']->header('Authorization');
        $token = JwtHelper::extractTokenFromHeader($authHeader);

        if (!$token) {
            throw new Error('No token provided');
        }

        // Get user data from token
        $userData = JwtHelper::getUserFromToken($token);

        if (!$userData) {
            throw new Error('Invalid or expired token');
        }

        // Fetch user from database
        return User::find($userData['id']);
    }
}
