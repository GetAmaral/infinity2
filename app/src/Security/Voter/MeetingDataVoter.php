<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\MeetingDataVoterGenerated;

/**
 * MeetingData Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class MeetingDataVoter extends MeetingDataVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(MeetingData $meetingdata, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($meetingdata->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($meetingdata, $user);
    // }
}
