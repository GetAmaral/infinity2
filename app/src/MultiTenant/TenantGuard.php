<?php

declare(strict_types=1);

namespace App\MultiTenant;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * TenantGuard - Centralized access control for multi-tenant system
 *
 * Runs on EVERY request to:
 * 1. Detect tenant from subdomain OR user
 * 2. Validate user has access to tenant
 * 3. Redirect/block unauthorized access
 * 4. Enable Doctrine tenant filter
 *
 * Priority: Runs early (16) after firewall (8) but before controllers
 */
final class TenantGuard implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after security firewall (priority 8) but before controllers
            KernelEvents::REQUEST => ['onKernelRequest', 16],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $host = $request->getHost();
        $isApiRequest = str_starts_with($request->getPathInfo(), '/api/');

        // STEP 1: Determine tenant (subdomain first, then user)
        $subdomain = $this->tenantContext->extractTenantSlugFromHost($host);
        $tenant = null;

        if ($subdomain !== null) {
            // Subdomain access - load tenant from database
            $tenant = $this->tenantContext->loadTenantBySlug($subdomain);

            if (!$tenant) {
                // Organization not found for subdomain
                $this->logger->warning('Tenant not found for subdomain', [
                    'subdomain' => $subdomain,
                    'host' => $host,
                ]);

                // Show 404 error page for invalid subdomain
                $rootDomain = preg_replace('/^' . preg_quote($subdomain, '/') . '\./', '', $host);
                $content = $this->twig->render('error/organization_not_found.html.twig', [
                    'slug' => $subdomain,
                    'rootDomain' => $rootDomain,
                ]);

                $response = new Response($content, Response::HTTP_NOT_FOUND);
                $event->setResponse($response);
                return;
            }

            $this->logger->debug('Tenant detected from subdomain', [
                'subdomain' => $subdomain,
                'tenant_id' => $tenant->getId()->toRfc4122(),
                'tenant_name' => $tenant->getName(),
            ]);
        } else {
            // Root domain access - get tenant from user (if authenticated)
            $user = $this->security->getUser();

            if ($user instanceof User) {
                $tenant = $user->getOrganization();

                if ($tenant) {
                    $this->logger->debug('Tenant detected from user organization', [
                        'user_id' => $user->getId()->toRfc4122(),
                        'tenant_id' => $tenant->getId()->toRfc4122(),
                    ]);
                }
            }
        }

        // Store tenant in context (single source of truth!)
        $this->tenantContext->setTenant($tenant);

        // STEP 2: Validate user access
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            // Not authenticated - let security firewall handle it
            $this->configureDoctrineTenantFilter();
            return;
        }

        // ✅ Use isGranted() instead of in_array()
        if ($this->security->isGranted('ROLE_ADMIN')) {
            // ADMIN: Can access ANY tenant (subdomain or root)
            $this->logger->info('Admin access granted', [
                'user' => $user->getUserIdentifier(),
                'tenant' => $tenant?->getSlug(),
                'host' => $host,
            ]);

            $this->configureDoctrineTenantFilter();
            return;
        }

        // NON-ADMIN user validation
        $userOrg = $user->getOrganization();

        if ($subdomain !== null) {
            // Subdomain access - validate user belongs to this tenant
            if (!$userOrg || $userOrg->getId()->toRfc4122() !== $tenant->getId()->toRfc4122()) {
                // User does NOT belong to this subdomain's tenant
                $this->logger->warning('User attempted to access wrong tenant subdomain', [
                    'user' => $user->getUserIdentifier(),
                    'user_org' => $userOrg?->getSlug(),
                    'subdomain_org' => $subdomain,
                ]);

                if ($isApiRequest) {
                    // API: Block with 403 Forbidden
                    throw new AccessDeniedHttpException('You do not have access to this organization');
                } else {
                    // HTTP: Redirect to user's correct tenant subdomain
                    $response = $this->redirectToUserTenantSubdomain($userOrg);
                    $event->setResponse($response);
                    return;
                }
            }

            // ✅ User belongs to subdomain tenant - allow access
            $this->logger->debug('User access validated for subdomain', [
                'user' => $user->getUserIdentifier(),
                'tenant' => $tenant->getSlug(),
            ]);
        } else {
            // Root domain access - non-admin users should use their tenant subdomain
            if ($isApiRequest) {
                // API at root domain
                if (!$userOrg) {
                    // User has no organization - block
                    throw new AccessDeniedHttpException('User must belong to an organization to use the API');
                }

                // ✅ User has org - allow API access (filtered by their org)
                $this->logger->debug('API access at root domain', [
                    'user' => $user->getUserIdentifier(),
                    'tenant' => $userOrg->getSlug(),
                ]);
            } else {
                // HTTP at root domain - redirect to user's tenant subdomain
                $this->logger->info('Redirecting non-admin user from root to their tenant', [
                    'user' => $user->getUserIdentifier(),
                    'user_org' => $userOrg?->getSlug(),
                ]);

                $response = $this->redirectToUserTenantSubdomain($userOrg);
                $event->setResponse($response);
                return;
            }
        }

        // STEP 3: Enable Doctrine filter with tenant from context
        $this->configureDoctrineTenantFilter();
    }

    /**
     * Enable and configure Doctrine tenant filter
     */
    private function configureDoctrineTenantFilter(): void
    {
        $filters = $this->entityManager->getFilters();

        if (!$filters->has('tenant_filter')) {
            $this->logger->warning('Tenant filter not configured in Doctrine');
            return;
        }

        $tenantId = $this->tenantContext->getTenantId();

        if ($tenantId === null) {
            // No tenant context - disable filter (root domain admin access)
            if ($filters->isEnabled('tenant_filter')) {
                $filters->disable('tenant_filter');
                $this->logger->debug('Tenant filter disabled - no active tenant');
            }
            return;
        }

        // Enable filter and set parameter
        if (!$filters->isEnabled('tenant_filter')) {
            $filter = $filters->enable('tenant_filter');
        } else {
            $filter = $filters->getFilter('tenant_filter');
        }

        $filter->setParameter('tenant_id', $tenantId, 'string');

        $this->logger->debug('Tenant filter enabled', [
            'tenant_id' => $tenantId,
            'tenant_slug' => $this->tenantContext->getTenantSlug(),
        ]);
    }

    /**
     * Redirect user to their tenant's subdomain
     */
    private function redirectToUserTenantSubdomain(?\App\Entity\Organization $userOrg): Response
    {
        if (!$userOrg) {
            // User has no organization - show error
            $content = $this->twig->render('error/no_organization.html.twig');
            return new Response($content, Response::HTTP_FORBIDDEN);
        }

        // Build subdomain URL
        $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseDomain = preg_replace('/^[^.]+\./', '', $currentHost); // Remove subdomain if present

        // If base domain is just "localhost" (no dots), subdomain is slug.localhost
        if ($baseDomain === $currentHost || !str_contains($baseDomain, '.')) {
            $baseDomain = $currentHost;
        }

        $subdomainHost = $userOrg->getSlug() . '.' . $baseDomain;
        $protocol = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http';
        $redirectUrl = $protocol . '://' . $subdomainHost . '/';

        $this->logger->info('Redirecting to user tenant subdomain', [
            'from' => $currentHost,
            'to' => $subdomainHost,
            'org' => $userOrg->getSlug(),
        ]);

        return new RedirectResponse($redirectUrl);
    }
}
