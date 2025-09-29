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

#[Route('/api-tokens')]
#[IsGranted('ROLE_USER')]
class ApiTokenController extends AbstractController
{
    #[Route('', name: 'api_token_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('api_token/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/generate', name: 'api_token_generate', methods: ['POST'])]
    public function generate(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('generate_token', $request->request->get('_token'))) {
            $this->addFlash('error', 'token.csrf_invalid');
            return $this->redirectToRoute('api_token_index');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Generate new API token
        $user->generateApiToken(30); // 30 days validity
        $entityManager->flush();

        $this->addFlash('success', 'token.generated_successfully');

        return $this->redirectToRoute('api_token_index');
    }

    #[Route('/revoke', name: 'api_token_revoke', methods: ['POST'])]
    public function revoke(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('revoke_token', $request->request->get('_token'))) {
            $this->addFlash('error', 'token.csrf_invalid');
            return $this->redirectToRoute('api_token_index');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Revoke API token
        $user->revokeApiToken();
        $entityManager->flush();

        $this->addFlash('success', 'token.revoked_successfully');

        return $this->redirectToRoute('api_token_index');
    }
}