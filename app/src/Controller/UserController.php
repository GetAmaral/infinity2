<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ListPreferencesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'user_index')]
    public function index(
        UserRepository $repository,
        ListPreferencesService $listPreferencesService
    ): Response
    {
        $users = $repository->findAll();

        // Get saved view preference from list preferences
        $preferences = $listPreferencesService->getEntityPreferences('users');
        $savedView = $preferences['view'] ?? 'grid';

        return $this->render('user/index.html.twig', [
            // Generic entity list variables for base template
            'entities' => $users,
            'entity_name' => 'user',
            'entity_name_plural' => 'users',
            'page_icon' => 'bi bi-people',
            'default_view' => $savedView, // Use saved preference
            'enable_search' => true,
            'enable_filters' => true,
            'enable_create_button' => true,

            // Backward compatibility: keep old variable name
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}', name: 'user_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/api/search', name: 'user_api_search', methods: ['GET'])]
    public function apiSearch(Request $request, UserRepository $repository): Response
    {
        $query = $request->query->get('q', '');
        $limit = min(10, max(1, (int) $request->query->get('limit', 10)));

        // For now, use findAll since UserRepository doesn't have searchByName yet
        $allUsers = $repository->findAll();

        // Filter by name or email if query provided
        if ($query) {
            $query = strtolower($query);
            $allUsers = array_filter($allUsers, function(User $user) use ($query) {
                return str_contains(strtolower($user->getName()), $query) ||
                       str_contains(strtolower($user->getEmail()), $query);
            });
        }

        // Limit results
        $users = array_slice($allUsers, 0, $limit);

        return $this->json([
            'users' => array_map(function (User $user) {
                return [
                    'id' => $user->getId()->toString(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'organizationId' => $user->getOrganization()?->getId()->toString(),
                    'organizationName' => $user->getOrganization()?->getName(),
                    'createdAt' => $user->getCreatedAt()->format('c'),
                ];
            }, $users),
        ]);
    }
}