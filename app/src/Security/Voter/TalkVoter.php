<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TalkVoterGenerated;

/**
 * Talk Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class TalkVoter extends TalkVoterGenerated
{
    /**
     * Override: All authenticated users can create talks
     */
    protected function canCREATE(\App\Entity\User $user): bool
    {
        // All authenticated users can create talks
        return true;
    }
}
