<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\DealVoterGenerated;

/**
 * Deal Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class DealVoter extends DealVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?Deal $deal, User $user): bool
    // {
    //     // Add custom logic
    //     if ($deal->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($deal, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?Deal $deal, User $user): bool
    // {
    //     // Owner can edit their own deal
    //     if ($deal->getOwner() && $user->getId()->equals($deal->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($deal, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?Deal $deal, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($deal->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($deal, $user);
    // }
}
