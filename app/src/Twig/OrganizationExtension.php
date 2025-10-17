<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use App\Service\OrganizationContext;
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
        private readonly OrganizationContext $organizationContext,
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
        $organizationId = $this->organizationContext->getOrganizationId();

        if ($organizationId === null) {
            return null;
        }

        // Temporarily disable organization filter to fetch the current organization itself
        $filters = $this->entityManager->getFilters();
        $filterWasEnabled = $filters->isEnabled('organization_filter');

        if ($filterWasEnabled) {
            $filters->disable('organization_filter');
        }

        $organization = $this->organizationRepository->find($organizationId);

        if ($filterWasEnabled) {
            $filter = $filters->enable('organization_filter');
            // Re-set the parameter after re-enabling
            $filter->setParameter('organization_id', $organizationId, 'string');
        }

        return $organization;
    }

    /**
     * Get the current organization slug
     */
    public function getCurrentOrganizationSlug(): ?string
    {
        return $this->organizationContext->getOrganizationSlug();
    }

    /**
     * Check if there's an active organization
     */
    public function hasActiveOrganization(): bool
    {
        return $this->organizationContext->hasActiveOrganization();
    }

    /**
     * Get all available organizations (for admin users)
     */
    public function getAvailableOrganizations(): array
    {
        if (!$this->canSwitchOrganization()) {
            return [];
        }

        // Temporarily disable organization filter to fetch all organizations
        $filters = $this->entityManager->getFilters();
        $filterWasEnabled = $filters->isEnabled('organization_filter');
        $organizationId = null;

        if ($filterWasEnabled) {
            // Save the organization ID before disabling
            $organizationId = $this->organizationContext->getOrganizationId();
            $filters->disable('organization_filter');
        }

        $organizations = $this->organizationRepository->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        if ($filterWasEnabled && $organizationId !== null) {
            $filter = $filters->enable('organization_filter');
            // Re-set the parameter after re-enabling
            $filter->setParameter('organization_id', $organizationId, 'string');
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