<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health')]
    public function check(): JsonResponse
    {
        return $this->json([
            'status' => 'OK',
            'timestamp' => new \DateTimeImmutable(),
            'version' => '1.0.0'
        ]);
    }
}