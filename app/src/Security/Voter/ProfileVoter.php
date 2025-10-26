<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\ProfileVoterGenerated;

/**
 * Profile Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class ProfileVoter extends ProfileVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?Profile $profile, User $user): bool
    // {
    //     // Add custom logic
    //     if ($profile->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($profile, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?Profile $profile, User $user): bool
    // {
    //     // Owner can edit their own profile
    //     if ($profile->getOwner() && $user->getId()->equals($profile->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($profile, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?Profile $profile, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($profile->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($profile, $user);
    // }
}
