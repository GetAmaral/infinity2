<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\HolidayVoterGenerated;

/**
 * Holiday Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class HolidayVoter extends HolidayVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Holiday $holiday, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($holiday->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($holiday, $user);
    // }
}
