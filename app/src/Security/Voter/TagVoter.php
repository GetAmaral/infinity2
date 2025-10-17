<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TagVoterGenerated;

/**
 * Tag Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TagVoter extends TagVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Tag $tag, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($tag->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($tag, $user);
    // }
}
