<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Invalid API token format. Use: Authorization: Bearer <token>');
        }

        $apiToken = substr($authHeader, 7); // Remove 'Bearer ' prefix

        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('API token is required');
        }

        return new SelfValidatingPassport(
            new UserBadge($apiToken, function ($apiToken) {
                $user = $this->userRepository->findOneBy(['apiToken' => $apiToken]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }

                if (!$user->isApiTokenValid()) {
                    throw new CustomUserMessageAuthenticationException('API token has expired');
                }

                if (!$user->isVerified()) {
                    throw new CustomUserMessageAuthenticationException('User account is not verified');
                }

                if ($user->isLocked()) {
                    throw new CustomUserMessageAuthenticationException('User account is locked');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Allow the request to continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ], Response::HTTP_UNAUTHORIZED);
    }
}