<?php

declare(strict_types=1);

namespace App\Security;

use App\MultiTenant\TenantContext;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Login form authenticator
 *
 * Simplified - validation logic delegated to TenantGuard
 * Uses isGranted() for role checks (not in_array)
 */
final class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TenantContext $tenantContext,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly UserProviderInterface $userProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                return $this->userProvider->loadUserByIdentifier($userIdentifier);
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $tenantId = $this->tenantContext->getTenantId();
        $tenantSlug = $this->tenantContext->getTenantSlug();

        // Basic validation at login time (TenantGuard will enforce on subsequent requests)
        // ✅ Use isGranted() instead of in_array()
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $this->logger->info('User authenticated successfully', [
            'email' => $user->getUserIdentifier(),
            'tenant_id' => $tenantId,
            'tenant_slug' => $tenantSlug,
            'is_admin' => $isAdmin,
            'host' => $request->getHost(),
        ]);

        // Quick validation: Non-admin at root domain → reject
        // (TenantGuard will handle redirects on next request)
        if ($tenantId === null && !$isAdmin) {
            $this->logger->warning('Non-admin attempted login at root domain', [
                'email' => $user->getUserIdentifier(),
            ]);

            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('user.auth.error.root_domain_access_contact_admin', [], 'user')
            );
        }

        // Quick validation: Non-admin at subdomain → must match org
        if ($tenantId !== null && !$isAdmin) {
            $userOrgId = $user->getOrganization()?->getId()?->toRfc4122();

            if ($userOrgId !== $tenantId) {
                $this->logger->warning('User attempted login to wrong tenant subdomain', [
                    'email' => $user->getUserIdentifier(),
                    'user_org' => $user->getOrganization()?->getSlug(),
                    'subdomain_org' => $tenantSlug,
                ]);

                throw new CustomUserMessageAuthenticationException(
                    $this->translator->trans('user.auth.error.wrong_organization', [], 'user')
                );
            }
        }

        // Redirect to target path or default
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            // Don't redirect to AJAX endpoints or API routes
            if (!str_contains($targetPath, '/ajax/') && !str_contains($targetPath, '/api/')) {
                return new RedirectResponse($targetPath);
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
