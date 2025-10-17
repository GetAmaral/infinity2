<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\PipelineStageVoterGenerated;

/**
 * PipelineStage Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class PipelineStageVoter extends PipelineStageVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(PipelineStage $pipelinestage, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($pipelinestage->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($pipelinestage, $user);
    // }
}
