<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ListPreferencesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings/ajax/list-preferences')]
#[IsGranted('ROLE_USER')]
final class ListPreferencesController extends AbstractController
{
    public function __construct(
        private readonly ListPreferencesService $listPreferencesService
    ) {}

    /**
     * Get all list preferences
     */
    #[Route('', name: 'ajax_list_preferences_get', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'preferences' => $this->listPreferencesService->getPreferences(),
        ]);
    }

    /**
     * Get preferences for a specific entity
     */
    #[Route('/{entityName}', name: 'ajax_list_preferences_get_entity', methods: ['GET'])]
    public function getEntityPreferences(string $entityName): JsonResponse
    {
        return $this->json([
            'success' => true,
            'entityName' => $entityName,
            'preferences' => $this->listPreferencesService->getEntityPreferences($entityName),
        ]);
    }

    /**
     * Save preference for a specific entity (merges with existing)
     */
    #[Route('/{entityName}', name: 'ajax_list_preferences_save_entity', methods: ['POST', 'PUT'])]
    public function saveEntityPreferences(string $entityName, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Merge with existing preferences instead of replacing
        $currentPreferences = $this->listPreferencesService->getEntityPreferences($entityName);
        $mergedPreferences = array_merge($currentPreferences, $data);

        $this->listPreferencesService->saveEntityPreferences($entityName, $mergedPreferences);

        return $this->json([
            'success' => true,
            'entityName' => $entityName,
            'preferences' => $this->listPreferencesService->getEntityPreferences($entityName),
        ]);
    }

    /**
     * Save a single preference value
     */
    #[Route('/{entityName}/{key}', name: 'ajax_list_preferences_save_key', methods: ['POST', 'PUT'])]
    public function savePreferenceKey(string $entityName, string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['value'])) {
            return $this->json(['error' => 'Missing value parameter'], Response::HTTP_BAD_REQUEST);
        }

        $currentPreferences = $this->listPreferencesService->getEntityPreferences($entityName);
        $currentPreferences[$key] = $data['value'];

        $this->listPreferencesService->saveEntityPreferences($entityName, $currentPreferences);

        return $this->json([
            'success' => true,
            'entityName' => $entityName,
            'key' => $key,
            'value' => $data['value'],
        ]);
    }

    /**
     * Clear all list preferences
     */
    #[Route('', name: 'ajax_list_preferences_clear', methods: ['DELETE'])]
    public function clearPreferences(): JsonResponse
    {
        $this->listPreferencesService->clearPreferences();

        return $this->json([
            'success' => true,
            'message' => 'List preferences cleared',
        ]);
    }
}