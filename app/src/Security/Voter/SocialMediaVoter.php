<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\SocialMediaVoterGenerated;

/**
 * SocialMedia Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class SocialMediaVoter extends SocialMediaVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(SocialMedia $socialmedia, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($socialmedia->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($socialmedia, $user);
    // }
}
