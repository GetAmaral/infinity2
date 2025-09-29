<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    private const SUPPORTED_LOCALES = [
        'en' => 'language.en',
        'pt_BR' => 'language.pt_BR',
    ];

    #[Route('/settings', name: 'app_settings')]
    public function index(): Response
    {
        $currentLocale = $this->getUser() ?
            $this->getUser()->getLocale() ?? $this->getRequest()->getLocale() :
            $this->getRequest()->getLocale();

        return $this->render('settings/index.html.twig', [
            'current_locale' => $currentLocale,
            'supported_locales' => self::SUPPORTED_LOCALES,
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

        // Add success message
        $this->addFlash('success', 'settings.language_saved');

        return $this->redirectToRoute('app_settings');
    }

    private function getRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
}