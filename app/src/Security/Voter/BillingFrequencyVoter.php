<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\BillingFrequencyVoterGenerated;

/**
 * BillingFrequency Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class BillingFrequencyVoter extends BillingFrequencyVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?BillingFrequency $billingFrequency, User $user): bool
    // {
    //     // Add custom logic
    //     if ($billingFrequency->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($billingFrequency, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?BillingFrequency $billingFrequency, User $user): bool
    // {
    //     // Owner can edit their own billingfrequency
    //     if ($billingFrequency->getOwner() && $user->getId()->equals($billingFrequency->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($billingFrequency, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?BillingFrequency $billingFrequency, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($billingFrequency->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($billingFrequency, $user);
    // }
}
