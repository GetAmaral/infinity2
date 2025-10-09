<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\AgentVoterGenerated;

/**
 * Agent Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class AgentVoter extends AgentVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Agent $agent, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($agent->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($agent, $user);
    // }
}
