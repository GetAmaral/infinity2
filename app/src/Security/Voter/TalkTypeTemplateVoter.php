<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\TalkTypeTemplateVoterGenerated;

/**
 * TalkTypeTemplate Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Genmax Code Generator
 */
final class TalkTypeTemplateVoter extends TalkTypeTemplateVoterGenerated
{
    // Constructor is inherited from base class (RoleHierarchy is automatically injected)

    // Override authorization methods here if needed

    // Example: Custom VIEW logic
    // protected function canView(?TalkTypeTemplate $talkTypeTemplate, User $user): bool
    // {
    //     // Add custom logic
    //     if ($talkTypeTemplate->isPublic()) {
    //         return true;
    //     }
    //
    //     return parent::canView($talkTypeTemplate, $user);
    // }

    // Example: Custom EDIT logic
    // protected function canEdit(?TalkTypeTemplate $talkTypeTemplate, User $user): bool
    // {
    //     // Owner can edit their own talktypetemplate
    //     if ($talkTypeTemplate->getOwner() && $user->getId()->equals($talkTypeTemplate->getOwner()->getId())) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($talkTypeTemplate, $user);
    // }

    // Example: Custom DELETE logic
    // protected function canDelete(?TalkTypeTemplate $talkTypeTemplate, User $user): bool
    // {
    //     // Only allow deletion if no related data
    //     if ($talkTypeTemplate->hasRelatedData()) {
    //         return false;
    //     }
    //
    //     return parent::canDelete($talkTypeTemplate, $user);
    // }
}
