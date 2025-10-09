<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CalendarVoterGenerated;

/**
 * Calendar Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class CalendarVoter extends CalendarVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Calendar $calendar, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($calendar->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($calendar, $user);
    // }
}
