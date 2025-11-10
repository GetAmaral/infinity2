<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CommunicationMethodVoterGenerated;

/**
 * CommunicationMethod Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class CommunicationMethodVoter extends CommunicationMethodVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?CommunicationMethod $communicationMethod, User $user): bool
    // {
    //     // Add custom logic
    //     if ($communicationMethod->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($communicationMethod, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?CommunicationMethod $communicationMethod, User $user): bool
    // {
    //     // Owner can edit their own communicationmethod
    //     if ($communicationMethod->getOwner() && $user->getId()->equals($communicationMethod->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($communicationMethod, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?CommunicationMethod $communicationMethod, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($communicationMethod->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($communicationMethod, $user);
    // }
}
