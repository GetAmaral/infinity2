<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Talk;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/talks', name: 'api_talk_pause_')]
#[IsGranted('ROLE_USER')]
class TalkPauseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Pause a conversation
     */
    #[Route('/{id}/pause', name: 'pause', methods: ['POST'])]
    public function pause(Talk $talk, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Paused by user';

        $talk->setPaused(true);
        $talk->setPausedAt(new \DateTimeImmutable());
        $talk->setPausedReason($reason);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'paused' => true,
            'pausedAt' => $talk->getPausedAt()->format('c'),
            'reason' => $reason,
        ]);
    }

    /**
     * Resume a paused conversation
     */
    #[Route('/{id}/resume', name: 'resume', methods: ['POST'])]
    public function resume(Talk $talk): JsonResponse
    {
        if (!$talk->isPaused()) {
            return new JsonResponse([
                'error' => 'Talk is not paused',
            ], 400);
        }

        $talk->setPaused(false);
        $talk->setResumedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'paused' => false,
            'resumedAt' => $talk->getResumedAt()->format('c'),
        ]);
    }

    /**
     * Check if conversation is paused
     */
    #[Route('/{id}/pause/status', name: 'pause_status', methods: ['GET'])]
    public function getPauseStatus(Talk $talk): JsonResponse
    {
        return new JsonResponse([
            'paused' => $talk->isPaused(),
            'pausedAt' => $talk->getPausedAt()?->format('c'),
            'pausedReason' => $talk->getPausedReason(),
            'resumedAt' => $talk->getResumedAt()?->format('c'),
        ]);
    }
}
