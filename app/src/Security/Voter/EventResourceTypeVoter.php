<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\EventResourceTypeVoterGenerated;

/**
 * EventResourceType Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class EventResourceTypeVoter extends EventResourceTypeVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?EventResourceType $eventResourceType, User $user): bool
    // {
    //     // Add custom logic
    //     if ($eventResourceType->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($eventResourceType, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?EventResourceType $eventResourceType, User $user): bool
    // {
    //     // Owner can edit their own eventresourcetype
    //     if ($eventResourceType->getOwner() && $user->getId()->equals($eventResourceType->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($eventResourceType, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?EventResourceType $eventResourceType, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($eventResourceType->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($eventResourceType, $user);
    // }
}
