<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\TreeFlowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/treeflows', name: 'api_treeflow_')]
class TreeFlowJsonController extends AbstractController
{
    public function __construct(
        private readonly TreeFlowRepository $treeFlowRepository
    ) {
    }

    #[Route('/{id}/json', name: 'get_json', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getJson(string $id): JsonResponse
    {
        // Use cached repository method for better performance
        $treeFlow = $this->treeFlowRepository->findOneByIdWithCache($id);

        if (!$treeFlow) {
            return new JsonResponse(
                ['error' => 'TreeFlow not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse([
            'id' => $treeFlow->getId()->toRfc4122(),
            'jsonStructure' => $treeFlow->getJsonStructure(),
            'talkFlow' => $treeFlow->getTalkFlow(),
        ]);
    }
}
