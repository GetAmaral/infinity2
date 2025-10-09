<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\BrandVoterGenerated;

/**
 * Brand Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class BrandVoter extends BrandVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Brand $brand, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($brand->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($brand, $user);
    // }
}
