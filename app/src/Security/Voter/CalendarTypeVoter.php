<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CalendarTypeVoterGenerated;

/**
 * CalendarType Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class CalendarTypeVoter extends CalendarTypeVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(CalendarType $calendartype, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($calendartype->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($calendartype, $user);
    // }
}
