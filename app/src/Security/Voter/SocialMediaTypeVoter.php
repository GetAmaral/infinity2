<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\SocialMediaTypeVoterGenerated;

/**
 * SocialMediaType Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class SocialMediaTypeVoter extends SocialMediaTypeVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?SocialMediaType $socialMediaType, User $user): bool
    // {
    //     // Add custom logic
    //     if ($socialMediaType->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($socialMediaType, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?SocialMediaType $socialMediaType, User $user): bool
    // {
    //     // Owner can edit their own socialmediatype
    //     if ($socialMediaType->getOwner() && $user->getId()->equals($socialMediaType->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($socialMediaType, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?SocialMediaType $socialMediaType, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($socialMediaType->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($socialMediaType, $user);
    // }
}
