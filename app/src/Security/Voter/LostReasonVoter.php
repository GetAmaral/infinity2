<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\LostReasonVoterGenerated;

/**
 * LostReason Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class LostReasonVoter extends LostReasonVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(LostReason $lostreason, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($lostreason->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($lostreason, $user);
    // }
}
