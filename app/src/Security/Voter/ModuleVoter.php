<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Module;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * ModuleVoter - Security Voter for Module CRUD Permissions
 *
 * Implements 2025 CRM Best Practices:
 * - Role-Based Access Control (RBAC)
 * - Principle of Least Privilege
 * - Organization-based Multi-tenancy
 * - System module protection
 * - Permission matrix validation
 *
 * Single source of truth for all Module access control.
 * Defines who can CREATE, VIEW, EDIT, DELETE, ACTIVATE, and ACCESS modules.
 */
final class ModuleVoter extends Voter
{
    // CRUD permissions constants
    public const CREATE = 'MODULE_CREATE';
    public const VIEW = 'MODULE_VIEW';
    public const EDIT = 'MODULE_EDIT';
    public const DELETE = 'MODULE_DELETE';
    public const LIST = 'MODULE_LIST';

    // Module-specific permissions
    public const ACTIVATE = 'MODULE_ACTIVATE';
    public const DEACTIVATE = 'MODULE_DEACTIVATE';
    public const ACCESS = 'MODULE_ACCESS';
    public const CONFIGURE = 'MODULE_CONFIGURE';
    public const MANAGE_PERMISSIONS = 'MODULE_MANAGE_PERMISSIONS';
    public const VIEW_STATS = 'MODULE_VIEW_STATS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportedAttributes = [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::LIST,
            self::ACTIVATE,
            self::DEACTIVATE,
            self::ACCESS,
            self::CONFIGURE,
            self::MANAGE_PERMISSIONS,
            self::VIEW_STATS,
        ];

        // Check if this is a module-related permission
        if (!in_array($attribute, $supportedAttributes, true)) {
            return false;
        }

        // For CREATE, LIST, VIEW_STATS - subject can be null (not tied to specific module)
        if (in_array($attribute, [self::CREATE, self::LIST, self::VIEW_STATS], true)) {
            return true;
        }

        // For all other operations, subject must be a Module
        return $subject instanceof Module;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be logged in
        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var Module|null $module */
        $module = $subject;

        return match ($attribute) {
            self::LIST => $this->canList($currentUser),
            self::CREATE => $this->canCreate($currentUser),
            self::VIEW => $this->canView($module, $currentUser),
            self::EDIT => $this->canEdit($module, $currentUser),
            self::DELETE => $this->canDelete($module, $currentUser),
            self::ACTIVATE => $this->canActivate($module, $currentUser),
            self::DEACTIVATE => $this->canDeactivate($module, $currentUser),
            self::ACCESS => $this->canAccess($module, $currentUser),
            self::CONFIGURE => $this->canConfigure($module, $currentUser),
            self::MANAGE_PERMISSIONS => $this->canManagePermissions($module, $currentUser),
            self::VIEW_STATS => $this->canViewStats($currentUser),
            default => false,
        };
    }

    /**
     * Can the user list modules?
     * All authenticated users can list modules (filtered by their permissions)
     */
    private function canList(User $user): bool
    {
        // All authenticated users can list modules they have access to
        return true;
    }

    /**
     * Can the user create a new module?
     * Only ADMIN and SUPER_ADMIN can create modules
     */
    private function canCreate(User $user): bool
    {
        return $this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
    }

    /**
     * Can the user view this module?
     * - ADMIN and SUPER_ADMIN can view all modules
     * - ORGANIZATION_ADMIN can view modules in their organization
     * - Regular users can view active/enabled modules they have permission to access
     */
    private function canView(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can view all modules
        if ($this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Check organization access
        if (!$this->hasOrganizationAccess($module, $user)) {
            return false;
        }

        // ORGANIZATION_ADMIN can view modules in their organization
        if ($this->hasRole($user, ['ROLE_ORGANIZATION_ADMIN'])) {
            return true;
        }

        // Regular users can only view active and enabled modules
        if (!$module->isActive() || !$module->isEnabled()) {
            return false;
        }

        // Check if user has permission to access this module
        return $this->userHasModuleAccess($module, $user);
    }

    /**
     * Can the user edit this module?
     * - ADMIN and SUPER_ADMIN can edit all modules
     * - ORGANIZATION_ADMIN can edit non-system modules in their organization
     */
    private function canEdit(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can edit all modules
        if ($this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Check organization access
        if (!$this->hasOrganizationAccess($module, $user)) {
            return false;
        }

        // ORGANIZATION_ADMIN can edit non-system modules in their organization
        if ($this->hasRole($user, ['ROLE_ORGANIZATION_ADMIN'])) {
            return !$module->isSystem();
        }

        return false;
    }

    /**
     * Can the user delete this module?
     * Only ADMIN and SUPER_ADMIN can delete modules
     * System modules cannot be deleted
     */
    private function canDelete(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // System modules cannot be deleted
        if ($module->isSystem()) {
            return false;
        }

        // Only ADMIN and SUPER_ADMIN can delete
        if (!$this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return false;
        }

        // Check organization access
        return $this->hasOrganizationAccess($module, $user);
    }

    /**
     * Can the user activate this module?
     * - ADMIN and SUPER_ADMIN can activate all modules
     * - ORGANIZATION_ADMIN can activate modules in their organization
     */
    private function canActivate(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can activate all modules
        if ($this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Check organization access
        if (!$this->hasOrganizationAccess($module, $user)) {
            return false;
        }

        // ORGANIZATION_ADMIN can activate modules in their organization
        return $this->hasRole($user, ['ROLE_ORGANIZATION_ADMIN']);
    }

    /**
     * Can the user deactivate this module?
     * - ADMIN and SUPER_ADMIN can deactivate all modules
     * - ORGANIZATION_ADMIN can deactivate non-system modules in their organization
     */
    private function canDeactivate(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // System modules cannot be deactivated
        if ($module->isSystem()) {
            // Only SUPER_ADMIN can deactivate system modules
            return $this->hasRole($user, ['ROLE_SUPER_ADMIN']);
        }

        // ADMIN and SUPER_ADMIN can deactivate all modules
        if ($this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Check organization access
        if (!$this->hasOrganizationAccess($module, $user)) {
            return false;
        }

        // ORGANIZATION_ADMIN can deactivate non-system modules in their organization
        return $this->hasRole($user, ['ROLE_ORGANIZATION_ADMIN']);
    }

    /**
     * Can the user access (use) this module?
     * - Module must be active and enabled
     * - User must have required roles/permissions
     * - Public modules are accessible to all
     * - License requirements must be met
     */
    private function canAccess(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can access all modules
        if ($this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Module must be active and enabled
        if (!$module->isActive() || !$module->isEnabled()) {
            return false;
        }

        // Check organization access
        if (!$this->hasOrganizationAccess($module, $user)) {
            return false;
        }

        // Public modules are accessible to all authenticated users
        if ($module->isPublicAccess()) {
            return true;
        }

        // Check if user has required roles
        if (!$this->userHasRequiredRoles($module, $user)) {
            return false;
        }

        // Check module permissions
        if (!$this->userHasModuleAccess($module, $user)) {
            return false;
        }

        // Check license requirements
        return !$module->isLicenseBlocked();
    }

    /**
     * Can the user configure this module?
     * Only ADMIN and SUPER_ADMIN can configure modules
     */
    private function canConfigure(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can configure all modules
        if ($this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Check organization access
        if (!$this->hasOrganizationAccess($module, $user)) {
            return false;
        }

        // ORGANIZATION_ADMIN can configure non-system modules in their organization
        if ($this->hasRole($user, ['ROLE_ORGANIZATION_ADMIN'])) {
            return !$module->isSystem();
        }

        return false;
    }

    /**
     * Can the user manage permissions for this module?
     * Only ADMIN and SUPER_ADMIN can manage module permissions
     */
    private function canManagePermissions(?Module $module, User $user): bool
    {
        if (!$module) {
            return false;
        }

        // Only ADMIN and SUPER_ADMIN can manage permissions
        return $this->hasRole($user, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
    }

    /**
     * Can the user view module statistics?
     * ADMIN, SUPER_ADMIN, and ORGANIZATION_ADMIN can view stats
     */
    private function canViewStats(User $user): bool
    {
        return $this->hasRole($user, [
            'ROLE_ADMIN',
            'ROLE_SUPER_ADMIN',
            'ROLE_ORGANIZATION_ADMIN'
        ]);
    }

    /**
     * Check if user has any of the specified roles
     */
    private function hasRole(User $user, array $roles): bool
    {
        $userRoles = $user->getRoles();
        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has organization access to the module
     */
    private function hasOrganizationAccess(Module $module, User $user): bool
    {
        $moduleOrg = $module->getOrganization();
        $userOrg = $user->getOrganization();

        // Global modules (no organization) are accessible to all
        if ($moduleOrg === null) {
            return true;
        }

        // User must have an organization
        if ($userOrg === null) {
            return false;
        }

        // Organizations must match
        return $moduleOrg->getId()->equals($userOrg->getId());
    }

    /**
     * Check if user has required roles for the module
     */
    private function userHasRequiredRoles(Module $module, User $user): bool
    {
        $requiredRoles = $module->getRequiredRoles();

        // No required roles means accessible to all
        if ($requiredRoles === null || empty($requiredRoles)) {
            return true;
        }

        // User must have at least one of the required roles
        return $this->hasRole($user, $requiredRoles);
    }

    /**
     * Check if user has access to the module based on permissions
     */
    private function userHasModuleAccess(Module $module, User $user): bool
    {
        // If no permissions defined, allow access
        $modulePermissions = $module->getPermissions();
        if ($modulePermissions === null || empty($modulePermissions)) {
            return true;
        }

        // Check if user has any of the required permissions through their roles
        foreach ($user->getRoleEntities() as $role) {
            foreach ($modulePermissions as $requiredPermission) {
                if ($role->hasPermission($requiredPermission)) {
                    return true;
                }
            }
        }

        return false;
    }
}
