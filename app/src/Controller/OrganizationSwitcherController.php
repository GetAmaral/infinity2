<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Service\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for organization switching (ROLE_ADMIN and ROLE_SUPER_ADMIN only)
 *
 * This controller allows admins to:
 * 1. Switch their own organization (changes database + session)
 * 2. Clear organization context for root access (session only)
 */
#[Route('/organization-switcher')]
#[IsGranted('ROLE_ADMIN')]
final class OrganizationSwitcherController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly OrganizationRepository $organizationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Switch to a specific organization
     * This changes the user's organization in the database permanently
     */
    #[Route('/switch/{id}', name: 'app_organization_switcher_switch', methods: ['POST'])]
    public function switch(string $id, Request $request): Response
    {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('organization_switch_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('organization_switcher.error.invalid_token', [], 'organization'));
            return $this->redirectToRoute('app_home');
        }

        // Get current user
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', $this->translator->trans('organization_switcher.error.not_logged_in', [], 'organization'));
            return $this->redirectToRoute('app_home');
        }

        // Load organization
        $organization = $this->organizationRepository->find($id);

        if ($organization === null) {
            $this->addFlash('error', $this->translator->trans('organization_switcher.error.not_found', [], 'organization'));
            return $this->redirectToRoute('app_home');
        }

        // Update user's organization in database
        $user->setOrganization($organization);
        $this->entityManager->flush();

        // Set organization in context/session
        $this->organizationContext->setOrganization($organization);

        $this->addFlash('success', $this->translator->trans('organization_switcher.success.switched', [
            '%name%' => $organization->getName()
        ], 'organization'));

        // Redirect back to referer or home
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }

    /**
     * Clear organization context (access as root/admin)
     */
    #[Route('/clear', name: 'app_organization_switcher_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('organization_clear', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('organization_switcher.error.invalid_token', [], 'organization'));
            return $this->redirectToRoute('app_home');
        }

        // Clear organization context
        $this->organizationContext->clearOrganization();

        $this->addFlash('success', $this->translator->trans('organization_switcher.success.cleared', [], 'organization'));

        // Redirect back to referer or home
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}