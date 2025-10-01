<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\OrganizationContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Custom authenticator that validates user belongs to organization from subdomain
 * ROLE_ADMIN and ROLE_SUPER_ADMIN can login to any organization or root domain
 */
final class OrganizationAwareAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly OrganizationContext $organizationContext,
        private readonly LoggerInterface $logger
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
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

        // Validate organization access
        $organizationId = $this->organizationContext->getOrganizationId();
        $organizationSlug = $this->organizationContext->getOrganizationSlug();

        // Check if user has admin/super admin role
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);

        if ($organizationId === null) {
            // Root domain access - only allow admins and super admins
            if (!$isAdmin && !$isSuperAdmin) {
                $this->logger->warning('Non-admin user attempted to login at root domain', [
                    'email' => $user->getUserIdentifier(),
                ]);
                throw new CustomUserMessageAuthenticationException(
                    'You must access the system through your organization subdomain. Please contact your administrator.'
                );
            }

            $this->logger->info('Admin user logged in at root domain', [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ]);
        } else {
            // Subdomain access - validate user belongs to organization (unless admin)
            if (!$isAdmin && !$isSuperAdmin) {
                $userOrganizationId = $user->getOrganization()?->getId()?->toRfc4122();

                if ($userOrganizationId !== $organizationId) {
                    $this->logger->warning('User attempted to login to wrong organization subdomain', [
                        'email' => $user->getUserIdentifier(),
                        'user_organization_id' => $userOrganizationId,
                        'subdomain_organization_id' => $organizationId,
                        'subdomain_slug' => $organizationSlug,
                    ]);
                    throw new CustomUserMessageAuthenticationException(
                        'You do not have access to this organization. Please use your organization subdomain.'
                    );
                }
            }

            $this->logger->info('User logged in successfully', [
                'email' => $user->getUserIdentifier(),
                'organization_slug' => $organizationSlug,
                'is_admin' => $isAdmin || $isSuperAdmin,
            ]);
        }

        // Redirect to target path or default
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}