<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\TreeFlow;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Centralized Security Voter for TreeFlow CRUD Permissions
 *
 * Single source of truth for all TreeFlow access control.
 * Defines who can CREATE, VIEW, EDIT, DELETE tree flows.
 */
final class TreeFlowVoter extends Voter
{
    // CRUD permissions constants - single source of truth
    public const LIST = 'TREEFLOW_LIST';
    public const CREATE = 'TREEFLOW_CREATE';
    public const VIEW = 'TREEFLOW_VIEW';
    public const EDIT = 'TREEFLOW_EDIT';
    public const DELETE = 'TREEFLOW_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if this is a treeflow-related permission
        if (!in_array($attribute, [self::LIST, self::CREATE, self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        // For CREATE and LIST, subject can be null (not tied to specific treeflow)
        if (in_array($attribute, [self::CREATE, self::LIST])) {
            return true;
        }

        // For VIEW, EDIT, DELETE, subject must be a TreeFlow
        return $subject instanceof TreeFlow;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be logged in
        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var TreeFlow|null $treeFlow */
        $treeFlow = $subject;

        return match ($attribute) {
            self::LIST => $this->canList($currentUser),
            self::CREATE => $this->canCreate($currentUser),
            self::VIEW => $this->canView($treeFlow, $currentUser),
            self::EDIT => $this->canEdit($treeFlow, $currentUser),
            self::DELETE => $this->canDelete($treeFlow, $currentUser),
            default => false,
        };
    }

    /**
     * Can the user list treeflows?
     * ADMIN, SUPER_ADMIN, and ORGANIZATION_ADMIN can list
     */
    private function canList(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user create a new treeflow?
     * ADMIN, SUPER_ADMIN, and ORGANIZATION_ADMIN can create
     */
    private function canCreate(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user view this treeflow?
     * - ADMIN and SUPER_ADMIN can view all treeflows
     * - ORGANIZATION_ADMIN can view treeflows in their organization
     */
    private function canView(?TreeFlow $treeFlow, User $currentUser): bool
    {
        if (!$treeFlow) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can view all treeflows
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // ORGANIZATION_ADMIN can only view treeflows in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return $currentUser->getOrganization()
                && $treeFlow->getOrganization()
                && $currentUser->getOrganization()->getId()->equals($treeFlow->getOrganization()->getId());
        }

        return false;
    }

    /**
     * Can the user edit this treeflow?
     * - ADMIN and SUPER_ADMIN can edit all treeflows
     * - ORGANIZATION_ADMIN can edit treeflows in their organization
     */
    private function canEdit(?TreeFlow $treeFlow, User $currentUser): bool
    {
        if (!$treeFlow) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can edit all treeflows
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // ORGANIZATION_ADMIN can only edit treeflows in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return $currentUser->getOrganization()
                && $treeFlow->getOrganization()
                && $currentUser->getOrganization()->getId()->equals($treeFlow->getOrganization()->getId());
        }

        return false;
    }

    /**
     * Can the user delete this treeflow?
     * - ADMIN and SUPER_ADMIN can delete all treeflows
     * - ORGANIZATION_ADMIN can delete treeflows in their organization
     */
    private function canDelete(?TreeFlow $treeFlow, User $currentUser): bool
    {
        if (!$treeFlow) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can delete all treeflows
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // ORGANIZATION_ADMIN can only delete treeflows in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return $currentUser->getOrganization()
                && $treeFlow->getOrganization()
                && $currentUser->getOrganization()->getId()->equals($treeFlow->getOrganization()->getId());
        }

        return false;
    }
}
