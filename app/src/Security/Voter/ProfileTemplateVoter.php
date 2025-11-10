<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\ProfileTemplateVoterGenerated;

/**
 * ProfileTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class ProfileTemplateVoter extends ProfileTemplateVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?ProfileTemplate $profileTemplate, User $user): bool
    // {
    //     // Add custom logic
    //     if ($profileTemplate->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($profileTemplate, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?ProfileTemplate $profileTemplate, User $user): bool
    // {
    //     // Owner can edit their own profiletemplate
    //     if ($profileTemplate->getOwner() && $user->getId()->equals($profileTemplate->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($profileTemplate, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?ProfileTemplate $profileTemplate, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($profileTemplate->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($profileTemplate, $user);
    // }
}
