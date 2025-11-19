<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\WhatsApp\WhatsAppService;
use App\Service\WhatsApp\WhatsAppWebhookParser;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * WhatsApp Webhook Controller
 *
 * Handles webhooks from:
 * - WhatsApp Cloud API (Meta's official API) - NEW
 * - Evolution API (deprecated) - LEGACY
 */
#[Route('/api/webhooks/whatsapp', name: 'api_whatsapp_webhook_')]
class WhatsAppWebhookController extends AbstractController
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly LoggerInterface $logger,
        private readonly ?WhatsAppWebhookParser $webhookParser = null,
        private readonly ?string $verifyToken = null
    ) {
    }

    /**
     * WhatsApp Cloud API Webhook Verification (GET)
     *
     * Meta sends GET request during webhook setup with:
     * - hub.mode=subscribe
     * - hub.verify_token=YOUR_VERIFY_TOKEN
     * - hub.challenge=RANDOM_STRING
     *
     * Must respond with the challenge string to verify webhook ownership.
     */
    #[Route('/cloud', name: 'cloud_verify', methods: ['GET'])]
    public function cloudWebhookVerify(Request $request): Response
    {
        $mode = $request->query->get('hub_mode');
        $token = $request->query->get('hub_verify_token');
        $challenge = $request->query->get('hub_challenge');

        $this->logger->info('WhatsApp Cloud API: Verification request', [
            'mode' => $mode,
            'token_match' => $token === $this->verifyToken,
        ]);

        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            $this->logger->info('WhatsApp Cloud API: Verification successful');
            return new Response($challenge, 200);
        }

        $this->logger->warning('WhatsApp Cloud API: Verification failed', [
            'mode' => $mode,
            'expected_token' => $this->verifyToken,
            'received_token' => $token,
        ]);

        return new Response('Forbidden', 403);
    }

    /**
     * WhatsApp Cloud API Webhook Receiver (POST)
     *
     * Receives messages and status updates from Meta's WhatsApp Cloud API.
     * Validates signature and processes messages via WhatsAppService.
     */
    #[Route('/cloud', name: 'cloud_receive', methods: ['POST'])]
    public function cloudWebhookReceive(Request $request): JsonResponse
    {
        // Get raw payload for signature validation
        $rawPayload = $request->getContent();
        $signature = $request->headers->get('X-Hub-Signature-256', '');

        // Validate signature (if webhook parser is configured)
        if ($this->webhookParser && !$this->webhookParser->validateSignature($rawPayload, $signature)) {
            $this->logger->error('WhatsApp Cloud API: Invalid signature');
            return new JsonResponse(['error' => 'Invalid signature'], 403);
        }

        // Decode payload
        $payload = json_decode($rawPayload, true);

        if (!$payload) {
            $this->logger->error('WhatsApp Cloud API: Invalid JSON payload');
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // Parse webhook (if webhook parser is configured)
        if ($this->webhookParser) {
            $parsedMessage = $this->webhookParser->parseWebhook($payload);

            if (!$parsedMessage) {
                // Not a message event (maybe status update), acknowledge and skip
                return new JsonResponse(['status' => 'ok']);
            }

            // Process message asynchronously
            try {
                $this->whatsAppService->processInboundMessage($parsedMessage);
                return new JsonResponse(['status' => 'ok']);

            } catch (\Exception $e) {
                $this->logger->error('WhatsApp Cloud API: Message processing failed', [
                    'error' => $e->getMessage(),
                    'parsed_message' => $parsedMessage,
                ]);

                // Still return 200 to avoid webhook retries
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Fallback if parser not configured
        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * WhatsApp Cloud API Test Endpoint (POST)
     *
     * ⚠️ DEVELOPMENT ONLY - NO SIGNATURE VALIDATION ⚠️
     * Use this endpoint for local testing without requiring Meta signature.
     * Will be disabled in production.
     */
    #[Route('/cloud/test', name: 'cloud_test', methods: ['POST'])]
    public function cloudWebhookTest(Request $request): JsonResponse
    {
        // Only allow in dev environment
        if ($this->getParameter('kernel.environment') !== 'dev') {
            $this->logger->warning('WhatsApp Cloud API Test: Attempted access in non-dev environment');
            return new JsonResponse(['error' => 'Test endpoint only available in dev environment'], 403);
        }

        // Decode payload (no signature validation)
        $payload = json_decode($request->getContent(), true);

        if (!$payload) {
            $this->logger->error('WhatsApp Cloud API Test: Invalid JSON payload');
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $this->logger->info('WhatsApp Cloud API Test: Received test webhook', [
            'payload_structure' => array_keys($payload),
        ]);

        // Parse webhook (if webhook parser is configured)
        if ($this->webhookParser) {
            $parsedMessage = $this->webhookParser->parseWebhook($payload);

            if (!$parsedMessage) {
                // Not a message event (maybe status update)
                return new JsonResponse([
                    'status' => 'ok',
                    'message' => 'Not a message event (status update or other)',
                ]);
            }

            // Log parsed message
            $this->logger->info('WhatsApp Cloud API Test: Parsed message', [
                'parsed' => $parsedMessage,
            ]);

            // Process message
            try {
                $this->whatsAppService->processInboundMessage($parsedMessage);

                return new JsonResponse([
                    'status' => 'success',
                    'message_id' => $parsedMessage['message_id'] ?? null,
                    'phone_number_id' => $parsedMessage['phone_number_id'] ?? null,
                    'from' => $parsedMessage['from'] ?? null,
                    'agent_name' => 'Check logs for agent resolution',
                ]);

            } catch (\Exception $e) {
                $this->logger->error('WhatsApp Cloud API Test: Message processing failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'parsed_message' => $parsedMessage,
                ]);

                return new JsonResponse([
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'parsed_message' => $parsedMessage,
                ], 200); // Still 200 to avoid retries
            }
        }

        // Fallback if parser not configured
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Webhook parser not configured',
        ], 500);
    }

    /**
     * Evolution API webhook endpoint (DEPRECATED)
     *
     * Receives MESSAGES_UPSERT events from Evolution API and processes them.
     * Always returns 200 OK to prevent Evolution API from retrying.
     *
     * This endpoint will be removed after migration to Cloud API is complete.
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
