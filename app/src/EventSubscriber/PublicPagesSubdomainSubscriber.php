<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Restricts public pages to root domain, www, and avelum subdomains only.
 * All other organization subdomains redirect to login when accessing public pages.
 */
class PublicPagesSubdomainSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 10],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Only check public routes (landing, about, contact, products, solutions, privacy, education)
        // Also check app_home (root landing page)
        if (!str_starts_with($route, 'public_') && $route !== 'app_home') {
            return;
        }

        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        // Allowed subdomains for public pages: root domain, www, avelum, and localhost (for dev)
        $allowedSubdomains = ['', 'www', 'avelum', 'localhost'];

        if (!in_array($subdomain, $allowedSubdomains, true)) {
            // Organization subdomain trying to access public pages - redirect to login
            $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $event->setController(function () use ($loginUrl) {
                return new RedirectResponse($loginUrl);
            });
        }
    }

    /**
     * Extract subdomain from host
     * Examples:
     * - localhost -> 'localhost'
     * - avelum.com.br -> ''
     * - www.avelum.com.br -> 'www'
     * - avelum.avelum.com.br -> 'avelum'
     * - acme-corp.avelum.com.br -> 'acme-corp'
     */
    private function extractSubdomain(string $host): string
    {
        // Remove port if present
        $host = explode(':', $host)[0];

        $parts = explode('.', $host);

        // If localhost or single part, return 'localhost'
        if (count($parts) <= 1 || $host === 'localhost') {
            return 'localhost';
        }

        // If 2 parts (domain.tld), no subdomain - return empty string
        if (count($parts) === 2) {
            return '';
        }

        // First part is the subdomain
        return $parts[0];
    }
}
