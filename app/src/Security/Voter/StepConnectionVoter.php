<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StepConnectionVoterGenerated;

/**
 * StepConnection Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StepConnectionVoter extends StepConnectionVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StepConnection $stepConnection, User $user): bool
    // {
    //     // Add custom logic
    //     if ($stepConnection->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($stepConnection, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StepConnection $stepConnection, User $user): bool
    // {
    //     // Owner can edit their own stepconnection
    //     if ($stepConnection->getOwner() && $user->getId()->equals($stepConnection->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($stepConnection, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StepConnection $stepConnection, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($stepConnection->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($stepConnection, $user);
    // }
}
