<?php

namespace App\Service\WhatsApp;

use Psr\Log\LoggerInterface;

/**
 * WhatsApp Cloud API Webhook Parser
 * Parses and validates Meta's webhook payloads
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/components
 */
class WhatsAppWebhookParser
{
    public function __construct(
        private string $appSecret,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Parse webhook payload from Meta
     *
     * Webhook structure:
     * {
     *   "object": "whatsapp_business_account",
     *   "entry": [{
     *     "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
     *     "changes": [{
     *       "value": {
     *         "messaging_product": "whatsapp",
     *         "metadata": {
     *           "display_phone_number": "15550000000",
     *           "phone_number_id": "123456789012345"
     *         },
     *         "contacts": [{"profile": {"name": "John Doe"}, "wa_id": "5511999999999"}],
     *         "messages": [{
     *           "from": "5511999999999",
     *           "id": "wamid.xxx",
     *           "timestamp": "1234567890",
     *           "type": "text",
     *           "text": {"body": "Hello"}
     *         }]
     *       },
     *       "field": "messages"
     *     }]
     *   }]
     * }
     *
     * @param array $payload Decoded JSON webhook payload
     * @return array|null Parsed message data or null if not a message event
     */
    public function parseWebhook(array $payload): ?array
    {
        // Validate webhook structure
        if (!isset($payload['entry'][0]['changes'][0]['value'])) {
            $this->logger->warning('WhatsApp Webhook: Invalid structure', ['payload' => $payload]);
            return null;
        }

        $value = $payload['entry'][0]['changes'][0]['value'];

        // Only process message events (not statuses)
        if (!isset($value['messages'][0])) {
            $this->logger->debug('WhatsApp Webhook: No messages in payload', ['value' => $value]);
            return null;
        }

        $metadata = $value['metadata'] ?? [];
        $contact = $value['contacts'][0] ?? [];
        $message = $value['messages'][0];

        // Extract key information
        $parsed = [
            // Routing information (CRITICAL for multi-tenant)
            'phone_number_id' => $metadata['phone_number_id'] ?? null,
            'display_phone_number' => $metadata['display_phone_number'] ?? null,

            // Message information
            'message_id' => $message['id'] ?? null,
            'from' => $message['from'] ?? null,
            'timestamp' => $message['timestamp'] ?? null,
            'type' => $message['type'] ?? 'unknown',

            // Contact information
            'contact_name' => $contact['profile']['name'] ?? null,
            'contact_wa_id' => $contact['wa_id'] ?? null,

            // Message content (depends on type)
            'content' => $this->extractMessageContent($message),

            // Raw data for debugging
            'raw_message' => $message,
            'raw_metadata' => $metadata,
        ];

        $this->logger->info('WhatsApp Webhook: Message parsed', [
            'phone_number_id' => $parsed['phone_number_id'],
            'from' => $parsed['from'],
            'type' => $parsed['type'],
        ]);

        return $parsed;
    }

    /**
     * Validate webhook signature (X-Hub-Signature-256)
     *
     * @param string $payload Raw request body (JSON string)
     * @param string $signature Header value of X-Hub-Signature-256
     * @return bool True if signature is valid
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        // Remove 'sha256=' prefix
        $signature = str_replace('sha256=', '', $signature);

        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $this->appSecret);

        // Use hash_equals to prevent timing attacks
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            $this->logger->error('WhatsApp Webhook: Invalid signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Extract message content based on type
     */
    private function extractMessageContent(array $message): mixed
    {
        return match ($message['type']) {
            'text' => $message['text']['body'] ?? null,
            'image' => [
                'id' => $message['image']['id'] ?? null,
                'mime_type' => $message['image']['mime_type'] ?? null,
                'caption' => $message['image']['caption'] ?? null,
            ],
            'video' => [
                'id' => $message['video']['id'] ?? null,
                'mime_type' => $message['video']['mime_type'] ?? null,
                'caption' => $message['video']['caption'] ?? null,
            ],
            'audio' => [
                'id' => $message['audio']['id'] ?? null,
                'mime_type' => $message['audio']['mime_type'] ?? null,
            ],
            'document' => [
                'id' => $message['document']['id'] ?? null,
                'mime_type' => $message['document']['mime_type'] ?? null,
                'filename' => $message['document']['filename'] ?? null,
            ],
            'location' => [
                'latitude' => $message['location']['latitude'] ?? null,
                'longitude' => $message['location']['longitude'] ?? null,
                'name' => $message['location']['name'] ?? null,
                'address' => $message['location']['address'] ?? null,
            ],
            'interactive' => $message['interactive'] ?? null,
            'button' => $message['button'] ?? null,
            default => $message,
        };
    }
}
