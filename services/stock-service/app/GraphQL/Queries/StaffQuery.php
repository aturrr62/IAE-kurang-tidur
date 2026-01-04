<?php

namespace App\GraphQL\Queries;

use App\Models\WarehouseStaff;
use App\Services\JwtService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * StaffQuery
 * 
 * Resolver untuk staff management queries
 */
class StaffQuery
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Get authenticated user from context
     */
    protected function getAuthUser(GraphQLContext $context): ?WarehouseStaff
    {
        $request = $context->request();
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return null;
        }

        $token = $this->jwtService->extractTokenFromHeader($authHeader);
        return $this->jwtService->getAuthenticatedUser($token);
    }

    /**
     * Query: staffById
     * 
     * Get staff by ID (admin only)
     */
    public function staffById($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?WarehouseStaff
    {
        $authUser = $this->getAuthUser($context);

        if (!$authUser || !$authUser->isAdmin()) {
            throw new \Exception('Unauthorized. Admin access required.');
        }

        return WarehouseStaff::find($args['id']);
    }

    /**
     * Query: staffList
     * 
     * List all staff (admin/manager only)
     */
    public function staffList($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $this->getAuthUser($context);

        if (!$authUser || (!$authUser->isAdmin() && !$authUser->isManager())) {
            throw new \Exception('Unauthorized. Admin or Manager access required.');
        }

        $query = WarehouseStaff::query();

        // Filter by role if provided
        if (isset($args['role'])) {
            $query->where('role', $args['role']);
        }

        // Filter by department if provided
        if (isset($args['department'])) {
            $query->where('department', $args['department']);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }
}
