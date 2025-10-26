<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StepOutputVoterGenerated;

/**
 * StepOutput Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StepOutputVoter extends StepOutputVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StepOutput $stepOutput, User $user): bool
    // {
    //     // Add custom logic
    //     if ($stepOutput->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($stepOutput, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StepOutput $stepOutput, User $user): bool
    // {
    //     // Owner can edit their own stepoutput
    //     if ($stepOutput->getOwner() && $user->getId()->equals($stepOutput->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($stepOutput, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StepOutput $stepOutput, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($stepOutput->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($stepOutput, $user);
    // }
}
