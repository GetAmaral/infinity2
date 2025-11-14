<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TalkMessageVoterGenerated;

/**
 * TalkMessage Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TalkMessageVoter extends TalkMessageVoterGenerated
{
    /**
     * Override: All authenticated users can create messages in chat
     */
    protected function canCREATE(\App\Entity\User $user): bool
    {
        // All authenticated users can send chat messages
        return true;
    }
}
