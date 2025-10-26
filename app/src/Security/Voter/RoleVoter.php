<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\RoleVoterGenerated;

/**
 * Role Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class RoleVoter extends RoleVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?Role $role, User $user): bool
    // {
    //     // Add custom logic
    //     if ($role->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($role, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?Role $role, User $user): bool
    // {
    //     // Owner can edit their own role
    //     if ($role->getOwner() && $user->getId()->equals($role->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($role, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?Role $role, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($role->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($role, $user);
    // }
}
