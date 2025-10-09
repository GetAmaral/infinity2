<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\LeadSourceVoterGenerated;

/**
 * LeadSource Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class LeadSourceVoter extends LeadSourceVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(LeadSource $leadsource, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($leadsource->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($leadsource, $user);
    // }
}
