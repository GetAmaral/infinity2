<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
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
        private readonly Security $security,
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

        $request = $event->getRequest();
        $filters = $this->entityManager->getFilters();

        // Enable the organization filter
        if (!$filters->has('organization_filter')) {
            $this->logger->warning('Organization filter not configured in Doctrine');
            return;
        }

        // Determine organization ID based on request type
        $isApiRequest = str_starts_with($request->getPathInfo(), '/api/');

        if ($isApiRequest) {
            // For API requests: get organization from authenticated user
            $user = $this->security->getUser();
            $organizationId = ($user instanceof User && $user->getOrganization())
                ? $user->getOrganization()->getId()->toRfc4122()
                : null;
        } else {
            // For web requests: get organization from session
            $organizationId = $this->organizationContext->getOrganizationId();
        }

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

        $logContext = ['organization_id' => $organizationId];
        if (!$isApiRequest) {
            $logContext['organization_slug'] = $this->organizationContext->getOrganizationSlug();
        }

        $this->logger->debug('Organization filter enabled', $logContext);
    }
}