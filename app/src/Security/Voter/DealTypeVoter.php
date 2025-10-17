<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\DealTypeVoterGenerated;

/**
 * DealType Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class DealTypeVoter extends DealTypeVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(DealType $dealtype, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($dealtype->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($dealtype, $user);
    // }
}
