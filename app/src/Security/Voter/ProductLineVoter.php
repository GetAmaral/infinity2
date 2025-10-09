<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\ProductLineVoterGenerated;

/**
 * ProductLine Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class ProductLineVoter extends ProductLineVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(ProductLine $productline, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($productline->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($productline, $user);
    // }
}
