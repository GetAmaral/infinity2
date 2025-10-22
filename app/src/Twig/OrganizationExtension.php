<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use App\MultiTenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for organization-related functions
 */
final class OrganizationExtension extends AbstractExtension
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OrganizationRepository $organizationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_organization', [$this, 'getCurrentOrganization']),
            new TwigFunction('current_organization_slug', [$this, 'getCurrentOrganizationSlug']),
            new TwigFunction('has_active_organization', [$this, 'hasActiveOrganization']),
            new TwigFunction('available_organizations', [$this, 'getAvailableOrganizations']),
            new TwigFunction('can_switch_organization', [$this, 'canSwitchOrganization']),
        ];
    }

    /**
     * Get the current active organization
     */
    public function getCurrentOrganization(): ?Organization
    {
        $tenantId = $this->tenantContext->getTenantId();

        if ($tenantId === null) {
            return null;
        }

        // Temporarily disable tenant filter to fetch the current organization itself
        $filters = $this->entityManager->getFilters();
        $filterWasEnabled = $filters->isEnabled('tenant_filter');

        if ($filterWasEnabled) {
            $filters->disable('tenant_filter');
        }

        $organization = $this->organizationRepository->find($tenantId);

        if ($filterWasEnabled) {
            $filter = $filters->enable('tenant_filter');
            // Re-set the parameter after re-enabling
            $filter->setParameter('tenant_id', $tenantId, 'string');
        }

        return $organization;
    }

    /**
     * Get the current organization slug
     */
    public function getCurrentOrganizationSlug(): ?string
    {
        return $this->tenantContext->getTenantSlug();
    }

    /**
     * Check if there's an active organization
     */
    public function hasActiveOrganization(): bool
    {
        return $this->tenantContext->hasTenant();
    }

    /**
     * Get all available organizations (for admin users)
     */
    public function getAvailableOrganizations(): array
    {
        if (!$this->canSwitchOrganization()) {
            return [];
        }

        // Temporarily disable tenant filter to fetch all organizations
        $filters = $this->entityManager->getFilters();
        $filterWasEnabled = $filters->isEnabled('tenant_filter');
        $tenantId = null;

        if ($filterWasEnabled) {
            // Save the tenant ID before disabling
            $tenantId = $this->tenantContext->getTenantId();
            $filters->disable('tenant_filter');
        }

        $organizations = $this->organizationRepository->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        if ($filterWasEnabled && $tenantId !== null) {
            $filter = $filters->enable('tenant_filter');
            // Re-set the parameter after re-enabling
            $filter->setParameter('tenant_id', $tenantId, 'string');
        }

        return $organizations;
    }

    /**
     * Check if current user can switch organizations
     * Only ROLE_ADMIN and ROLE_SUPER_ADMIN can switch
     */
    public function canSwitchOrganization(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN');
    }
}