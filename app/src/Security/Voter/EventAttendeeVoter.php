<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\EventAttendeeVoterGenerated;

/**
 * EventAttendee Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class EventAttendeeVoter extends EventAttendeeVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(EventAttendee $eventattendee, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($eventattendee->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($eventattendee, $user);
    // }
}
