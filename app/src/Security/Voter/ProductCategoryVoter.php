<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\ProductCategoryVoterGenerated;

/**
 * ProductCategory Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class ProductCategoryVoter extends ProductCategoryVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(ProductCategory $productcategory, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($productcategory->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($productcategory, $user);
    // }
}
