<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Configures and enables the Doctrine OrganizationFilter based on active organization
 */
final class OrganizationFilterConfigurator implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationContext $organizationContext,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after SubdomainOrganizationSubscriber (priority 32)
            // Run after security (priority 8)
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $filters = $this->entityManager->getFilters();

        // Enable the organization filter
        if (!$filters->has('organization_filter')) {
            $this->logger->warning('Organization filter not configured in Doctrine');
            return;
        }

        $organizationId = $this->organizationContext->getOrganizationId();

        if ($organizationId === null) {
            // No organization context - disable filter (root domain / admin access)
            if ($filters->isEnabled('organization_filter')) {
                $filters->disable('organization_filter');
                $this->logger->debug('Organization filter disabled - no active organization');
            }
            return;
        }

        // Enable filter and set parameter
        if (!$filters->isEnabled('organization_filter')) {
            $filter = $filters->enable('organization_filter');
        } else {
            $filter = $filters->getFilter('organization_filter');
        }

        // Always set/update the parameter
        $filter->setParameter('organization_id', $organizationId, 'string');

        $this->logger->debug('Organization filter enabled', [
            'organization_id' => $organizationId,
            'organization_slug' => $this->organizationContext->getOrganizationSlug(),
        ]);
    }
}