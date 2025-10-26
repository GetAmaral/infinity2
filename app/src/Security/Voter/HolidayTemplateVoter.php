<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\HolidayTemplateVoterGenerated;

/**
 * HolidayTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class HolidayTemplateVoter extends HolidayTemplateVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?HolidayTemplate $holidayTemplate, User $user): bool
    // {
    //     // Add custom logic
    //     if ($holidayTemplate->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($holidayTemplate, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?HolidayTemplate $holidayTemplate, User $user): bool
    // {
    //     // Owner can edit their own holidaytemplate
    //     if ($holidayTemplate->getOwner() && $user->getId()->equals($holidayTemplate->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($holidayTemplate, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?HolidayTemplate $holidayTemplate, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($holidayTemplate->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($holidayTemplate, $user);
    // }
}
