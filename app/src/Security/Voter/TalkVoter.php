<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TalkVoterGenerated;

/**
 * Talk Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TalkVoter extends TalkVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Talk $talk, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($talk->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($talk, $user);
    // }
}
