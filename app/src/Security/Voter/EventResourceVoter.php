<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\EventResourceVoterGenerated;

/**
 * EventResource Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class EventResourceVoter extends EventResourceVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(EventResource $eventresource, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($eventresource->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($eventresource, $user);
    // }
}
