<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CompetitorVoterGenerated;

/**
 * Competitor Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class CompetitorVoter extends CompetitorVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Competitor $competitor, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($competitor->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($competitor, $user);
    // }
}
