<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CourseModuleVoterGenerated;

/**
 * CourseModule Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class CourseModuleVoter extends CourseModuleVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?CourseModule $courseModule, User $user): bool
    // {
    //     // Add custom logic
    //     if ($courseModule->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($courseModule, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?CourseModule $courseModule, User $user): bool
    // {
    //     // Owner can edit their own coursemodule
    //     if ($courseModule->getOwner() && $user->getId()->equals($courseModule->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($courseModule, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?CourseModule $courseModule, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($courseModule->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($courseModule, $user);
    // }
}
