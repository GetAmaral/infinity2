<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\Voter\CourseVoter;
use App\Security\Voter\OrganizationVoter;
use App\Security\Voter\TreeFlowVoter;
use App\Security\Voter\UserVoter;

/**
 * NavigationConfig - Single Source of Truth for Navigation Menu Structure
 *
 * Centralizes menu configuration including:
 * - Menu structure and hierarchy
 * - Required permissions for each menu item
 * - Route names, icons, and labels
 * - Visibility rules based on roles and permissions
 *
 * Following Symfony 2025 best practices:
 * - Uses Security Voters as permission source
 * - Supports role-based and attribute-based access control
 * - Compatible with is_granted() Twig function
 * - Provides single configuration point for all navigation
 */
final class NavigationConfig
{
    /**
     * Get main navigation menu structure
     *
     * @return array<string, array{
     *   label: string,
     *   route: string,
     *   icon: string,
     *   permission?: string,
     *   role?: string,
     *   divider_before?: bool,
     *   divider_after?: bool,
     *   section_title?: string
     * }>
     */
    public function getMainMenu(): array
    {
        return [
            'home' => [
                'label' => 'nav.home',
                'route' => 'app_home',
                'icon' => 'bi-house',
                // No permission required - all authenticated users can access home
            ],
            'student_courses' => [
                'label' => 'nav.my.courses',
                'route' => 'student_courses',
                'icon' => 'bi-mortarboard',
                // All authenticated users can view their courses
            ],
            'organizations' => [
                'label' => 'nav.organizations',
                'route' => 'organization_index',
                'icon' => 'bi-building',
                'permission' => OrganizationVoter::LIST,
            ],
            'users' => [
                'label' => 'nav.users',
                'route' => 'user_index',
                'icon' => 'bi-people',
                'permission' => UserVoter::LIST,
            ],
            'courses' => [
                'label' => 'nav.courses',
                'route' => 'course_index',
                'icon' => 'bi-book',
                'permission' => CourseVoter::LIST,
            ],
            'treeflows' => [
                'label' => 'treeflow.plural',
                'route' => 'treeflow_index',
                'icon' => 'bi-diagram-3',
                'permission' => TreeFlowVoter::LIST,
                'translation_domain' => 'treeflow',
            ],
            'admin_section_divider' => [
                'divider_before' => true,
                'section_title' => 'nav.admin.section',
                'role' => 'ROLE_ADMIN',
            ],
            'admin_audit' => [
                'label' => 'nav.admin.audit.log',
                'route' => 'admin_audit_index',
                'icon' => 'bi-clipboard-data',
                'role' => 'ROLE_ADMIN',
            ],
            'admin_analytics' => [
                'label' => 'nav.admin.audit.analytics',
                'route' => 'admin_audit_analytics',
                'icon' => 'bi-graph-up',
                'role' => 'ROLE_ADMIN',
            ],
        ];
    }

    /**
     * Get user profile dropdown menu structure
     *
     * @return array<string, array{
     *   label: string,
     *   route: string,
     *   icon: string,
     *   permission?: string,
     *   role?: string,
     *   divider_before?: bool,
     *   css_class?: string
     * }>
     */
    public function getUserMenu(): array
    {
        return [
            'settings' => [
                'label' => 'nav.settings',
                'route' => 'app_settings',
                'icon' => 'bi-gear',
            ],
            'terms' => [
                'label' => 'nav.terms',
                'route' => 'app_terms',
                'icon' => 'bi-file-text',
            ],
            'logout_divider' => [
                'divider_before' => true,
            ],
            'logout' => [
                'label' => 'auth.logout',
                'route' => 'app_logout',
                'icon' => 'bi-box-arrow-right',
                'css_class' => 'text-danger',
            ],
        ];
    }

    /**
     * Check if a menu item should be visible based on permissions
     *
     * @param array{
     *   permission?: string,
     *   role?: string,
     *   divider_before?: bool,
     *   divider_after?: bool,
     *   section_title?: string
     * } $item
     */
    public function isMenuItemVisible(array $item, callable $isGranted): bool
    {
        // Dividers and section titles are visible if their role requirement is met
        if (isset($item['divider_before']) || isset($item['divider_after']) || isset($item['section_title'])) {
            if (isset($item['role'])) {
                return $isGranted($item['role']);
            }
            return true; // Dividers without role are always visible
        }

        // Check role-based access (simple RBAC)
        if (isset($item['role'])) {
            if (!$isGranted($item['role'])) {
                return false;
            }
        }

        // Check voter permission (attribute-based access control)
        if (isset($item['permission'])) {
            if (!$isGranted($item['permission'])) {
                return false;
            }
        }

        // If no permission or role specified, item is visible to all authenticated users
        return true;
    }

    /**
     * Get translation domain for menu item
     *
     * @param array{translation_domain?: string} $item
     */
    public function getTranslationDomain(array $item): string
    {
        return $item['translation_domain'] ?? 'messages';
    }
}
