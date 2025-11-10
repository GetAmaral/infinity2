<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\SidebarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/sidebar', name: 'api_sidebar_')]
#[IsGranted('ROLE_USER')]
final class SidebarController extends AbstractController
{
    public function __construct(
        private readonly SidebarService $sidebarService,
    ) {}

    #[Route('/preferences', name: 'preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $user = $this->getUser();
        $preferences = $this->sidebarService->getPreferences($user);

        return $this->json($preferences);
    }

    #[Route('/state', name: 'state', methods: ['POST'])]
    public function updateState(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $this->sidebarService->updateState(
            $user,
            $data['collapsed'] ?? false,
            $data['expandedSections'] ?? []
        );

        return $this->json(['success' => true]);
    }

    #[Route('/favorites', name: 'favorites_list', methods: ['GET'])]
    public function getFavorites(): JsonResponse
    {
        $user = $this->getUser();
        $favorites = $this->sidebarService->getFavorites($user);

        return $this->json($favorites);
    }

    #[Route('/favorites', name: 'favorites_add', methods: ['POST'])]
    public function addFavorite(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $this->sidebarService->addFavorite($user, $data['menuKey']);

        return $this->json(['success' => true]);
    }

    #[Route('/favorites/{menuKey}', name: 'favorites_remove', methods: ['DELETE'])]
    public function removeFavorite(string $menuKey): JsonResponse
    {
        $user = $this->getUser();
        $this->sidebarService->removeFavorite($user, $menuKey);

        return $this->json(['success' => true]);
    }

    #[Route('/favorites/reorder', name: 'favorites_reorder', methods: ['PUT'])]
    public function reorderFavorites(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $this->sidebarService->reorderFavorites($user, $data['items'] ?? []);

        return $this->json(['success' => true]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json(['results' => []]);
        }

        $results = $this->sidebarService->searchMenuItems($query);

        return $this->json(['results' => $results]);
    }
}
