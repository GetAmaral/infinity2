<?php

declare(strict_types=1);

namespace App\MultiTenant;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Single source of truth for tenant/organization context
 *
 * Handles both stateless (API) and stateful (web) requests:
 * - API requests: Store tenant in request attributes (no session)
 * - Web requests: Store tenant in session
 */
final class TenantContext
{
    private const SESSION_KEY = '_tenant_id';
    private const SESSION_SLUG_KEY = '_tenant_slug';
    private const REQUEST_ATTR_KEY = '_tenant';
    private const REQUEST_ATTR_ID_KEY = '_tenant_id';
    private const REQUEST_ATTR_SLUG_KEY = '_tenant_slug';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly OrganizationRepository $organizationRepository
    ) {
    }

    /**
     * Set the active tenant
     * - For stateless requests (API): Store in request attributes
     * - For stateful requests (web): Store in session
     */
    public function setTenant(?Organization $organization): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        // For stateless requests (API), use request attributes
        if ($this->isStatelessRequest($request)) {
            if ($organization === null) {
                $request->attributes->remove(self::REQUEST_ATTR_KEY);
                $request->attributes->remove(self::REQUEST_ATTR_ID_KEY);
                $request->attributes->remove(self::REQUEST_ATTR_SLUG_KEY);
            } else {
                $request->attributes->set(self::REQUEST_ATTR_KEY, $organization);
                $request->attributes->set(self::REQUEST_ATTR_ID_KEY, $organization->getId()->toRfc4122());
                $request->attributes->set(self::REQUEST_ATTR_SLUG_KEY, $organization->getSlug());
            }
            return;
        }

        // For stateful requests (web), use session
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        if ($organization === null) {
            $session->remove(self::SESSION_KEY);
            $session->remove(self::SESSION_SLUG_KEY);
            return;
        }

        $session->set(self::SESSION_KEY, $organization->getId()->toRfc4122());
        $session->set(self::SESSION_SLUG_KEY, $organization->getSlug());
    }

    /**
     * Get the active tenant ID
     * - For stateless requests (API): Get from request attributes
     * - For stateful requests (web): Get from session
     */
    public function getTenantId(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        // For stateless requests (API), use request attributes
        if ($this->isStatelessRequest($request)) {
            return $request->attributes->get(self::REQUEST_ATTR_ID_KEY);
        }

        // For stateful requests (web), use session
        if (!$request->hasSession()) {
            return null;
        }

        return $request->getSession()->get(self::SESSION_KEY);
    }

    /**
     * Get the active tenant slug
     * - For stateless requests (API): Get from request attributes
     * - For stateful requests (web): Get from session
     */
    public function getTenantSlug(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        // For stateless requests (API), use request attributes
        if ($this->isStatelessRequest($request)) {
            return $request->attributes->get(self::REQUEST_ATTR_SLUG_KEY);
        }

        // For stateful requests (web), use session
        if (!$request->hasSession()) {
            return null;
        }

        return $request->getSession()->get(self::SESSION_SLUG_KEY);
    }

    /**
     * Get the active tenant entity
     * - For stateless requests (API): Get from request attributes
     * - For stateful requests (web): Load from database using session ID
     */
    public function getTenant(): ?Organization
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        // For stateless requests (API), tenant is stored directly in request attributes
        if ($this->isStatelessRequest($request)) {
            return $request->attributes->get(self::REQUEST_ATTR_KEY);
        }

        // For stateful requests (web), load from database using session ID
        $tenantId = $this->getTenantId();

        if ($tenantId === null) {
            return null;
        }

        return $this->organizationRepository->find($tenantId);
    }

    /**
     * Check if a tenant is currently active
     */
    public function hasTenant(): bool
    {
        return $this->getTenantId() !== null;
    }

    /**
     * Clear the active tenant
     */
    public function clearTenant(): void
    {
        $this->setTenant(null);
    }

    /**
     * Load tenant from database by slug
     */
    public function loadTenantBySlug(string $slug): ?Organization
    {
        return $this->organizationRepository->findOneBy(['slug' => $slug]);
    }

    /**
     * Check if current request is stateless (API request)
     */
    private function isStatelessRequest($request): bool
    {
        // Check if request path starts with /api/
        $pathInfo = $request->getPathInfo();
        return str_starts_with($pathInfo, '/api/');
    }

    /**
     * Extract tenant slug from subdomain
     *
     * Examples:
     * - "acme-corporation.localhost" → "acme-corporation"
     * - "localhost" → null (root domain, no tenant)
     * - "www.luminai.ia.br" → null (www is not a tenant)
     * - "luminai.ia.br" → null (apex domain, no tenant)
     * - "91.98.137.175" → null (IP address, no tenant)
     */
    public function extractTenantSlugFromHost(string $host): ?string
    {
        // Remove port if present
        $host = preg_replace('/:\d+$/', '', $host);

        // Ignore IP addresses (IPv4) - treat as root domain access
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            return null;
        }

        // Ignore apex domains - treat as root domain access
        if (preg_match('/^(avelum\.com\.br|luminai\.ia\.br)$/i', $host)) {
            return null;
        }

        // Check if it's a subdomain (contains a dot before localhost/domain)
        if (preg_match('/^([a-z0-9\-]+)\.(localhost|avelum\.com\.br|luminai\.ia\.br)$/i', $host, $matches)) {
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
