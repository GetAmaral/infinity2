<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\ContactVoterGenerated;

/**
 * Contact Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class ContactVoter extends ContactVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Contact $contact, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($contact->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($contact, $user);
    // }
}
