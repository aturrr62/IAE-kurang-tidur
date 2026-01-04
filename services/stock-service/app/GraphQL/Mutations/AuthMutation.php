<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseStaff;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * AuthMutation
 * 
 * Resolver untuk authentication mutations
 * Stock Service sebagai Auth Provider
 */
class AuthMutation
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Mutation: login
     * 
     * Authenticate staff and return JWT token
     * 
     * Response format:
     * {
     *   token: "eyJ0eXAiOiJKV1QiLCJhbGc...",
     *   user: { id, username, name, email, role, department },
     *   expiresIn: 1800
     * }
     * 
     * @return array AuthResponse
     */
    public function login($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        // Support login with username OR email
        $identifier = $args['username'] ?? $args['email'] ?? null;
        $password = $args['password'];

        if (!$identifier) {
            throw new \Exception('Username or email is required');
        }

        // Find user by username or email
        $staff = WarehouseStaff::where('username', $identifier)
                    ->orWhere('email', $identifier)
                    ->first();

        if (!$staff) {
            throw new \Exception('Invalid credentials');
        }

        // Verify password
        if (!Hash::check($password, $staff->password)) {
            throw new \Exception('Invalid credentials');
        }

        // Generate JWT token
        $tokenData = $this->jwtService->generateToken($staff);

        return [
            'token' => $tokenData['token'],
            'user' => $staff,
            'expiresIn' => $tokenData['expiresIn'],
        ];
    }

    /**
     * Mutation: register
     * 
     * Self-registration for new users
     * 
     * @return WarehouseStaff
     */
    public function register($_, array $args): WarehouseStaff
    {
        $input = $args['input'];

        // Create new staff with default role
        $staff = WarehouseStaff::create([
            'username' => $input['username'],
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'], // Will be auto-hashed by model
            'role' => $input['role'] ?? 'staff',
            'department' => $input['department'] ?? 'inventory',
        ]);

        return $staff;
    }

    /**
     * Mutation: logout
     * 
     * Blacklist current JWT token
     * 
     * @return bool
     */
    public function logout($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): bool
    {
        $request = $context->request();
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            throw new \Exception('No authorization header provided');
        }

        $token = $this->jwtService->extractTokenFromHeader($authHeader);

        if (!$token) {
            throw new \Exception('Invalid authorization header format');
        }

        // Blacklist token
        $success = $this->jwtService->blacklistToken($token);

        if (!$success) {
            throw new \Exception('Failed to logout');
        }

        return true;
    }
}
