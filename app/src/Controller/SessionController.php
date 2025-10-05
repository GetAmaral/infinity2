<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/session')]
class SessionController extends AbstractController
{
    /**
     * Get session status without extending the session
     * This endpoint checks remaining session time but does NOT count as user activity
     */
    #[Route('/status', name: 'api_session_status', methods: ['GET'])]
    public function status(Request $request, SessionInterface $session): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse([
                'authenticated' => false,
                'remaining' => 0,
                'expired' => true,
            ]);
        }

        // Get session metadata
        $metadata = $session->getMetadataBag();
        $sessionLifetime = ini_get('session.gc_maxlifetime') ?: 3600; // Default 1 hour
        $lastUsed = $metadata->getLastUsed();
        $now = time();

        $elapsed = $now - $lastUsed;
        $remaining = max(0, $sessionLifetime - $elapsed);

        return new JsonResponse([
            'authenticated' => true,
            'remaining' => $remaining,
            'elapsed' => $elapsed,
            'lifetime' => (int) $sessionLifetime,
            'expired' => $remaining <= 0,
            'lastActivity' => date('Y-m-d H:i:s', $lastUsed),
        ]);
    }

    /**
     * Keep session alive - explicitly extends session
     * This endpoint DOES count as user activity and resets the session timer
     */
    #[Route('/keepalive', name: 'api_session_keepalive', methods: ['POST'])]
    public function keepalive(Request $request, SessionInterface $session): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        // Trigger session activity by accessing session data
        // This updates the session's last used timestamp
        $session->set('_keepalive', time());

        $metadata = $session->getMetadataBag();
        $sessionLifetime = ini_get('session.gc_maxlifetime') ?: 3600;

        return new JsonResponse([
            'success' => true,
            'message' => 'Session extended',
            'lifetime' => (int) $sessionLifetime,
            'expiresAt' => date('Y-m-d H:i:s', time() + $sessionLifetime),
        ]);
    }
}
