<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CalendarExternalLinkVoterGenerated;

/**
 * CalendarExternalLink Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class CalendarExternalLinkVoter extends CalendarExternalLinkVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(CalendarExternalLink $calendarexternallink, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($calendarexternallink->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($calendarexternallink, $user);
    // }
}
