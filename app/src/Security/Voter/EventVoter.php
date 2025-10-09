<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\EventVoterGenerated;

/**
 * Event Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class EventVoter extends EventVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Event $event, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($event->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($event, $user);
    // }
}
