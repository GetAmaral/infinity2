<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CityVoterGenerated;

/**
 * City Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class CityVoter extends CityVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?City $city, User $user): bool
    // {
    //     // Add custom logic
    //     if ($city->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($city, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?City $city, User $user): bool
    // {
    //     // Owner can edit their own city
    //     if ($city->getOwner() && $user->getId()->equals($city->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($city, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?City $city, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($city->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($city, $user);
    // }
}
