<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\DealCategoryVoterGenerated;

/**
 * DealCategory Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class DealCategoryVoter extends DealCategoryVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(DealCategory $dealcategory, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($dealcategory->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($dealcategory, $user);
    // }
}
