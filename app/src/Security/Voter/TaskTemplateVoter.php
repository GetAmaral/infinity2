<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TaskTemplateVoterGenerated;

/**
 * TaskTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TaskTemplateVoter extends TaskTemplateVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(TaskTemplate $tasktemplate, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($tasktemplate->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($tasktemplate, $user);
    // }
}
