<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\NavigationConfig;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * MenuExtension - Twig Extension for Rendering Navigation Menus with Permission Checks
 *
 * Provides Twig functions to render navigation menus with automatic permission filtering.
 * Integrates with NavigationConfig service for centralized menu structure and
 * Symfony Security for permission checks using is_granted().
 *
 * Following Symfony 2025 best practices:
 * - Automatic permission filtering using Security Voters
 * - Supports both role-based and attribute-based access control
 * - Single source of truth (NavigationConfig) for menu structure
 * - Clean separation of concerns (config, permissions, rendering)
 * - Templates for presentation instead of hardcoded HTML
 *
 * Usage in Twig:
 *   {{ render_main_menu() }}
 *   {{ render_user_menu() }}
 */
final class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly NavigationConfig $navigationConfig,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly Environment $twig,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_main_menu', [$this, 'renderMainMenu'], ['is_safe' => ['html']]),
            new TwigFunction('render_user_menu', [$this, 'renderUserMenu'], ['is_safe' => ['html']]),
            new TwigFunction('get_visible_menu_items', [$this, 'getVisibleMenuItems']),
        ];
    }

    /**
     * Render main navigation menu using Twig template
     */
    public function renderMainMenu(): string
    {
        $menuItems = $this->navigationConfig->getMainMenu();
        $visibleItems = $this->filterVisibleItems($menuItems);

        if (empty($visibleItems)) {
            return '';
        }

        return $this->twig->render('_partials/menu/_main_menu.html.twig', [
            'menu_items' => $visibleItems,
        ]);
    }

    /**
     * Render user profile menu using Twig template
     */
    public function renderUserMenu(): string
    {
        $menuItems = $this->navigationConfig->getUserMenu();
        $visibleItems = $this->filterVisibleItems($menuItems);

        if (empty($visibleItems)) {
            return '';
        }

        return $this->twig->render('_partials/menu/_user_menu.html.twig', [
            'menu_items' => $visibleItems,
        ]);
    }

    /**
     * Get visible menu items (for testing or custom rendering)
     *
     * @return array<string, mixed>
     */
    public function getVisibleMenuItems(string $menuType = 'main'): array
    {
        $menuItems = $menuType === 'user'
            ? $this->navigationConfig->getUserMenu()
            : $this->navigationConfig->getMainMenu();

        return $this->filterVisibleItems($menuItems);
    }

    /**
     * Filter menu items based on permissions
     *
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    private function filterVisibleItems(array $items): array
    {
        $isGranted = fn(string $attribute) => $this->authorizationChecker->isGranted($attribute);

        return array_filter(
            $items,
            fn(array $item) => $this->navigationConfig->isMenuItemVisible($item, $isGranted)
        );
    }
}
