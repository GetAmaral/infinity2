<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TalkGenerated;
use App\Repository\TalkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Talk Entity
 *
 * Communication threads with customers and prospects *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: TalkRepository::class)]
#[ORM\Table(name: 'talk')]
class Talk extends TalkGenerated
{
    // Channel constants for Talk.channel field (integer type)
    public const CHANNEL_UNKNOWN = 0;
    public const CHANNEL_WEB = 1;
    public const CHANNEL_WHATSAPP = 2;
    public const CHANNEL_EMAIL = 3;

    // Add custom properties here

    /**
     * Array of user IDs currently typing in this talk (for real-time typing indicators)
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $typingUsers = [];

    /**
     * Cached preview of the last message (for performance)
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lastMessagePreview = null;

    // Add custom methods here

    /**
     * Get a preview of the last message (for chat list display)
     * Returns cached value for performance
     */
    public function getLastMessagePreview(): ?string
    {
        return $this->lastMessagePreview;
    }

    /**
     * Set the cached preview of the last message
     */
    public function setLastMessagePreview(?string $lastMessagePreview): self
    {
        $this->lastMessagePreview = $lastMessagePreview;
        return $this;
    }

    /**
     * Get the list of user IDs currently typing in this talk
     */
    public function getTypingUsers(): ?array
    {
        return $this->typingUsers ?? [];
    }

    /**
     * Set the list of user IDs currently typing in this talk
     */
    public function setTypingUsers(?array $typingUsers): self
    {
        $this->typingUsers = $typingUsers;
        return $this;
    }

    /**
     * Get TreeFlow via Agent relationship
     *
     * TreeFlow is accessed indirectly: Talk → Agent → TreeFlow
     * This is a convenience method that encapsulates the relationship traversal.
     *
     * @return TreeFlow|null The TreeFlow from the first assigned Agent, or null if no Agent or no TreeFlow
     */
    public function getTreeFlow(): ?TreeFlow
    {
        // Get first agent from the agents collection
        $agent = $this->getAgents()->first();

        // If no agent assigned, return null
        if (!$agent) {
            return null;
        }

        // Return the agent's TreeFlow (may be null)
        return $agent->getTreeFlow();
    }
}
