<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Organization;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to manage the active organization context based on subdomain
 */
final class OrganizationContext
{
    private const SESSION_KEY = '_active_organization_id';
    private const SESSION_SLUG_KEY = '_active_organization_slug';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * Set the active organization in the session
     */
    public function setOrganization(?Organization $organization): void
    {
        $session = $this->requestStack->getSession();

        if ($organization === null) {
            $session->remove(self::SESSION_KEY);
            $session->remove(self::SESSION_SLUG_KEY);
            return;
        }

        $session->set(self::SESSION_KEY, $organization->getId()->toRfc4122());
        $session->set(self::SESSION_SLUG_KEY, $organization->getSlug());
    }

    /**
     * Get the active organization ID from session
     */
    public function getOrganizationId(): ?string
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::SESSION_KEY);
    }

    /**
     * Get the active organization slug from session
     */
    public function getOrganizationSlug(): ?string
    {
        $session = $this->requestStack->getSession();
        return $session->get(self::SESSION_SLUG_KEY);
    }

    /**
     * Check if an organization is currently active
     */
    public function hasActiveOrganization(): bool
    {
        return $this->getOrganizationId() !== null;
    }

    /**
     * Clear the active organization from session
     */
    public function clearOrganization(): void
    {
        $this->setOrganization(null);
    }

    /**
     * Extract organization slug from subdomain
     * Example: "acme-corporation.localhost" returns "acme-corporation"
     *          "localhost" returns null (root domain, no organization)
     *          "www.luminai.ia.br" returns null (www is not an organization)
     *          "luminai.ia.br" returns null (apex domain, no organization)
     *          "91.98.137.175" returns null (IP address, no organization)
     */
    public function extractSlugFromHost(string $host): ?string
    {
        // Remove port if present
        $host = preg_replace('/:\d+$/', '', $host);

        // Ignore IP addresses (IPv4) - treat as root domain access
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            return null;
        }

        // Ignore apex domain luminai.ia.br - treat as root domain access
        if (preg_match('/^luminai\.ia\.br$/i', $host)) {
            return null;
        }

        // Check if it's a subdomain (contains a dot before localhost/domain)
        if (preg_match('/^([a-z0-9\-]+)\.(localhost|luminai\.ia\.br)$/i', $host, $matches)) {
            $subdomain = strtolower($matches[1]);

            // Ignore "www" subdomain - treat as root domain access
            if ($subdomain === 'www') {
                return null;
            }

            return $subdomain;
        }

        // No subdomain found (root domain access)
        return null;
    }
}