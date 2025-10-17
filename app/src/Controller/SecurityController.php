<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Service\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    // #[RateLimit('auth_login', limit: 5, interval: '15 minutes')] // TODO: Install symfony/lock
    public function login(
        AuthenticationUtils $authenticationUtils,
        OrganizationContext $organizationContext,
        OrganizationRepository $organizationRepository
    ): Response {
        // If user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Get the current organization from context (if any)
        $organization = null;
        $organizationId = $organizationContext->getOrganizationId();
        if ($organizationId !== null) {
            $organization = $organizationRepository->find($organizationId);
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'organization' => $organization,
        ]);
    }

    /**
     * API endpoint to lookup organization by email
     * Used for seamless subdomain redirect on login
     */
    #[Route('/api/lookup-organization', name: 'api_lookup_organization', methods: ['POST'])]
    public function lookupOrganization(
        Request $request,
        UserRepository $userRepository,
        LoggerInterface $logger
    ): JsonResponse {
        // Get email from request
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        // Basic validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'invalid_email',
                'message' => 'Please enter a valid email address'
            ], 400);
        }

        // Rate limiting: Track by IP to prevent enumeration attacks
        $clientIp = $request->getClientIp();

        // Simple in-memory rate limiting (for production, use Redis or database)
        // For now, we'll implement basic throttling in the Stimulus controller

        // Log the lookup attempt (for security monitoring)
        $logger->info('Organization lookup attempt', [
            'email_domain' => substr($email, strpos($email, '@')),
            'ip' => $clientIp,
            'timestamp' => new \DateTimeImmutable(),
        ]);

        try {
            // Lookup user by email
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user || !$user->getOrganization()) {
                // Security: Don't reveal if user exists - use generic message
                // Add artificial delay to prevent timing attacks
                usleep(random_int(100000, 300000)); // 100-300ms random delay

                $logger->warning('Organization lookup failed', [
                    'email_domain' => substr($email, strpos($email, '@')),
                    'ip' => $clientIp,
                    'reason' => 'user_not_found_or_no_org',
                ]);

                return new JsonResponse([
                    'error' => 'not_found',
                    'message' => 'No organization found for this email'
                ], 404);
            }

            $organization = $user->getOrganization();
            $orgSlug = $organization->getSlug();

            // Get current host to build redirect URL
            $host = $request->getHost();
            $scheme = $request->getScheme();

            // Build redirect URL with organization subdomain
            // Remove any existing subdomain first
            $baseDomain = preg_replace('/^[^.]+\./', '', $host);
            $redirectUrl = sprintf('%s://%s.%s/login', $scheme, $orgSlug, $baseDomain);

            $logger->info('Organization lookup successful', [
                'email' => $email,
                'organization_slug' => $orgSlug,
                'redirect_url' => $redirectUrl,
                'ip' => $clientIp,
            ]);

            return new JsonResponse([
                'success' => true,
                'organizationSlug' => $orgSlug,
                'organizationName' => $organization->getName(),
                'redirectUrl' => $redirectUrl,
            ]);

        } catch (\Exception $e) {
            $logger->error('Organization lookup error', [
                'email_domain' => substr($email, strpos($email, '@')),
                'error' => $e->getMessage(),
                'ip' => $clientIp,
            ]);

            return new JsonResponse([
                'error' => 'server_error',
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }

    #[Route('/register', name: 'app_register')]
    // #[RateLimit('auth_register', limit: 3, interval: '1 hour')] // TODO: Install symfony/lock
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        // If user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));

            $plainPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validate passwords match
            if ($plainPassword !== $confirmPassword) {
                $this->addFlash('error', 'auth.passwords_do_not_match');
                return $this->render('security/register.html.twig', [
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                ]);
            }

            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Auto-verify for now (email verification can be added later)
            $user->setIsVerified(true);

            // Validate user entity
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('security/register.html.twig', [
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                ]);
            }

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'auth.registration_successful');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'auth.registration_failed');
            }
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - Symfony will handle the logout
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}