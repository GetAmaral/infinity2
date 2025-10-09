<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\DealStageVoterGenerated;

/**
 * DealStage Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class DealStageVoter extends DealStageVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(DealStage $dealstage, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($dealstage->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($dealstage, $user);
    // }
}
