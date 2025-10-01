<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StudentLecture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentLecture>
 */
class StudentLectureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentLecture::class);
    }

    /**
     * Find student progress for a specific lecture
     */
    public function findProgressByStudentAndLecture($student, $lecture): ?StudentLecture
    {
        return $this->findOneBy([
            'student' => $student,
            'lecture' => $lecture
        ]);
    }

    /**
     * Get all lecture progress for a student in a course
     */
    public function findProgressByStudentAndCourse($student, $course): array
    {
        return $this->createQueryBuilder('sl')
            ->join('sl.lecture', 'l')
            ->join('l.course', 'c')
            ->where('sl.student = :student')
            ->andWhere('c = :course')
            ->setParameter('student', $student)
            ->setParameter('course', $course)
            ->orderBy('l.viewOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get completed lecture count for student in course
     */
    public function countCompletedByStudentAndCourse($student, $course): int
    {
        return (int) $this->createQueryBuilder('sl')
            ->select('COUNT(sl.id)')
            ->join('sl.lecture', 'l')
            ->join('l.course', 'c')
            ->where('sl.student = :student')
            ->andWhere('c = :course')
            ->andWhere('sl.completed = true')
            ->setParameter('student', $student)
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
