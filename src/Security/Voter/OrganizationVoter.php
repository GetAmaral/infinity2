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
     * All authenticated users can list organizations
     */
    private function canList(User $user): bool
    {
        return true; // All logged-in users can list
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
     * All authenticated users can view organizations
     */
    private function canView(?Organization $organization, User $user): bool
    {
        if (!$organization) {
            return false;
        }

        return true; // All logged-in users can view
    }

    /**
     * Can the user edit this organization?
     * Only ADMIN and SUPER_ADMIN can edit
     */
    private function canEdit(?Organization $organization, User $user): bool
    {
        if (!$organization) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user delete this organization?
     * Only SUPER_ADMIN can delete (more restrictive than edit)
     */
    private function canDelete(?Organization $organization, User $user): bool
    {
        if (!$organization) {
            return false;
        }

        // Only SUPER_ADMIN can delete
        // Can add additional checks like: organization must not have users
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }
}
