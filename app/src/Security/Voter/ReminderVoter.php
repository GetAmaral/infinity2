<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\ReminderVoterGenerated;

/**
 * Reminder Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class ReminderVoter extends ReminderVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Reminder $reminder, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($reminder->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($reminder, $user);
    // }
}
