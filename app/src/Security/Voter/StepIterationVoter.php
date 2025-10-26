<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StepIterationVoterGenerated;

/**
 * StepIteration Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StepIterationVoter extends StepIterationVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StepIteration $stepIteration, User $user): bool
    // {
    //     // Add custom logic
    //     if ($stepIteration->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($stepIteration, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StepIteration $stepIteration, User $user): bool
    // {
    //     // Owner can edit their own stepiteration
    //     if ($stepIteration->getOwner() && $user->getId()->equals($stepIteration->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($stepIteration, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StepIteration $stepIteration, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($stepIteration->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($stepIteration, $user);
    // }
}
