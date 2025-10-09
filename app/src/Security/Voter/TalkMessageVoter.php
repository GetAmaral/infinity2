<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TalkMessageVoterGenerated;

/**
 * TalkMessage Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TalkMessageVoter extends TalkMessageVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(TalkMessage $talkmessage, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($talkmessage->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($talkmessage, $user);
    // }
}
