<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\CampaignVoterGenerated;

/**
 * Campaign Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class CampaignVoter extends CampaignVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Campaign $campaign, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($campaign->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($campaign, $user);
    // }
}
