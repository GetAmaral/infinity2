<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\PipelineVoterGenerated;

/**
 * Pipeline Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class PipelineVoter extends PipelineVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Pipeline $pipeline, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($pipeline->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($pipeline, $user);
    // }
}
