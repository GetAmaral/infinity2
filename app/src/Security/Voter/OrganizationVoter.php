<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Organization;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Centralized Security Voter for Organization CRUD Permissions
 *
 * Single source of truth for all Organization access control.
 * Defines who can CREATE, VIEW, EDIT, DELETE organizations.
 */
final class OrganizationVoter extends Voter
{
    // CRUD permissions constants - single source of truth
    public const CREATE = 'ORGANIZATION_CREATE';
    public const VIEW = 'ORGANIZATION_VIEW';
    public const EDIT = 'ORGANIZATION_EDIT';
    public const DELETE = 'ORGANIZATION_DELETE';
    public const LIST = 'ORGANIZATION_LIST';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if this is an organization-related permission
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE, self::LIST])) {
            return false;
        }

        // For CREATE and LIST, subject can be null (not tied to specific org)
        if (in_array($attribute, [self::CREATE, self::LIST])) {
            return true;
        }

        // For VIEW, EDIT, DELETE, subject must be an Organization
        return $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in
        if (!$user instanceof User) {
            return false;
        }

        /** @var Organization|null $organization */
        $organization = $subject;

        return match ($attribute) {
            self::LIST => $this->canList($user),
            self::CREATE => $this->canCreate($user),
            self::VIEW => $this->canView($organization, $user),
            self::EDIT => $this->canEdit($organization, $user),
            self::DELETE => $this->canDelete($organization, $user),
            default => false,
        };
    }

    /**
     * Can the user list organizations?
     * Only ADMIN and SUPER_ADMIN can list all organizations
     */
    private function canList(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user create a new organization?
     * Only ADMIN and SUPER_ADMIN can create
     */
    private function canCreate(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user view this organization?
     * - ADMIN and SUPER_ADMIN can view all organizations
     * - Regular users can only view their own organization
     */
    private function canView(?Organization $organization, User $user): bool
    {
        if (!$organization) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can view all organizations
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Regular users can only view their own organization
        return $user->getOrganization() && $user->getOrganization()->getId()->equals($organization->getId());
    }

    /**
     * Can the user edit this organization?
     * - ADMIN and SUPER_ADMIN can edit all organizations
     * - ORGANIZATION_ADMIN can edit only their own organization
     */
    private function canEdit(?Organization $organization, User $user): bool
    {
        if (!$organization) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can edit all organizations
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // ORGANIZATION_ADMIN can edit only their own organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true)) {
            return $user->getOrganization() && $user->getOrganization()->getId()->equals($organization->getId());
        }

        return false;
    }

    /**
     * Can the user delete this organization?
     * ADMIN and SUPER_ADMIN can delete organizations
     */
    private function canDelete(?Organization $organization, User $user): bool
    {
        if (!$organization) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can delete
        // Note: Controller also checks if organization has users before allowing deletion
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }
}