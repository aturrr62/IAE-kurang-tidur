<?php

namespace App\GraphQL\Queries;

use App\Models\WarehouseStaff;
use App\Services\JwtService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * AuthQuery
 * 
 * Resolver untuk authentication queries
 */
class AuthQuery
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Query: me
     * 
     * Get current authenticated user from JWT token
     * 
     * @param null $_
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return WarehouseStaff|null
     */
    public function me($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?WarehouseStaff
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

        $user = $this->jwtService->getAuthenticatedUser($token);

        if (!$user) {
            throw new \Exception('User not found or token invalid');
        }

        return $user;
    }
}
