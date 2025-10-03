<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseLecture;
use App\Entity\StudentCourse;
use App\Repository\CourseRepository;
use App\Repository\CourseLectureRepository;
use App\Repository\CourseModuleRepository;
use App\Repository\StudentCourseRepository;
use App\Repository\StudentLectureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Student Learning Portal
 *
 * Students can:
 * - View their enrolled courses
 * - Access course lectures
 * - Watch videos and track progress
 */
#[Route('/student')]
#[IsGranted('ROLE_USER')]
final class StudentController extends AbstractController
{
    public function __construct(
        private readonly StudentCourseRepository $studentCourseRepository,
        private readonly StudentLectureRepository $studentLectureRepository,
        private readonly CourseRepository $courseRepository,
        private readonly CourseModuleRepository $moduleRepository,
        private readonly CourseLectureRepository $lectureRepository
    ) {}

    /**
     * List all courses the student is enrolled in
     */
    #[Route('/courses', name: 'student_courses', methods: ['GET'])]
    public function courses(): Response
    {
        $student = $this->getUser();

        if (!$student) {
            throw $this->createAccessDeniedException('You must be logged in to view courses.');
        }

        // Get all active enrollments for this student
        $enrollments = $this->studentCourseRepository->findBy(
            ['student' => $student, 'active' => true],
            ['enrolledAt' => 'DESC']
        );

        return $this->render('student/courses.html.twig', [
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * View course details and lectures list
     */
    #[Route('/course/{id}', name: 'student_course', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function course(string $id): Response
    {
        $student = $this->getUser();

        if (!$student) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $course = $this->courseRepository->find($id);

        if (!$course) {
            throw $this->createNotFoundException('Course not found.');
        }

        // Verify student is enrolled
        $enrollment = $this->studentCourseRepository->findOneBy([
            'student' => $student,
            'course' => $course,
            'active' => true
        ]);

        if (!$enrollment) {
            throw $this->createAccessDeniedException('You are not enrolled in this course.');
        }

        // Get all modules ordered by viewOrder
        $modules = $this->moduleRepository->createQueryBuilder('cm')
            ->where('cm.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cm.viewOrder', 'ASC')
            ->addOrderBy('cm.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Get all lectures for progress counting
        $lectures = $this->lectureRepository->findByCourseOrdered($course->getId()->toString());

        // Get student's progress for each lecture
        $lectureProgress = [];
        foreach ($lectures as $lecture) {
            $progress = $this->studentLectureRepository->findOneBy([
                'student' => $student,
                'lecture' => $lecture
            ]);

            if ($progress) {
                $lectureProgress[$lecture->getId()->toString()] = $progress;
            }
        }

        return $this->render('student/course.html.twig', [
            'course' => $course,
            'enrollment' => $enrollment,
            'modules' => $modules,
            'lectures' => $lectures,
            'lectureProgress' => $lectureProgress,
        ]);
    }

    /**
     * Watch lecture video with progress tracking
     */
    #[Route('/course/{courseId}/lecture/{lectureId}', name: 'student_lecture', requirements: ['courseId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', 'lectureId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function lecture(string $courseId, string $lectureId): Response
    {
        $student = $this->getUser();

        if (!$student) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $course = $this->courseRepository->find($courseId);

        if (!$course) {
            throw $this->createNotFoundException('Course not found.');
        }

        // Verify student is enrolled
        $enrollment = $this->studentCourseRepository->findOneBy([
            'student' => $student,
            'course' => $course,
            'active' => true
        ]);

        if (!$enrollment) {
            throw $this->createAccessDeniedException('You are not enrolled in this course.');
        }

        $lecture = $this->lectureRepository->find($lectureId);

        if (!$lecture) {
            throw $this->createNotFoundException('Lecture not found.');
        }

        // Verify lecture belongs to this course
        if ($lecture->getCourseModule()->getCourse()->getId()->toString() !== $courseId) {
            throw $this->createNotFoundException('Lecture does not belong to this course.');
        }

        // Get or create student progress for this lecture
        $studentProgress = $this->studentLectureRepository->findOneBy([
            'student' => $student,
            'lecture' => $lecture
        ]);

        // Get all modules for navigation sidebar
        $modules = $this->moduleRepository->createQueryBuilder('cm')
            ->where('cm.course = :course')
            ->setParameter('course', $course)
            ->orderBy('cm.viewOrder', 'ASC')
            ->addOrderBy('cm.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Get all lectures for navigation sidebar (ordered by module and viewOrder)
        $allLectures = $this->lectureRepository->findByCourseOrdered($course->getId()->toString());

        // Get progress for all lectures (for sidebar completion indicators)
        $allLectureProgress = [];
        foreach ($allLectures as $courseLecture) {
            $progress = $this->studentLectureRepository->findOneBy([
                'student' => $student,
                'lecture' => $courseLecture
            ]);

            if ($progress) {
                $allLectureProgress[$courseLecture->getId()->toString()] = $progress;
            }
        }

        // Find next and previous lectures
        $previousLecture = null;
        $nextLecture = null;
        $currentIndex = null;

        foreach ($allLectures as $index => $courseLecture) {
            if ($courseLecture->getId()->toString() === $lectureId) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex !== null) {
            if ($currentIndex > 0) {
                $previousLecture = $allLectures[$currentIndex - 1];
            }
            if ($currentIndex < count($allLectures) - 1) {
                $nextLecture = $allLectures[$currentIndex + 1];
            }
        }

        // Update enrollment's current lecture and startDate if first access
        if (!$enrollment->getStartDate()) {
            $enrollment->setStartDate(new \DateTimeImmutable());
        }
        $enrollment->setCurrentLecture($lecture);
        $enrollment->setLastDate(new \DateTimeImmutable());
        $this->studentCourseRepository->save($enrollment, true);

        return $this->render('student/lecture.html.twig', [
            'course' => $course,
            'lecture' => $lecture,
            'enrollment' => $enrollment,
            'studentProgress' => $studentProgress,
            'previousLecture' => $previousLecture,
            'nextLecture' => $nextLecture,
            'modules' => $modules,
            'allLectures' => $allLectures,
            'allLectureProgress' => $allLectureProgress,
        ]);
    }
}
