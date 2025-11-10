<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SidebarService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NavigationConfig $navigationConfig,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    private function getSidebarSettings(User $user): array
    {
        $settings = $user->getUiSetting('sidebar', []);

        // Ensure defaults
        return array_merge([
            'collapsed' => false,
            'expandedSections' => ['crm'],
            'favorites' => []
        ], $settings);
    }

    private function saveSidebarSettings(User $user, array $settings): void
    {
        $user->setUiSetting('sidebar', $settings);
        $this->em->flush();
    }

    public function getPreferences(User $user): array
    {
        return $this->getSidebarSettings($user);
    }

    public function updateState(User $user, bool $collapsed, array $expandedSections): void
    {
        $settings = $this->getSidebarSettings($user);
        $settings['collapsed'] = $collapsed;
        $settings['expandedSections'] = $expandedSections;
        $this->saveSidebarSettings($user, $settings);
    }

    public function getFavorites(User $user): array
    {
        $settings = $this->getSidebarSettings($user);
        $favoriteKeys = $settings['favorites'] ?? [];

        if (empty($favoriteKeys)) {
            return [];
        }

        // Get full menu structure
        $menu = $this->navigationConfig->getMainMenu();

        // Build favorites array with full item data
        $favorites = [];
        foreach ($favoriteKeys as $key) {
            if (isset($menu[$key])) {
                $favorites[] = array_merge($menu[$key], ['key' => $key]);
            }
        }

        return $favorites;
    }

    public function addFavorite(User $user, string $menuKey): void
    {
        $settings = $this->getSidebarSettings($user);
        $favorites = $settings['favorites'] ?? [];

        if (!in_array($menuKey, $favorites, true)) {
            $favorites[] = $menuKey;
            $settings['favorites'] = $favorites;
            $this->saveSidebarSettings($user, $settings);
        }
    }

    public function removeFavorite(User $user, string $menuKey): void
    {
        $settings = $this->getSidebarSettings($user);
        $favorites = $settings['favorites'] ?? [];

        $favorites = array_filter($favorites, fn($key) => $key !== $menuKey);
        $settings['favorites'] = array_values($favorites);
        $this->saveSidebarSettings($user, $settings);
    }

    public function reorderFavorites(User $user, array $orderedKeys): void
    {
        $settings = $this->getSidebarSettings($user);
        $settings['favorites'] = $orderedKeys;
        $this->saveSidebarSettings($user, $settings);
    }

    public function searchMenuItems(string $query): array
    {
        $menu = $this->navigationConfig->getMainMenu();
        $query = strtolower($query);
        $results = [];

        foreach ($menu as $key => $item) {
            // Skip dividers and sections
            if (isset($item['divider_before']) || isset($item['section_title'])) {
                continue;
            }

            // Skip items without route
            if (!isset($item['route'])) {
                continue;
            }

            $label = strtolower($item['label']);
            $key_lower = strtolower($key);

            // Check multiple matching strategies
            $isMatch = false;

            // 1. Exact substring match in label or key (highest priority)
            if (str_contains($label, $query) || str_contains($key_lower, $query)) {
                $isMatch = true;
            }
            // 2. Word boundary match (e.g., "cal" matches "Calendar" at word start)
            elseif (preg_match('/\b' . preg_quote($query, '/') . '/i', $label) ||
                    preg_match('/\b' . preg_quote($query, '/') . '/i', $key)) {
                $isMatch = true;
            }
            // 3. Fuzzy match only if query is 4+ chars and similarity is high
            elseif (strlen($query) >= 4 && $this->fuzzyMatch($query, $label)) {
                // Additional check: ensure at least 60% of query chars are consecutive
                $isMatch = $this->hasConsecutiveChars($query, $label, 0.6);
            }

            if ($isMatch) {
                $results[] = [
                    'key' => $key,
                    'label' => $item['label'],
                    'icon' => $item['icon'] ?? 'bi-circle',
                    'route' => $item['route'],
                    'url' => $this->urlGenerator->generate($item['route']),
                    'section' => $this->getSectionForItem($key, $menu),
                ];
            }
        }

        return $results;
    }

    private function fuzzyMatch(string $query, string $text): bool
    {
        $queryLen = strlen($query);
        $textLen = strlen($text);
        $queryIndex = 0;

        for ($textIndex = 0; $textIndex < $textLen && $queryIndex < $queryLen; $textIndex++) {
            if ($query[$queryIndex] === $text[$textIndex]) {
                $queryIndex++;
            }
        }

        return $queryIndex === $queryLen;
    }

    private function hasConsecutiveChars(string $query, string $text, float $threshold): bool
    {
        $queryLen = strlen($query);
        $maxConsecutive = 0;
        $currentConsecutive = 0;
        $queryIndex = 0;
        $lastMatchIndex = -2;

        for ($textIndex = 0; $textIndex < strlen($text) && $queryIndex < $queryLen; $textIndex++) {
            if ($query[$queryIndex] === $text[$textIndex]) {
                if ($textIndex === $lastMatchIndex + 1) {
                    $currentConsecutive++;
                } else {
                    $maxConsecutive = max($maxConsecutive, $currentConsecutive);
                    $currentConsecutive = 1;
                }
                $lastMatchIndex = $textIndex;
                $queryIndex++;
            }
        }

        $maxConsecutive = max($maxConsecutive, $currentConsecutive);
        return ($maxConsecutive / $queryLen) >= $threshold;
    }

    private function getSectionForItem(string $itemKey, array $menu): ?string
    {
        $currentSection = null;

        foreach ($menu as $key => $item) {
            if (isset($item['section_title'])) {
                // Extract section name from translation key
                // e.g., 'nav.section.crm' -> 'CRM'
                $sectionKey = str_replace('nav.section.', '', $item['section_title']);
                $currentSection = ucfirst($sectionKey);
            }

            if ($key === $itemKey) {
                return $currentSection;
            }
        }

        return null;
    }
}
