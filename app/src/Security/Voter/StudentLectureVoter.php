<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StudentLectureVoterGenerated;

/**
 * StudentLecture Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StudentLectureVoter extends StudentLectureVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StudentLecture $studentLecture, User $user): bool
    // {
    //     // Add custom logic
    //     if ($studentLecture->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($studentLecture, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StudentLecture $studentLecture, User $user): bool
    // {
    //     // Owner can edit their own studentlecture
    //     if ($studentLecture->getOwner() && $user->getId()->equals($studentLecture->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($studentLecture, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StudentLecture $studentLecture, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($studentLecture->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($studentLecture, $user);
    // }
}
