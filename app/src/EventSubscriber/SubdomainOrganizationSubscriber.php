<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Repository\OrganizationRepository;
use App\Service\OrganizationContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Detects organization from subdomain and sets it in OrganizationContext
 */
final class SubdomainOrganizationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly OrganizationRepository $organizationRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run early to set organization context before security
            KernelEvents::REQUEST => ['onKernelRequest', 32],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $host = $request->getHost();

        // Extract slug from subdomain
        $slug = $this->organizationContext->extractSlugFromHost($host);

        if ($slug === null) {
            // Root domain access (no subdomain)
            // Keep organization if it was set via switcher (admin functionality)
            // Only log if there's no active organization
            if (!$this->organizationContext->hasActiveOrganization()) {
                $this->logger->info('Root domain access, no organization context set', [
                    'host' => $host,
                ]);
            } else {
                $this->logger->debug('Root domain access, keeping manually selected organization', [
                    'host' => $host,
                    'organization_slug' => $this->organizationContext->getOrganizationSlug(),
                ]);
            }
            return;
        }

        // Check if organization is already set in session with the same slug
        if ($this->organizationContext->getOrganizationSlug() === $slug) {
            $this->logger->debug('Organization already set in session', [
                'slug' => $slug,
                'organization_id' => $this->organizationContext->getOrganizationId(),
            ]);
            return;
        }

        // Load organization from database by slug
        $organization = $this->organizationRepository->findOneBy(['slug' => $slug]);

        if ($organization === null) {
            // Organization not found - clear context
            $this->organizationContext->clearOrganization();
            $this->logger->warning('Organization not found for subdomain', [
                'slug' => $slug,
                'host' => $host,
            ]);
            return;
        }

        // Set organization in context
        $this->organizationContext->setOrganization($organization);
        $this->logger->info('Organization context set from subdomain', [
            'slug' => $slug,
            'organization_id' => $organization->getId()->toRfc4122(),
            'organization_name' => $organization->getName(),
            'host' => $host,
        ]);
    }
}