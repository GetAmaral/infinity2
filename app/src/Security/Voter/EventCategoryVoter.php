<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\EventCategoryVoterGenerated;

/**
 * EventCategory Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class EventCategoryVoter extends EventCategoryVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(EventCategory $eventcategory, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($eventcategory->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($eventcategory, $user);
    // }
}
