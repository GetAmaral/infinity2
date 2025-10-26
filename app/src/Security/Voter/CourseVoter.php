<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Course;
use App\Entity\User;
use App\Security\Voter\Generated\CourseVoterGenerated;

/**
 * Course Voter
 *
 * Extends generated base class for custom authorization logic.
 * Override methods from the base class to add custom business rules.
 *
 * Base class provides:
 * - canLIST(): Allows ADMIN, SUPER_ADMIN, ORGANIZATION_ADMIN
 * - canCREATE(): Allows ADMIN, SUPER_ADMIN, ORGANIZATION_ADMIN
 * - canVIEW(): Allows admins + same organization users
 * - canEDIT(): Allows admins + same organization users + owner
 * - canDELETE(): Allows admins + ORGANIZATION_ADMIN in same org
 */
final class CourseVoter extends CourseVoterGenerated
{
    // Additional permission constants (base class has LIST, CREATE, VIEW, EDIT, DELETE)
    public const MANAGE_ENROLLMENTS = 'COURSE_MANAGE_ENROLLMENTS';

    /**
     * Override supports to add MANAGE_ENROLLMENTS support
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Handle MANAGE_ENROLLMENTS
        if ($attribute === self::MANAGE_ENROLLMENTS) {
            return $subject instanceof Course;
        }

        // Delegate to parent for standard permissions
        return parent::supports($attribute, $subject);
    }

    /**
     * Override voteOnAttribute to handle MANAGE_ENROLLMENTS
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token): bool
    {
        // Handle MANAGE_ENROLLMENTS
        if ($attribute === self::MANAGE_ENROLLMENTS) {
            $user = $token->getUser();
            if (!$user instanceof User) {
                return false;
            }
            /** @var Course $course */
            $course = $subject;
            return $this->canManageEnrollments($course, $user);
        }

        // Delegate to parent for standard permissions
        return parent::voteOnAttribute($attribute, $subject, $token);
    }

    /**
     * Override canLIST to allow INSTRUCTOR and EDUCATION_ADMIN
     *
     * Base class allows: ADMIN, SUPER_ADMIN, ORGANIZATION_ADMIN
     * We add: INSTRUCTOR, EDUCATION_ADMIN, MANAGER
     *
     * IMPORTANT: Check privileged roles FIRST to allow admins who also have student role
     */
    protected function canLIST(User $user): bool
    {
        // Check privileged roles FIRST (before any blocking logic)
        // This allows users with both admin AND student roles to access course management
        if ($this->hasRole($user, 'ROLE_INSTRUCTOR')
            || $this->hasRole($user, 'ROLE_EDUCATION_ADMIN')
            || $this->hasRole($user, 'ROLE_MANAGER')
            || $this->hasRole($user, 'ROLE_ORGANIZATION_ADMIN')
            || $this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Block students from accessing course management
        // Students access courses via student portal, not course management
        // This only blocks users who ONLY have student role
        if ($this->hasRole($user, 'ROLE_STUDENT')) {
            return false;
        }

        return false;
    }

    /**
     * Override canCREATE to allow INSTRUCTOR and EDUCATION_ADMIN
     */
    protected function canCREATE(User $user): bool
    {
        // Allow base roles
        if (parent::canCREATE($user)) {
            return true;
        }

        // Also allow instructors, education admins, and managers
        return $this->hasRole($user, 'ROLE_INSTRUCTOR')
            || $this->hasRole($user, 'ROLE_EDUCATION_ADMIN')
            || $this->hasRole($user, 'ROLE_MANAGER');
    }

    /**
     * Override canVIEW to add instructor and education admin access
     */
    protected function canVIEW(?Course $course, User $user): bool
    {
        if (!$course) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can view all courses
        if ($this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $user->getOrganization()
            && $course->getOrganization()
            && $user->getOrganization()->getId()->equals($course->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN, EDUCATION_ADMIN, INSTRUCTOR, and MANAGER can view courses in their organization
        if ($this->hasRole($user, 'ROLE_ORGANIZATION_ADMIN')
            || $this->hasRole($user, 'ROLE_EDUCATION_ADMIN')
            || $this->hasRole($user, 'ROLE_INSTRUCTOR')
            || $this->hasRole($user, 'ROLE_MANAGER')) {
            return true;
        }

        // Course owner can view their own courses
        if ($course->getOwner() && $user->getId()->equals($course->getOwner()->getId())) {
            return true;
        }

        // Regular users can only view active courses
        return $course->isActive();
    }

    /**
     * Override canEDIT to add instructor access
     */
    protected function canEDIT(?Course $course, User $user): bool
    {
        if (!$course) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can edit all courses
        if ($this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $user->getOrganization()
            && $course->getOrganization()
            && $user->getOrganization()->getId()->equals($course->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN, EDUCATION_ADMIN, INSTRUCTOR, and MANAGER can edit courses in their organization
        if ($this->hasRole($user, 'ROLE_ORGANIZATION_ADMIN')
            || $this->hasRole($user, 'ROLE_EDUCATION_ADMIN')
            || $this->hasRole($user, 'ROLE_INSTRUCTOR')
            || $this->hasRole($user, 'ROLE_MANAGER')) {
            return true;
        }

        // Course owner can edit their own courses
        return $course->getOwner() && $user->getId()->equals($course->getOwner()->getId());
    }

    /**
     * Override canDELETE to add education admin access
     */
    protected function canDELETE(?Course $course, User $user): bool
    {
        if (!$course) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can delete all courses
        if ($this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $user->getOrganization()
            && $course->getOrganization()
            && $user->getOrganization()->getId()->equals($course->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN and EDUCATION_ADMIN can delete courses in their organization
        if ($this->hasRole($user, 'ROLE_ORGANIZATION_ADMIN')
            || $this->hasRole($user, 'ROLE_EDUCATION_ADMIN')) {
            return true;
        }

        // Course owner can delete their own courses
        return $course->getOwner() && $user->getId()->equals($course->getOwner()->getId());
    }

    /**
     * Can the user manage enrollments for this course?
     * - ADMIN and SUPER_ADMIN can manage all enrollments
     * - ORGANIZATION_ADMIN can manage enrollments in their organization
     * - INSTRUCTOR, EDUCATION_ADMIN, and MANAGER can manage enrollments in their organization
     */
    private function canManageEnrollments(?Course $course, User $user): bool
    {
        if (!$course) {
            return false;
        }

        // ADMIN and SUPER_ADMIN can manage all enrollments
        if ($this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Course must be in user's organization
        $sameOrganization = $user->getOrganization()
            && $course->getOrganization()
            && $user->getOrganization()->getId()->equals($course->getOrganization()->getId());

        if (!$sameOrganization) {
            return false;
        }

        // ORGANIZATION_ADMIN, EDUCATION_ADMIN, INSTRUCTOR, and MANAGER can manage enrollments in their organization
        return $this->hasRole($user, 'ROLE_ORGANIZATION_ADMIN')
            || $this->hasRole($user, 'ROLE_EDUCATION_ADMIN')
            || $this->hasRole($user, 'ROLE_INSTRUCTOR')
            || $this->hasRole($user, 'ROLE_MANAGER');
    }
}
