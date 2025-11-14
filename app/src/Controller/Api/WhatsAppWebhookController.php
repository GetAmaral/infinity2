<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\WhatsApp\WhatsAppService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * WhatsApp Webhook Controller
 *
 * Receives webhooks from Evolution API when WhatsApp messages are received.
 * This is a public endpoint (no authentication required) - Evolution API controls access.
 */
#[Route('/api/webhooks/whatsapp', name: 'api_whatsapp_webhook_')]
class WhatsAppWebhookController extends AbstractController
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Evolution API webhook endpoint
     *
     * Receives MESSAGES_UPSERT events from Evolution API and processes them.
     * Always returns 200 OK to prevent Evolution API from retrying.
     *
     * @param Request $request HTTP request
     * @return JsonResponse Success response
     */
    #[Route('/evolution', name: 'evolution', methods: ['POST'])]
    public function evolutionWebhook(Request $request): JsonResponse
    {
        try {
            // 1. Decode JSON payload
            $payload = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON in webhook', [
                    'error' => json_last_error_msg(),
                    'content' => substr($request->getContent(), 0, 500),
                ]);

                // Still return 200 to prevent retries
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON',
                ], 200);
            }

            // 2. Log webhook reception
            $this->logger->info('WhatsApp webhook received', [
                'event' => $payload['event'] ?? 'unknown',
                'instance' => $payload['instance'] ?? 'unknown',
                'data_keys' => array_keys($payload['data'] ?? []),
            ]);

            // 3. Validate payload structure
            if (!isset($payload['event']) || !isset($payload['data'])) {
                $this->logger->warning('Missing required fields in webhook', [
                    'payload_keys' => array_keys($payload),
                ]);

                // Still return 200 to prevent retries
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields',
                ], 200);
            }

            // 4. Process message via WhatsAppService
            $this->whatsAppService->processIncomingMessage($payload);

            // 5. Return success
            return new JsonResponse([
                'success' => true,
                'message' => 'Webhook processed',
            ], 200);

        } catch (\Exception $e) {
            // Log error but still return 200 to prevent Evolution API retry storms
            $this->logger->error('Error processing WhatsApp webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Internal error',
            ], 200);
        }
    }
}
