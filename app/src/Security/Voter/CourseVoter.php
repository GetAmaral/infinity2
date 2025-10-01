<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Course;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Centralized Security Voter for Course CRUD Permissions
 *
 * Single source of truth for all Course access control.
 * Defines who can CREATE, VIEW, EDIT, DELETE courses.
 */
final class CourseVoter extends Voter
{
    // CRUD permissions constants - single source of truth
    public const CREATE = 'COURSE_CREATE';
    public const VIEW = 'COURSE_VIEW';
    public const EDIT = 'COURSE_EDIT';
    public const DELETE = 'COURSE_DELETE';
    public const LIST = 'COURSE_LIST';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if this is a course-related permission
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE, self::LIST])) {
            return false;
        }

        // For CREATE and LIST, subject can be null (not tied to specific course)
        if (in_array($attribute, [self::CREATE, self::LIST])) {
            return true;
        }

        // For VIEW, EDIT, DELETE, subject must be a Course
        return $subject instanceof Course;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be logged in
        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var Course|null $targetCourse */
        $targetCourse = $subject;

        return match ($attribute) {
            self::LIST => $this->canList($currentUser),
            self::CREATE => $this->canCreate($currentUser),
            self::VIEW => $this->canView($targetCourse, $currentUser),
            self::EDIT => $this->canEdit($targetCourse, $currentUser),
            self::DELETE => $this->canDelete($targetCourse, $currentUser),
            default => false,
        };
    }

    /**
     * Can the user list courses?
     * All authenticated users can list courses (filtered by organization)
     */
    private function canList(User $user): bool
    {
        return true; // All authenticated users can list courses
    }

    /**
     * Can the user create a new course?
     * ADMIN, SUPER_ADMIN, and ORGANIZATION_ADMIN can create
     */
    private function canCreate(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true);
    }

    /**
     * Can the user view this course?
     * - ADMIN and SUPER_ADMIN can view all courses
     * - ORGANIZATION_ADMIN can view courses in their organization
     * - Course owner can view their own courses
     * - Regular users can view active courses in their organization
     */
    private function canView(?Course $targetCourse, User $currentUser): bool
    {
        if (!$targetCourse) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can view all courses
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $currentUser->getOrganization()
            && $targetCourse->getOrganization()
            && $currentUser->getOrganization()->getId()->equals($targetCourse->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN can view all courses in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course owner can view their own courses
        if ($currentUser->getId()->equals($targetCourse->getOwner()->getId())) {
            return true;
        }

        // Regular users can only view active courses
        return $targetCourse->isActive();
    }

    /**
     * Can the user edit this course?
     * - ADMIN and SUPER_ADMIN can edit all courses
     * - ORGANIZATION_ADMIN can edit courses in their organization
     * - Course owner can edit their own courses
     */
    private function canEdit(?Course $targetCourse, User $currentUser): bool
    {
        if (!$targetCourse) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can edit all courses
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $currentUser->getOrganization()
            && $targetCourse->getOrganization()
            && $currentUser->getOrganization()->getId()->equals($targetCourse->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN can edit all courses in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course owner can edit their own courses
        return $currentUser->getId()->equals($targetCourse->getOwner()->getId());
    }

    /**
     * Can the user delete this course?
     * - ADMIN and SUPER_ADMIN can delete all courses
     * - ORGANIZATION_ADMIN can delete courses in their organization
     * - Course owner can delete their own courses
     */
    private function canDelete(?Course $targetCourse, User $currentUser): bool
    {
        if (!$targetCourse) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can delete all courses
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $currentUser->getOrganization()
            && $targetCourse->getOrganization()
            && $currentUser->getOrganization()->getId()->equals($targetCourse->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN can delete all courses in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course owner can delete their own courses
        return $currentUser->getId()->equals($targetCourse->getOwner()->getId());
    }
}
