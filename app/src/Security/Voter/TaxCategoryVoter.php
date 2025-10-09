<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TaxCategoryVoterGenerated;

/**
 * TaxCategory Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TaxCategoryVoter extends TaxCategoryVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(TaxCategory $taxcategory, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($taxcategory->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($taxcategory, $user);
    // }
}
