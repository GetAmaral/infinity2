<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StepInputVoterGenerated;

/**
 * StepInput Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StepInputVoter extends StepInputVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StepInput $stepInput, User $user): bool
    // {
    //     // Add custom logic
    //     if ($stepInput->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($stepInput, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StepInput $stepInput, User $user): bool
    // {
    //     // Owner can edit their own stepinput
    //     if ($stepInput->getOwner() && $user->getId()->equals($stepInput->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($stepInput, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StepInput $stepInput, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($stepInput->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($stepInput, $user);
    // }
}
