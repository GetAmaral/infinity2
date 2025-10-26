<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\NotificationTypeTemplateVoterGenerated;

/**
 * NotificationTypeTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class NotificationTypeTemplateVoter extends NotificationTypeTemplateVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?NotificationTypeTemplate $notificationTypeTemplate, User $user): bool
    // {
    //     // Add custom logic
    //     if ($notificationTypeTemplate->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($notificationTypeTemplate, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?NotificationTypeTemplate $notificationTypeTemplate, User $user): bool
    // {
    //     // Owner can edit their own notificationtypetemplate
    //     if ($notificationTypeTemplate->getOwner() && $user->getId()->equals($notificationTypeTemplate->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($notificationTypeTemplate, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?NotificationTypeTemplate $notificationTypeTemplate, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($notificationTypeTemplate->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($notificationTypeTemplate, $user);
    // }
}
