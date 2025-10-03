<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_LOCALES = ['en', 'pt_BR'];
    private const DEFAULT_LOCALE = 'en';

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        // Skip for API requests
        if (str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $locale = $this->determineLocale($request);

        if ($locale) {
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);
        }
    }

    private function determineLocale($request): ?string
    {
        // 1. Check session for stored locale preference (highest priority)
        $sessionLocale = $request->getSession()->get('_locale');
        if ($sessionLocale && in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
            return $sessionLocale;
        }

        // 2. Detect from browser Accept-Language header
        $preferredLanguages = $request->getLanguages();

        foreach ($preferredLanguages as $language) {
            // Handle exact matches
            if (in_array($language, self::SUPPORTED_LOCALES, true)) {
                return $language;
            }

            // Handle language variants (e.g., pt-BR, pt)
            $normalizedLanguage = $this->normalizeLanguage($language);
            if ($normalizedLanguage && in_array($normalizedLanguage, self::SUPPORTED_LOCALES, true)) {
                return $normalizedLanguage;
            }
        }

        // 3. Fall back to default locale
        return self::DEFAULT_LOCALE;
    }

    private function normalizeLanguage(string $language): ?string
    {
        // Convert common language codes to our supported locales
        $languageMap = [
            'pt' => 'pt_BR',
            'ptbr' => 'pt_BR',
            'ptBR' => 'pt_BR',
            'pt-br' => 'pt_BR',
            'pt_br' => 'pt_BR',
            'pt-BR' => 'pt_BR',
            'en' => 'en',
            'en-us' => 'en',
            'en-US' => 'en',
            'en_US' => 'en',
        ];

        return $languageMap[strtolower($language)] ?? null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
