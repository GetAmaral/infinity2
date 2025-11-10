<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\StepActionVoterGenerated;

/**
 * StepAction Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class StepActionVoter extends StepActionVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?StepAction $stepAction, User $user): bool
    // {
    //     // Add custom logic
    //     if ($stepAction->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($stepAction, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?StepAction $stepAction, User $user): bool
    // {
    //     // Owner can edit their own stepaction
    //     if ($stepAction->getOwner() && $user->getId()->equals($stepAction->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($stepAction, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?StepAction $stepAction, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($stepAction->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($stepAction, $user);
    // }
}
