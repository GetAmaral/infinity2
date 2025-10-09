<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CompanyVoterGenerated;

/**
 * Company Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class CompanyVoter extends CompanyVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Company $company, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($company->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($company, $user);
    // }
}
