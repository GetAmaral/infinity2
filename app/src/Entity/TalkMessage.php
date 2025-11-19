<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TalkMessageGenerated;
use App\Repository\TalkMessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TalkMessage Entity
 *
 * Individual messages within communication threads *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: TalkMessageRepository::class)]
#[ORM\Table(name: 'talk_message')]
class TalkMessage extends TalkMessageGenerated
{
    // Add custom properties here

    // Add custom methods here

    /**
     * Get sender label for display in chat UI
     * Priority: Contact > User > Agent > Unknown
     *
     * @return string
     */
    public function getSenderLabel(): string
    {
        // Priority 1: Contact (customer/lead)
        if ($this->getFromContact()) {
            return $this->getFromContact()->getName() ?? $this->getFromContact()->__toString();
        }

        // Priority 2: User (internal team member)
        if ($this->getFromUser()) {
            return $this->getFromUser()->__toString();
        }

        // Priority 3: Agent (AI assistant)
        if ($this->getFromAgent()) {
            return $this->getFromAgent()->getName() ?? $this->getFromAgent()->__toString();
        }

        // Fallback
        return 'Unknown';
    }
}
