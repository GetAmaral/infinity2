<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StudentLecture;
use App\Repository\CourseLectureRepository;
use App\Repository\StudentCourseRepository;
use App\Repository\StudentLectureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/student/progress')]
final class StudentProgressController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseLectureRepository $lectureRepository,
        private readonly StudentLectureRepository $studentLectureRepository,
        private readonly StudentCourseRepository $studentCourseRepository
    ) {}

    #[Route('/lecture/{lectureId}', name: 'student_progress_update', methods: ['POST'])]
    public function updateProgress(string $lectureId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $lecture = $this->lectureRepository->find($lectureId);
        if (!$lecture) {
            return new JsonResponse(['error' => 'Lecture not found'], Response::HTTP_NOT_FOUND);
        }

        // Parse JSON body
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $position = (int) ($data['position'] ?? 0);
        $duration = (int) ($data['duration'] ?? 0);

        // Find StudentCourse enrollment
        $studentCourse = $this->studentCourseRepository->findOneBy([
            'student' => $user,
            'course' => $lecture->getCourseModule()->getCourse(),
            'active' => true
        ]);

        // Find or create StudentLecture
        $studentLecture = $this->studentLectureRepository->findOneBy([
            'student' => $user,
            'lecture' => $lecture
        ]);

        if (!$studentLecture) {
            $studentLecture = new StudentLecture();
            $studentLecture->setStudent($user);
            $studentLecture->setLecture($lecture);
            $studentLecture->setStudentCourse($studentCourse); // Set parent relationship
            $this->entityManager->persist($studentLecture);
        }

        // Update progress
        $studentLecture->setLastPositionSeconds($position);
        $studentLecture->setLastWatchedAt(new \DateTimeImmutable());

        // Update watched seconds (approximate - use position as proxy for watched time)
        if ($position > $studentLecture->getWatchedSeconds()) {
            $studentLecture->setWatchedSeconds($position);
        }

        // Update StudentCourse currentLecture to track last watched lecture
        if ($studentCourse) {
            $studentCourse->setCurrentLecture($lecture);

            // Set startDate if this is the first time watching
            if (!$studentCourse->getStartDate()) {
                $studentCourse->setStartDate(new \DateTimeImmutable());
            }
        }

        // First flush: persists StudentLecture and triggers lifecycle callbacks
        // PreUpdate calculates completionPercentage, PostUpdate modifies parent StudentCourse
        $this->entityManager->flush();

        // Second flush: persists the parent StudentCourse changes made in PostUpdate callback
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'position' => $position,
            'completion' => $studentLecture->getCompletionPercentage(),
            'completed' => $studentLecture->isCompleted()
        ]);
    }

    #[Route('/lecture/{lectureId}/milestone', name: 'student_progress_milestone', methods: ['POST'])]
    public function recordMilestone(string $lectureId, Request $request): JsonResponse
    {
        // Milestones are now calculated dynamically based on completionPercentage
        // This endpoint can be kept for backwards compatibility but doesn't need to do anything
        return new JsonResponse([
            'success' => true,
            'message' => 'Milestones are calculated automatically based on watch progress'
        ]);
    }

    #[Route('/lecture/{lectureId}', name: 'student_progress_get', methods: ['GET'])]
    public function getProgress(string $lectureId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $lecture = $this->lectureRepository->find($lectureId);
        if (!$lecture) {
            return new JsonResponse(['error' => 'Lecture not found'], Response::HTTP_NOT_FOUND);
        }

        $studentLecture = $this->studentLectureRepository->findOneBy([
            'student' => $user,
            'lecture' => $lecture
        ]);

        if (!$studentLecture) {
            return new JsonResponse([
                'position' => 0,
                'completion' => 0,
                'completed' => false
            ]);
        }

        return new JsonResponse([
            'position' => $studentLecture->getLastPositionSeconds(),
            'completion' => $studentLecture->getCompletionPercentage(),
            'completed' => $studentLecture->isCompleted(),
            'milestones' => [
                25 => $studentLecture->isReached25Percent(),
                50 => $studentLecture->isReached50Percent(),
                75 => $studentLecture->isReached75Percent(),
            ]
        ]);
    }

    #[Route('/lecture/{lectureId}/complete', name: 'student_progress_complete', methods: ['POST'])]
    public function toggleCompletion(string $lectureId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $lecture = $this->lectureRepository->find($lectureId);
        if (!$lecture) {
            return new JsonResponse(['error' => 'Lecture not found'], Response::HTTP_NOT_FOUND);
        }

        // Parse JSON body
        $data = json_decode($request->getContent(), true);
        $shouldBeCompleted = $data['completed'] ?? false;

        error_log(sprintf('[Completion] User %s toggling lecture %s to: %s',
            $user->getId(),
            $lectureId,
            $shouldBeCompleted ? 'COMPLETED' : 'INCOMPLETE'
        ));

        // Find StudentCourse enrollment
        $studentCourse = $this->studentCourseRepository->findOneBy([
            'student' => $user,
            'course' => $lecture->getCourseModule()->getCourse(),
            'active' => true
        ]);

        // Find or create StudentLecture
        $studentLecture = $this->studentLectureRepository->findOneBy([
            'student' => $user,
            'lecture' => $lecture
        ]);

        if (!$studentLecture) {
            $studentLecture = new StudentLecture();
            $studentLecture->setStudent($user);
            $studentLecture->setLecture($lecture);
            $studentLecture->setStudentCourse($studentCourse);
            $this->entityManager->persist($studentLecture);
        }

        $lectureLength = $lecture->getLengthSeconds();

        error_log(sprintf('[Completion] Lecture length: %d seconds', $lectureLength));

        if ($shouldBeCompleted) {
            // Mark as completed: set watched seconds to full lecture length
            if ($lectureLength > 0) {
                $studentLecture->setWatchedSeconds($lectureLength);
                $studentLecture->setLastPositionSeconds($lectureLength);
                error_log(sprintf('[Completion] Set watched seconds to: %d', $lectureLength));
            } else {
                error_log('[Completion] WARNING: Lecture length is 0, cannot mark as complete');
                // Set to 100 seconds as a fallback if lecture length is not set
                $studentLecture->setWatchedSeconds(100);
                $studentLecture->setLastPositionSeconds(100);
            }
        } else {
            // Mark as incomplete: reset to 0 or keep current position if less than full
            $currentWatched = $studentLecture->getWatchedSeconds();
            error_log(sprintf('[Completion] Current watched: %d, Lecture length: %d', $currentWatched, $lectureLength));
            if ($currentWatched >= $lectureLength) {
                // Was at 100%, reset to 0
                $studentLecture->setWatchedSeconds(0);
                $studentLecture->setLastPositionSeconds(0);
                error_log('[Completion] Reset to 0');
            } else {
                error_log('[Completion] Keeping current progress');
            }
            // If less than 100%, keep current progress
        }

        $studentLecture->setLastWatchedAt(new \DateTimeImmutable());

        // Update StudentCourse currentLecture
        if ($studentCourse) {
            $studentCourse->setCurrentLecture($lecture);
            if (!$studentCourse->getStartDate()) {
                $studentCourse->setStartDate(new \DateTimeImmutable());
            }
        }

        // Completion percentage and status will be calculated automatically in PreUpdate lifecycle

        // First flush: persists StudentLecture and triggers lifecycle callbacks
        $this->entityManager->flush();

        // Second flush: persists the parent StudentCourse changes made in PostUpdate callback
        $this->entityManager->flush();

        // Get updated course progress for response
        $courseProgress = $studentCourse ? $studentCourse->getProgressPercentage() : 0;

        error_log(sprintf('[Completion] Saved successfully. Lecture: %s%%, Course: %s%%',
            $studentLecture->getCompletionPercentage(),
            $courseProgress
        ));

        return new JsonResponse([
            'success' => true,
            'completed' => $studentLecture->isCompleted(),
            'completion' => $studentLecture->getCompletionPercentage(),
            'courseProgress' => $courseProgress
        ]);
    }
}
