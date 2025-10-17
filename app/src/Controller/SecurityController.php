<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Service\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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