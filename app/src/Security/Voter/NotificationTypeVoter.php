<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\NotificationTypeVoterGenerated;

/**
 * NotificationType Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class NotificationTypeVoter extends NotificationTypeVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(NotificationType $notificationtype, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($notificationtype->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($notificationtype, $user);
    // }
}
