<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserPreferencesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    private const SUPPORTED_LOCALES = [
        'en' => 'language.en',
        'pt_BR' => 'language.pt_BR',
    ];

    public function __construct(
        private readonly UserPreferencesService $preferencesService
    ) {}

    #[Route('/settings', name: 'app_settings')]
    public function index(Request $request): Response
    {
        $currentLocale = $request->getLocale();
        $userPreferences = $this->preferencesService->getUserPreferences();

        return $this->render('settings/index.html.twig', [
            'current_locale' => $currentLocale,
            'supported_locales' => self::SUPPORTED_LOCALES,
            'user_preferences' => $userPreferences,
        ]);
    }

    #[Route('/settings/locale', name: 'app_settings_locale', methods: ['POST'])]
    public function changeLocale(Request $request): Response
    {
        $locale = $request->request->get('locale');

        if (!$locale || !array_key_exists($locale, self::SUPPORTED_LOCALES)) {
            $this->addFlash('error', 'Invalid language selection.');
            return $this->redirectToRoute('app_settings');
        }

        // Store in session
        $request->getSession()->set('_locale', $locale);

        // Save to user preferences
        $this->preferencesService->savePreference('locale', $locale);

        // Add success message
        $this->addFlash('success', 'settings.language_saved');

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings/ajax/preferences', name: 'ajax_save_preferences', methods: ['POST'])]
    public function savePreferences(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }

            $success = $this->preferencesService->saveUserPreferences(null, $data);

            if (!$success) {
                return new JsonResponse(['error' => 'Failed to save preferences'], 500);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Preferences saved successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Invalid request: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/settings/ajax/preference/{key}', name: 'ajax_save_preference', methods: ['POST'])]
    public function savePreference(Request $request, string $key): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $value = $data['value'] ?? null;

            $success = $this->preferencesService->savePreference($key, $value);

            if (!$success) {
                return new JsonResponse(['error' => 'Failed to save preference'], 500);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Preference saved successfully',
                'key' => $key,
                'value' => $value
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Invalid request: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/settings/ajax/preferences', name: 'ajax_get_preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        try {
            $preferences = $this->preferencesService->getUserPreferences();

            return new JsonResponse([
                'success' => true,
                'preferences' => $preferences
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to load preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/settings/ajax/preferences/reset', name: 'ajax_reset_preferences', methods: ['POST'])]
    public function resetPreferences(): JsonResponse
    {
        try {
            $success = $this->preferencesService->resetUserPreferences();

            if (!$success) {
                return new JsonResponse(['error' => 'Failed to reset preferences'], 500);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Preferences reset to defaults',
                'preferences' => $this->preferencesService->getUserPreferences()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to reset preferences: ' . $e->getMessage()
            ], 500);
        }
    }
}