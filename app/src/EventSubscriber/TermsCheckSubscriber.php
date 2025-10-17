<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Checks if authenticated users have signed terms of use
 * Redirects to terms page if not signed
 */
final class TermsCheckSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'app_terms',
        'app_terms_accept',
        'app_logout',
        'app_login',
        'app_register',
    ];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after security authentication (priority < 8)
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Skip check for allowed routes
        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        // Skip check for API routes, profiler, and system routes
        if ($this->isSystemRoute($route)) {
            return;
        }

        // Get authenticated user
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Check if user has signed terms
        if (!$user->hasSignedTerms()) {
            $this->logger->info('User has not signed terms, redirecting to terms page', [
                'user_id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'current_route' => $route,
            ]);

            $response = new RedirectResponse($this->urlGenerator->generate('app_terms'));
            $event->setResponse($response);
        }
    }

    private function isSystemRoute(?string $route): bool
    {
        if ($route === null) {
            return false;
        }

        // Skip API routes
        if (str_starts_with($route, 'api_')) {
            return true;
        }

        // Skip profiler and debug routes
        if (str_starts_with($route, '_')) {
            return true;
        }

        // Skip health check routes
        if (str_starts_with($route, 'health_')) {
            return true;
        }

        return false;
    }
}
