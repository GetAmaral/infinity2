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
    public const MANAGE_ENROLLMENTS = 'COURSE_MANAGE_ENROLLMENTS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Check if this is a course-related permission
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE, self::LIST, self::MANAGE_ENROLLMENTS])) {
            return false;
        }

        // For CREATE and LIST, subject can be null (not tied to specific course)
        if (in_array($attribute, [self::CREATE, self::LIST])) {
            return true;
        }

        // For VIEW, EDIT, DELETE, MANAGE_ENROLLMENTS, subject must be a Course
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
            self::MANAGE_ENROLLMENTS => $this->canManageEnrollments($targetCourse, $currentUser),
            default => false,
        };
    }

    /**
     * Can the user list courses in course management?
     * Only instructors, education admins, organization admins, and admins
     * Students access courses via student portal, not course management
     */
    private function canList(User $user): bool
    {
        $roles = $user->getRoles();

        // Block students from accessing course management
        if (in_array('ROLE_STUDENT', $roles, true)) {
            return false;
        }

        // Allow instructors, education admins, managers, org admins, and system admins
        return in_array('ROLE_INSTRUCTOR', $roles, true)
            || in_array('ROLE_EDUCATION_ADMIN', $roles, true)
            || in_array('ROLE_MANAGER', $roles, true)
            || in_array('ROLE_ORGANIZATION_ADMIN', $roles, true)
            || in_array('ROLE_ADMIN', $roles, true)
            || in_array('ROLE_SUPER_ADMIN', $roles, true);
    }

    /**
     * Can the user create a new course?
     * Instructors, education admins, managers, organization admins, and system admins can create
     */
    private function canCreate(User $user): bool
    {
        return in_array('ROLE_INSTRUCTOR', $user->getRoles(), true)
            || in_array('ROLE_EDUCATION_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_MANAGER', $user->getRoles(), true)
            || in_array('ROLE_ORGANIZATION_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
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

        // ORGANIZATION_ADMIN, EDUCATION_ADMIN, INSTRUCTOR, and MANAGER can view courses in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_EDUCATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_INSTRUCTOR', $currentUser->getRoles(), true)
            || in_array('ROLE_MANAGER', $currentUser->getRoles(), true)) {
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

        // ORGANIZATION_ADMIN, EDUCATION_ADMIN, INSTRUCTOR, and MANAGER can edit courses in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_EDUCATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_INSTRUCTOR', $currentUser->getRoles(), true)
            || in_array('ROLE_MANAGER', $currentUser->getRoles(), true)) {
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

        // ORGANIZATION_ADMIN and EDUCATION_ADMIN can delete courses in their organization
        if (in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_EDUCATION_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Course owner can delete their own courses
        return $currentUser->getId()->equals($targetCourse->getOwner()->getId());
    }

    /**
     * Can the user manage enrollments for this course?
     * - ADMIN and SUPER_ADMIN can manage all enrollments
     * - ORGANIZATION_ADMIN can manage enrollments in their organization
     */
    private function canManageEnrollments(?Course $targetCourse, User $currentUser): bool
    {
        if (!$targetCourse) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can manage all enrollments
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

        // ORGANIZATION_ADMIN, EDUCATION_ADMIN, INSTRUCTOR, and MANAGER can manage enrollments in their organization
        return in_array('ROLE_ORGANIZATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_EDUCATION_ADMIN', $currentUser->getRoles(), true)
            || in_array('ROLE_INSTRUCTOR', $currentUser->getRoles(), true)
            || in_array('ROLE_MANAGER', $currentUser->getRoles(), true);
    }
}
