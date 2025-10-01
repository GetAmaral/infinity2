<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StudentLecture;
use App\Repository\CourseLectureRepository;
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
        private readonly StudentLectureRepository $studentLectureRepository
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
        $completion = (float) ($data['completion'] ?? 0);

        // Find or create StudentLecture
        $studentLecture = $this->studentLectureRepository->findOneBy([
            'student' => $user,
            'lecture' => $lecture
        ]);

        if (!$studentLecture) {
            $studentLecture = new StudentLecture();
            $studentLecture->setStudent($user);
            $studentLecture->setLecture($lecture);
            $this->entityManager->persist($studentLecture);
        }

        // Update progress
        $studentLecture->setLastPositionSeconds($position);
        $studentLecture->setCompletionPercentage($completion);
        $studentLecture->setLastWatchedAt(new \DateTimeImmutable());

        // Increment watched seconds (approximate)
        if ($position > $studentLecture->getWatchedSeconds()) {
            $studentLecture->setWatchedSeconds($position);
        }

        // Mark as completed if >= 90%
        if ($completion >= 90 && !$studentLecture->isCompleted()) {
            $studentLecture->setCompleted(true);
            $studentLecture->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'position' => $position,
            'completion' => $completion,
            'completed' => $studentLecture->isCompleted()
        ]);
    }

    #[Route('/lecture/{lectureId}/milestone', name: 'student_progress_milestone', methods: ['POST'])]
    public function recordMilestone(string $lectureId, Request $request): JsonResponse
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
        if (!$data || !isset($data['milestone'])) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $milestone = (int) $data['milestone'];

        // Find or create StudentLecture
        $studentLecture = $this->studentLectureRepository->findOneBy([
            'student' => $user,
            'lecture' => $lecture
        ]);

        if (!$studentLecture) {
            $studentLecture = new StudentLecture();
            $studentLecture->setStudent($user);
            $studentLecture->setLecture($lecture);
            $this->entityManager->persist($studentLecture);
        }

        // Record milestone
        switch ($milestone) {
            case 25:
                $studentLecture->setReached25Percent(true);
                break;
            case 50:
                $studentLecture->setReached50Percent(true);
                break;
            case 75:
                $studentLecture->setReached75Percent(true);
                break;
            case 100:
                $studentLecture->setCompleted(true);
                $studentLecture->setCompletedAt(new \DateTimeImmutable());
                break;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'milestone' => $milestone
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
}
