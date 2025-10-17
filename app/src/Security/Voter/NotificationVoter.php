<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\NotificationVoterGenerated;

/**
 * Notification Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class NotificationVoter extends NotificationVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Notification $notification, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($notification->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($notification, $user);
    // }
}
