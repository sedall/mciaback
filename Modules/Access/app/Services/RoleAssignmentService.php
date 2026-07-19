<?php

namespace Modules\Access\Services;

use App\Models\User;

class RoleAssignmentService
{
    public function assignDefaultCustomerRole(User $user): void
    {
        if (! $user->hasAnyRole(['admin', 'expert', 'clinic', 'customer'])) {
            $user->assignRole('customer');
        }
    }

    public function canAccessEntryPoint(User $user, string $entryPoint): bool
    {
        $allowedEntryPoints = [
            'customer',
            'clinic',
            'admin',
            'expert',
        ];

        if (! in_array($entryPoint, $allowedEntryPoints, true)) {
            return false;
        }

        return $user->can("panel.{$entryPoint}.access");
    }

    public function resolvePrimaryRole(User $user): ?string
    {
        return $user->roles()->pluck('name')->first();
    }
}
