<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseStaff;
use App\Services\JwtService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * StaffMutation
 * 
 * Resolver untuk staff management mutations
 */
class StaffMutation
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
     * Mutation: registerStaff
     * 
     * Register new warehouse staff (admin only)
     * Password akan otomatis di-hash oleh model
     */
    public function registerStaff($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): WarehouseStaff
    {
        $authUser = $this->getAuthUser($context);

        if (!$authUser || !$authUser->isAdmin()) {
            throw new \Exception('Unauthorized. Admin access required.');
        }

        $input = $args['input'];

        // Set default values
        $input['role'] = $input['role'] ?? 'staff';
        $input['department'] = $input['department'] ?? 'inventory';

        // Create staff (password akan otomatis di-hash oleh model boot method)
        $staff = WarehouseStaff::create($input);

        return $staff;
    }

    /**
     * Mutation: updateStaff
     * 
     * Update staff information
     * Staff bisa update data sendiri, admin bisa update siapa saja
     */
    public function updateStaff($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): WarehouseStaff
    {
        $authUser = $this->getAuthUser($context);

        if (!$authUser) {
            throw new \Exception('Unauthorized. Authentication required.');
        }

        $staffId = $args['id'];
        $input = $args['input'];

        $staff = WarehouseStaff::find($staffId);

        if (!$staff) {
            throw new \Exception('Staff not found');
        }

        // Check permission: staff can only update themselves, admin can update anyone
        if (!$authUser->isAdmin() && $authUser->id != $staffId) {
            throw new \Exception('Unauthorized. You can only update your own profile.');
        }

        // Non-admin cannot change role or department
        if (!$authUser->isAdmin()) {
            unset($input['role']);
            unset($input['department']);
        }

        // Update staff (password akan otomatis di-hash jika ada perubahan)
        $staff->update($input);

        return $staff->fresh();
    }

    /**
     * Mutation: deleteStaff
     * 
     * Delete staff (admin only)
     */
    public function deleteStaff($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): bool
    {
        $authUser = $this->getAuthUser($context);

        if (!$authUser || !$authUser->isAdmin()) {
            throw new \Exception('Unauthorized. Admin access required.');
        }

        $staffId = $args['id'];

        // Prevent self-deletion
        if ($authUser->id == $staffId) {
            throw new \Exception('You cannot delete your own account.');
        }

        $staff = WarehouseStaff::find($staffId);

        if (!$staff) {
            throw new \Exception('Staff not found');
        }

        return $staff->delete();
    }
}
