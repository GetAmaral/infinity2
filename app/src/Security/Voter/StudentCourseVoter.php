<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StudentCourseVoterGenerated;

/**
 * StudentCourse Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StudentCourseVoter extends StudentCourseVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StudentCourse $studentCourse, User $user): bool
    // {
    //     // Add custom logic
    //     if ($studentCourse->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($studentCourse, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StudentCourse $studentCourse, User $user): bool
    // {
    //     // Owner can edit their own studentcourse
    //     if ($studentCourse->getOwner() && $user->getId()->equals($studentCourse->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($studentCourse, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StudentCourse $studentCourse, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($studentCourse->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($studentCourse, $user);
    // }
}
