<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Centralized Security Voter for User CRUD Permissions
 *
 * Single source of truth for all User access control.
 * Defines who can CREATE, VIEW, EDIT, DELETE users.
 */
final class UserVoter extends Voter
{
    // CRUD permissions constants - single source of truth
    public const CREATE = 'USER_CREATE';
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
    public const LIST = 'USER_LIST';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if this is a user-related permission
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE, self::LIST])) {
            return false;
        }

        // For CREATE and LIST, subject can be null (not tied to specific user)
        if (in_array($attribute, [self::CREATE, self::LIST])) {
            return true;
        }

        // For VIEW, EDIT, DELETE, subject must be a User
        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be logged in
        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var User|null $targetUser */
        $targetUser = $subject;

        return match ($attribute) {
            self::LIST => $this->canList($currentUser),
            self::CREATE => $this->canCreate($currentUser),
            self::VIEW => $this->canView($targetUser, $currentUser),
            self::EDIT => $this->canEdit($targetUser, $currentUser),
            self::DELETE => $this->canDelete($targetUser, $currentUser),
            default => false,
        };
    }

    /**
     * Can the user list users?
     * Only ADMIN and SUPER_ADMIN can list all users
     */
    private function canList(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user create a new user?
     * Only ADMIN and SUPER_ADMIN can create
     */
    private function canCreate(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user view this user?
     * - ADMIN and SUPER_ADMIN can view all users
     * - Regular users can only view themselves
     * - ORGANIZATION_ADMIN can view users in their organization
     */
    private function canView(?User $targetUser, User $currentUser): bool
    {
        if (!$targetUser) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can view all users
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Users can view themselves
        if ($currentUser->getId()->equals($targetUser->getId())) {
            return true;
        }

        // ORGANIZATION_ADMIN can view users in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return $currentUser->getOrganization()
                && $targetUser->getOrganization()
                && $currentUser->getOrganization()->getId()->equals($targetUser->getOrganization()->getId());
        }

        return false;
    }

    /**
     * Can the user edit this user?
     * - ADMIN and SUPER_ADMIN can edit all users
     * - Users can edit themselves
     * - ORGANIZATION_ADMIN can edit users in their organization
     */
    private function canEdit(?User $targetUser, User $currentUser): bool
    {
        if (!$targetUser) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can edit all users
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Users can edit themselves
        if ($currentUser->getId()->equals($targetUser->getId())) {
            return true;
        }

        // ORGANIZATION_ADMIN can edit users in their organization (but not other admins)
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            $sameOrganization = $currentUser->getOrganization()
                && $targetUser->getOrganization()
                && $currentUser->getOrganization()->getId()->equals($targetUser->getOrganization()->getId());

            $targetIsNotAdmin = !in_array('ROLE_ADMIN', $targetUser->getRoles(), true)
                && !in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles(), true)
                && !in_array('ROLE_ORGANIZATION_ADMIN', $targetUser->getRoles(), true);

            return $sameOrganization && $targetIsNotAdmin;
        }

        return false;
    }

    /**
     * Can the user delete this user?
     * Only ADMIN and SUPER_ADMIN can delete users
     * Users cannot delete themselves
     */
    private function canDelete(?User $targetUser, User $currentUser): bool
    {
        if (!$targetUser) {
            return false;
        }

        // Users cannot delete themselves
        if ($currentUser->getId()->equals($targetUser->getId())) {
            return false;
        }

        // Only ADMIN and SUPER_ADMIN can delete
        return in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true);
    }
}