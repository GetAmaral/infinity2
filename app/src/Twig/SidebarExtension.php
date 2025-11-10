<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\NavigationConfig;
use App\Service\SidebarService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class SidebarExtension extends AbstractExtension
{
    public function __construct(
        private readonly SidebarService $sidebarService,
        private readonly NavigationConfig $navigationConfig,
        private readonly Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_sidebar_favorites', [$this, 'getSidebarFavorites']),
            new TwigFunction('get_main_menu', [$this, 'getMainMenu']),
            new TwigFunction('is_menu_item_visible', [$this, 'isMenuItemVisible']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('group_by_section', [$this, 'groupBySection']),
        ];
    }

    public function getSidebarFavorites(): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        return $this->sidebarService->getFavorites($user);
    }

    public function getMainMenu(): array
    {
        return $this->navigationConfig->getMainMenu();
    }

    public function isMenuItemVisible(array $item): bool
    {
        return $this->navigationConfig->isMenuItemVisible(
            $item,
            fn($attribute) => $this->security->isGranted($attribute)
        );
    }

    public function groupBySection(array $menu): array
    {
        $grouped = [];
        $currentSection = null;

        foreach ($menu as $key => $item) {
            // Detect section divider
            if (isset($item['section_title'])) {
                $sectionKey = str_replace('nav.section.', '', $item['section_title']);
                $currentSection = $sectionKey;
                continue;
            }

            // Skip dividers
            if (isset($item['divider_before']) || isset($item['divider_after'])) {
                continue;
            }

            // Skip manual items (home, student_courses)
            if (in_array($key, ['home', 'student_courses'], true)) {
                continue;
            }

            // Add to current section
            if ($currentSection !== null) {
                $grouped[$currentSection] ??= [];
                $grouped[$currentSection][$key] = $item;
            }
        }

        return $grouped;
    }
}
