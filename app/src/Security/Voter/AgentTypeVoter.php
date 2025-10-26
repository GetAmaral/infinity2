<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\AgentTypeVoterGenerated;

/**
 * AgentType Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class AgentTypeVoter extends AgentTypeVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?AgentType $agentType, User $user): bool
    // {
    //     // Add custom logic
    //     if ($agentType->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($agentType, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?AgentType $agentType, User $user): bool
    // {
    //     // Owner can edit their own agenttype
    //     if ($agentType->getOwner() && $user->getId()->equals($agentType->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($agentType, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?AgentType $agentType, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($agentType->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($agentType, $user);
    // }
}
