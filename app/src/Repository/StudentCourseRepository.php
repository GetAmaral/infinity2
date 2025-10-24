<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StudentCourse;
use App\Repository\Generated\StudentCourseRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentCourse>
 */
class StudentCourseRepository extends StudentCourseRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentCourse::class);
    }

    /**
     * Find enrollment for specific student and course
     */
    public function findEnrollment($student, $course): ?StudentCourse
    {
        return $this->findOneBy([
            'student' => $student,
            'course' => $course
        ]);
    }

    /**
     * Get all courses a student is enrolled in
     */
    public function findByStudent($student): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.student = :student')
            ->setParameter('student', $student)
            ->orderBy('sc.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all students enrolled in a course
     */
    public function findByCourse($course): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.course = :course')
            ->setParameter('course', $course)
            ->orderBy('sc.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active enrollments for student
     */
    public function findActiveByStudent($student): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.course', 'c')
            ->where('sc.student = :student')
            ->andWhere('c.active = true')
            ->setParameter('student', $student)
            ->orderBy('sc.enrolledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find completed courses for student
     */
    public function findCompletedByStudent($student): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.student = :student')
            ->andWhere('sc.completedAt IS NOT NULL')
            ->setParameter('student', $student)
            ->orderBy('sc.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save enrollment (persist and flush)
     */
    public function save(StudentCourse $enrollment, bool $flush = false): void
    {
        $this->getEntityManager()->persist($enrollment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
