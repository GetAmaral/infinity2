<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\PipelineStageTemplateVoterGenerated;

/**
 * PipelineStageTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class PipelineStageTemplateVoter extends PipelineStageTemplateVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?PipelineStageTemplate $pipelineStageTemplate, User $user): bool
    // {
    //     // Add custom logic
    //     if ($pipelineStageTemplate->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($pipelineStageTemplate, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?PipelineStageTemplate $pipelineStageTemplate, User $user): bool
    // {
    //     // Owner can edit their own pipelinestagetemplate
    //     if ($pipelineStageTemplate->getOwner() && $user->getId()->equals($pipelineStageTemplate->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($pipelineStageTemplate, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?PipelineStageTemplate $pipelineStageTemplate, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($pipelineStageTemplate->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($pipelineStageTemplate, $user);
    // }
}
