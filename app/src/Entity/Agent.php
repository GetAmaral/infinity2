<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\AgentGenerated;
use App\Repository\AgentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Agent Entity
 *
 * Customer service and sales agents
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[ORM\Table(name: 'agent')]
class Agent extends AgentGenerated
{
    // ===================================================================
    // WhatsApp Cloud API Helper Methods
    // ===================================================================
    // Note: getWhatsappPhoneNumberId() and setWhatsappPhoneNumberId() are now
    // auto-generated in AgentGenerated.php as a proper database field

    /**
     * Get WhatsApp access token from metadata
     *
     * @return string|null Access token starting with EAA
     */
    public function getWhatsAppAccessToken(): ?string
    {
        return $this->whatsappMetadata['access_token'] ?? null;
    }

    /**
     * Set WhatsApp access token in metadata
     *
     * @param string $accessToken Access token starting with EAA
     * @return self
     */
    public function setWhatsAppAccessToken(string $accessToken): self
    {
        $metadata = $this->whatsappMetadata ?? [];
        $metadata['access_token'] = $accessToken;
        $this->whatsappMetadata = $metadata;

        return $this;
    }

    /**
     * Check if Agent is configured for WhatsApp Cloud API
     *
     * @return bool True if phone_number_id and access_token are set
     */
    public function isWhatsAppCloudConfigured(): bool
    {
        return $this->whatsappActive
            && !empty($this->whatsappMetadata['phone_number_id'])
            && !empty($this->whatsappMetadata['access_token']);
    }

    /**
     * Validate WhatsApp Cloud API configuration
     *
     * @throws \InvalidArgumentException If configuration is invalid
     * @return void
     */
    public function validateWhatsAppCloudConfig(): void
    {
        if (!$this->whatsappActive) {
            throw new \InvalidArgumentException('WhatsApp is not active for this Agent');
        }

        if (empty($this->whatsappMetadata['phone_number_id'])) {
            throw new \InvalidArgumentException('phone_number_id is required in whatsappMetadata');
        }

        if (empty($this->whatsappMetadata['access_token'])) {
            throw new \InvalidArgumentException('access_token is required in whatsappMetadata');
        }

        // Validate phone_number_id format (15 digits)
        if (!preg_match('/^\d{15}$/', $this->whatsappMetadata['phone_number_id'])) {
            throw new \InvalidArgumentException('Invalid phone_number_id format (expected 15 digits)');
        }

        // Validate access_token format (starts with EAA)
        if (!str_starts_with($this->whatsappMetadata['access_token'], 'EAA')) {
            throw new \InvalidArgumentException('Invalid access_token format (expected to start with EAA)');
        }
    }
}
