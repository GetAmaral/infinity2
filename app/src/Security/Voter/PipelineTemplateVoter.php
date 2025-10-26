<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\PipelineTemplateVoterGenerated;

/**
 * PipelineTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class PipelineTemplateVoter extends PipelineTemplateVoterGenerated
{
    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?PipelineTemplate $pipelineTemplate, User $user): bool
    // {
    //     // Add custom logic
    //     if ($pipelineTemplate->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($pipelineTemplate, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?PipelineTemplate $pipelineTemplate, User $user): bool
    // {
    //     // Owner can edit their own pipelinetemplate
    //     if ($pipelineTemplate->getOwner() && $user->getId()->equals($pipelineTemplate->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($pipelineTemplate, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?PipelineTemplate $pipelineTemplate, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($pipelineTemplate->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($pipelineTemplate, $user);
    // }
}
