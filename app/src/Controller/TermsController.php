<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class TermsController extends AbstractController
{
    #[Route('/terms', name: 'app_terms')]
    #[IsGranted('ROLE_USER')]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check if user has ROLE_STUDENT
        $isStudent = in_array('ROLE_STUDENT', $user->getRoles(), true);

        return $this->render('terms/index.html.twig', [
            'user' => $user,
            'isStudent' => $isStudent,
            'hasSignedTerms' => $user->hasSignedTerms(),
        ]);
    }

    #[Route('/terms/accept', name: 'app_terms_accept', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function accept(
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Verify CSRF token
        $csrfToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('accept_terms', $csrfToken)) {
            $this->addFlash('error', $translator->trans('terms.error.invalid.csrf', [], 'terms'));
            return $this->redirectToRoute('app_terms');
        }

        // Validate checkbox is checked
        $termsAccepted = $request->request->get('general_terms_accepted') === '1';
        if (!$termsAccepted) {
            $this->addFlash('error', $translator->trans('terms.error.must.accept.all', [], 'terms'));
            return $this->redirectToRoute('app_terms');
        }

        // Update user
        $user->setTermsSigned(true);
        $entityManager->flush();

        $this->addFlash('success', $translator->trans('terms.success.accepted', [], 'terms'));

        // Redirect to home page
        return $this->redirectToRoute('app_home');
    }
}
