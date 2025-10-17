<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TaskVoterGenerated;

/**
 * Task Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TaskVoter extends TaskVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Task $task, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($task->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($task, $user);
    // }
}
