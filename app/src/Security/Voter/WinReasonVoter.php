<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\WinReasonVoterGenerated;

/**
 * WinReason Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class WinReasonVoter extends WinReasonVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(WinReason $winreason, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($winreason->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($winreason, $user);
    // }
}
