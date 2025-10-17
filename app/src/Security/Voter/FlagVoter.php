<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\FlagVoterGenerated;

/**
 * Flag Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class FlagVoter extends FlagVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Flag $flag, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($flag->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($flag, $user);
    // }
}
