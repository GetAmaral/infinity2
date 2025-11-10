<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CourseLectureVoterGenerated;

/**
 * CourseLecture Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class CourseLectureVoter extends CourseLectureVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?CourseLecture $courseLecture, User $user): bool
    // {
    //     // Add custom logic
    //     if ($courseLecture->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($courseLecture, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?CourseLecture $courseLecture, User $user): bool
    // {
    //     // Owner can edit their own courselecture
    //     if ($courseLecture->getOwner() && $user->getId()->equals($courseLecture->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($courseLecture, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?CourseLecture $courseLecture, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($courseLecture->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($courseLecture, $user);
    // }
}
