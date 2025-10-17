<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Voter\Generated\AttachmentVoterGenerated;

/**
 * Attachment Voter
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom authorization logic and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
class AttachmentVoter extends AttachmentVoterGenerated
{
    // Override authorization methods here if needed

    // Example:
    // protected function canEdit(Attachment $attachment, User $user): bool
    // {
    //     // Custom edit logic
    //     if ($attachment->getCreatedBy() === $user) {
    //         return true;
    //     }
    //
    //     return parent::canEdit($attachment, $user);
    // }
}
