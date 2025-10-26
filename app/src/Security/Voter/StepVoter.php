<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StepVoterGenerated;

/**
 * Step Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StepVoter extends StepVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?Step $step, User $user): bool
    // {
    //     // Add custom logic
    //     if ($step->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($step, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?Step $step, User $user): bool
    // {
    //     // Owner can edit their own step
    //     if ($step->getOwner() && $user->getId()->equals($step->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($step, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?Step $step, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($step->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($step, $user);
    // }
}
