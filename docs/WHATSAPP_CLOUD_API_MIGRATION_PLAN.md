# WhatsApp Cloud API Migration Plan

**Status**: Ready for Implementation
**Estimated Duration**: 10-14 days
**Last Updated**: 2025-11-14

---

## Executive Summary

This plan details the migration from Evolution API (Baileys-based) to WhatsApp Cloud API (Meta's official Graph API) for the Luminai multi-tenant platform.

### Why Migrate?

1. **Current Problem**: Evolution API v2.2.3 failing with WebSocket "Connection Failure" errors (Baileys library blocked by WhatsApp)
2. **Cost**: WhatsApp Cloud API has FREE tier (1,000 conversations/month + unlimited service messages)
3. **Stability**: Official Meta API vs unofficial library prone to blocking
4. **Features**: Access to official Business API features (templates, media, interactive messages)

### Key Architecture Decisions

✅ **Genmax-Compliant**: Uses existing `whatsappMetadata` JSON field (no schema changes)
✅ **Multi-Tenant**: Routes via `phone_number_id` → Agent → Organization
✅ **Zero Downtime**: Phased migration with rollback capability
✅ **Production-Ready**: Comprehensive testing and monitoring

---

## Phase 0: Pre-Flight Checks (Day 1)

### Backup Current System

```bash
# 1. Backup database
docker-compose exec -T database pg_dump -U luminai_user -d luminai_db > backups/luminai_db_$(date +%Y%m%d_%H%M%S).sql

# 2. Backup evolution_db database
docker-compose exec -T database pg_dump -U luminai_user -d evolution_db > backups/evolution_db_$(date +%Y%m%d_%H%M%S).sql

# 3. Tag current Git state
git tag -a whatsapp-migration-pre-$(date +%Y%m%d) -m "Pre-WhatsApp migration state"
git push origin whatsapp-migration-pre-$(date +%Y%m%d)

# 4. Export current environment
cp .env .env.backup.$(date +%Y%m%d)
```

### Verify Genmax Configuration

```bash
# Verify Agent entity has whatsappMetadata field
docker-compose exec -T app php bin/console dbal:run-sql \
  "SELECT property_name, property_type FROM generator_property
   WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Agent')
   AND property_name = 'whatsappMetadata';"

# Expected output: whatsappMetadata | json
```

### Document Current Agents

```bash
# Export current WhatsApp agent configuration
docker-compose exec -T app php bin/console dbal:run-sql \
  "SELECT id, name, whatsapp_instance_name, whatsapp_active, organization_id
   FROM agent
   WHERE whatsapp_active = true;" \
  > backups/active_agents_$(date +%Y%m%d).txt
```

---

## Phase 1: Meta Business Account Setup (Day 2-3)

### 1.1 Create Meta Business Account

1. Go to [Meta Business Suite](https://business.facebook.com/)
2. Create Business Account (if not exists)
3. Add WhatsApp product

### 1.2 Set Up WhatsApp Business App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Create App → Business → WhatsApp
3. Add WhatsApp Product to your app
4. Configure Business Phone Numbers

### 1.3 Get Required Credentials

**You will need:**

1. **Phone Number ID** (`WHATSAPP_PHONE_NUMBER_ID`)
   - Location: WhatsApp > API Setup > Phone Number ID
   - Format: `123456789012345` (15 digits)

2. **Access Token** (`WHATSAPP_ACCESS_TOKEN`)
   - Location: WhatsApp > API Setup > Temporary/Permanent Access Token
   - Format: `EAAGxxx...` (long alphanumeric string)
   - **IMPORTANT**: Use System User token for production (doesn't expire)

3. **App Secret** (`WHATSAPP_APP_SECRET`)
   - Location: App Settings > Basic > App Secret (Click "Show")
   - Format: `abc123def456...` (32 hex characters)

4. **Webhook Verify Token** (`WHATSAPP_VERIFY_TOKEN`)
   - You create this yourself (random secure string)
   - Example: `luminai_webhook_verify_2025_secure_token`

### 1.4 Configure Webhook URL

**Webhook URL**: `https://yourdomain.com/api/whatsapp/webhook`

1. Go to WhatsApp > Configuration > Webhook
2. Set Callback URL: `https://yourdomain.com/api/whatsapp/webhook`
3. Set Verify Token: (your `WHATSAPP_VERIFY_TOKEN`)
4. Subscribe to webhook fields:
   - ✅ messages
   - ✅ message_status (delivery, read receipts)

---

## Phase 2: Environment Configuration (Day 3)

### 2.1 Update `.env` File

Add to `/home/user/inf/.env`:

```bash
###> WhatsApp Cloud API Configuration ###
WHATSAPP_CLOUD_API_VERSION=v23.0
WHATSAPP_CLOUD_BASE_URL=https://graph.facebook.com

# Primary Phone Number (default for system)
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_ACCESS_TOKEN=EAAGxxx...your_token_here
WHATSAPP_APP_SECRET=abc123def456...your_app_secret

# Webhook Security
WHATSAPP_VERIFY_TOKEN=luminai_webhook_verify_2025_secure_token
###< WhatsApp Cloud API Configuration ###
```

### 2.2 Update `config/services.yaml`

Add to `/home/user/inf/app/config/services.yaml`:

```yaml
parameters:
    whatsapp.cloud.api.version: '%env(WHATSAPP_CLOUD_API_VERSION)%'
    whatsapp.cloud.base_url: '%env(WHATSAPP_CLOUD_BASE_URL)%'
    whatsapp.phone_number_id: '%env(WHATSAPP_PHONE_NUMBER_ID)%'
    whatsapp.access_token: '%env(WHATSAPP_ACCESS_TOKEN)%'
    whatsapp.app_secret: '%env(WHATSAPP_APP_SECRET)%'
    whatsapp.verify_token: '%env(WHATSAPP_VERIFY_TOKEN)%'

services:
    # WhatsApp Cloud API Client
    App\Service\WhatsApp\WhatsAppCloudApiClient:
        arguments:
            $httpClient: '@http_client'
            $baseUrl: '%whatsapp.cloud.base_url%'
            $apiVersion: '%whatsapp.cloud.api.version%'
            $defaultPhoneNumberId: '%whatsapp.phone_number_id%'
            $defaultAccessToken: '%whatsapp.access_token%'
            $logger: '@monolog.logger.whatsapp'

    # WhatsApp Webhook Parser
    App\Service\WhatsApp\WhatsAppWebhookParser:
        arguments:
            $appSecret: '%whatsapp.app_secret%'
            $logger: '@monolog.logger.whatsapp'

    # Update existing WhatsAppService
    App\Service\WhatsApp\WhatsAppService:
        arguments:
            $cloudApiClient: '@App\Service\WhatsApp\WhatsAppCloudApiClient'
            $webhookParser: '@App\Service\WhatsApp\WhatsAppWebhookParser'
            # ... existing arguments
```

---

## Phase 3: Implement New Services (Day 4-6)

### 3.1 Create WhatsAppCloudApiClient

**File**: `/home/user/inf/app/src/Service/WhatsApp/WhatsAppCloudApiClient.php`

```php
<?php

namespace App\Service\WhatsApp;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * WhatsApp Cloud API Client
 * Official Meta Graph API integration for WhatsApp Business
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
 */
class WhatsAppCloudApiClient
{
    private const GRAPH_API_ENDPOINT = '/messages';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $apiVersion,
        private string $defaultPhoneNumberId,
        private string $defaultAccessToken,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Send text message
     *
     * @param string $to Recipient phone number (E.164 format: +5511999999999)
     * @param string $message Message text (max 4096 characters)
     * @param bool $previewUrl Enable URL preview
     * @param string|null $phoneNumberId Override default phone number ID
     * @param string|null $accessToken Override default access token
     * @return array Response from WhatsApp API
     */
    public function sendTextMessage(
        string $to,
        string $message,
        bool $previewUrl = false,
        ?string $phoneNumberId = null,
        ?string $accessToken = null
    ): array {
        $phoneNumberId ??= $this->defaultPhoneNumberId;
        $accessToken ??= $this->defaultAccessToken;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhoneNumber($to),
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $message,
            ],
        ];

        return $this->sendRequest($phoneNumberId, $payload, $accessToken);
    }

    /**
     * Send media message (image, video, audio, document)
     *
     * @param string $to Recipient phone number
     * @param string $mediaType One of: image, video, audio, document
     * @param string $mediaUrl URL or media ID
     * @param string|null $caption Optional caption (images/videos only)
     * @param string|null $filename Optional filename (documents only)
     * @param string|null $phoneNumberId Override default phone number ID
     * @param string|null $accessToken Override default access token
     * @return array Response from WhatsApp API
     */
    public function sendMediaMessage(
        string $to,
        string $mediaType,
        string $mediaUrl,
        ?string $caption = null,
        ?string $filename = null,
        ?string $phoneNumberId = null,
        ?string $accessToken = null
    ): array {
        $phoneNumberId ??= $this->defaultPhoneNumberId;
        $accessToken ??= $this->defaultAccessToken;

        $mediaPayload = str_starts_with($mediaUrl, 'http')
            ? ['link' => $mediaUrl]
            : ['id' => $mediaUrl];

        if ($caption && in_array($mediaType, ['image', 'video'])) {
            $mediaPayload['caption'] = $caption;
        }

        if ($filename && $mediaType === 'document') {
            $mediaPayload['filename'] = $filename;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhoneNumber($to),
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ];

        return $this->sendRequest($phoneNumberId, $payload, $accessToken);
    }

    /**
     * Upload media to WhatsApp servers
     *
     * @param string $filePath Local file path
     * @param string $mimeType MIME type (e.g., image/jpeg)
     * @param string|null $phoneNumberId Override default phone number ID
     * @param string|null $accessToken Override default access token
     * @return string Media ID
     */
    public function uploadMedia(
        string $filePath,
        string $mimeType,
        ?string $phoneNumberId = null,
        ?string $accessToken = null
    ): string {
        $phoneNumberId ??= $this->defaultPhoneNumberId;
        $accessToken ??= $this->defaultAccessToken;

        try {
            $url = sprintf(
                '%s/%s/%s/media',
                $this->baseUrl,
                $this->apiVersion,
                $phoneNumberId
            );

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'body' => [
                    'file' => fopen($filePath, 'r'),
                    'type' => $mimeType,
                    'messaging_product' => 'whatsapp',
                ],
            ]);

            $data = $response->toArray();

            $this->logger->info('WhatsApp Cloud API: Media uploaded', [
                'media_id' => $data['id'] ?? null,
                'file_path' => $filePath,
            ]);

            return $data['id'];

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Cloud API: Media upload failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);
            throw $e;
        }
    }

    /**
     * Get media URL from media ID
     *
     * @param string $mediaId Media ID from webhook
     * @param string|null $accessToken Override default access token
     * @return array ['url' => '...', 'mime_type' => '...', 'file_size' => ...]
     */
    public function getMediaUrl(string $mediaId, ?string $accessToken = null): array
    {
        $accessToken ??= $this->defaultAccessToken;

        try {
            $url = sprintf(
                '%s/%s/%s',
                $this->baseUrl,
                $this->apiVersion,
                $mediaId
            );

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Cloud API: Get media URL failed', [
                'error' => $e->getMessage(),
                'media_id' => $mediaId,
            ]);
            throw $e;
        }
    }

    /**
     * Download media from WhatsApp CDN
     *
     * @param string $mediaUrl URL from getMediaUrl()
     * @param string|null $accessToken Override default access token
     * @return string Binary file content
     */
    public function downloadMedia(string $mediaUrl, ?string $accessToken = null): string
    {
        $accessToken ??= $this->defaultAccessToken;

        try {
            $response = $this->httpClient->request('GET', $mediaUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            return $response->getContent();

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Cloud API: Download media failed', [
                'error' => $e->getMessage(),
                'media_url' => $mediaUrl,
            ]);
            throw $e;
        }
    }

    /**
     * Mark message as read
     *
     * @param string $messageId Message ID from webhook
     * @param string|null $phoneNumberId Override default phone number ID
     * @param string|null $accessToken Override default access token
     * @return array Response from WhatsApp API
     */
    public function markAsRead(
        string $messageId,
        ?string $phoneNumberId = null,
        ?string $accessToken = null
    ): array {
        $phoneNumberId ??= $this->defaultPhoneNumberId;
        $accessToken ??= $this->defaultAccessToken;

        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendRequest($phoneNumberId, $payload, $accessToken);
    }

    /**
     * Send request to WhatsApp Cloud API
     */
    private function sendRequest(string $phoneNumberId, array $payload, string $accessToken): array
    {
        try {
            $url = sprintf(
                '%s/%s/%s%s',
                $this->baseUrl,
                $this->apiVersion,
                $phoneNumberId,
                self::GRAPH_API_ENDPOINT
            );

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray();

            $this->logger->info('WhatsApp Cloud API: Message sent', [
                'phone_number_id' => $phoneNumberId,
                'message_id' => $data['messages'][0]['id'] ?? null,
                'payload_type' => $payload['type'] ?? 'unknown',
            ]);

            return $data;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('WhatsApp Cloud API: Request failed', [
                'error' => $e->getMessage(),
                'phone_number_id' => $phoneNumberId,
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Normalize phone number to E.164 format
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // Add + prefix if not present
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
```

### 3.2 Create WhatsAppWebhookParser

**File**: `/home/user/inf/app/src/Service/WhatsApp/WhatsAppWebhookParser.php`

```php
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
```

### 3.3 Update WhatsAppWebhookController

**File**: `/home/user/inf/app/src/Controller/Api/WhatsAppWebhookController.php`

```php
<?php

namespace App\Controller\Api;

use App\Service\WhatsApp\WhatsAppWebhookParser;
use App\Service\WhatsApp\WhatsAppService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/whatsapp/webhook', name: 'api_whatsapp_webhook_')]
class WhatsAppWebhookController extends AbstractController
{
    public function __construct(
        private WhatsAppWebhookParser $webhookParser,
        private WhatsAppService $whatsAppService,
        private LoggerInterface $logger,
        private string $verifyToken
    ) {
    }

    /**
     * Webhook verification (GET)
     *
     * Meta sends GET request with:
     * - hub.mode=subscribe
     * - hub.verify_token=YOUR_VERIFY_TOKEN
     * - hub.challenge=RANDOM_STRING
     *
     * You must respond with the challenge string
     */
    #[Route('', name: 'verify', methods: ['GET'])]
    public function verify(Request $request): Response
    {
        $mode = $request->query->get('hub_mode');
        $token = $request->query->get('hub_verify_token');
        $challenge = $request->query->get('hub_challenge');

        $this->logger->info('WhatsApp Webhook: Verification request', [
            'mode' => $mode,
            'token_match' => $token === $this->verifyToken,
        ]);

        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            $this->logger->info('WhatsApp Webhook: Verification successful');
            return new Response($challenge, 200);
        }

        $this->logger->warning('WhatsApp Webhook: Verification failed', [
            'mode' => $mode,
            'expected_token' => $this->verifyToken,
            'received_token' => $token,
        ]);

        return new Response('Forbidden', 403);
    }

    /**
     * Webhook receiver (POST)
     *
     * Receives messages and status updates from Meta
     */
    #[Route('', name: 'receive', methods: ['POST'])]
    public function receive(Request $request): Response
    {
        // Get raw payload for signature validation
        $rawPayload = $request->getContent();
        $signature = $request->headers->get('X-Hub-Signature-256', '');

        // Validate signature
        if (!$this->webhookParser->validateSignature($rawPayload, $signature)) {
            $this->logger->error('WhatsApp Webhook: Invalid signature');
            return $this->json(['error' => 'Invalid signature'], 403);
        }

        // Decode payload
        $payload = json_decode($rawPayload, true);

        if (!$payload) {
            $this->logger->error('WhatsApp Webhook: Invalid JSON payload');
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        // Parse webhook
        $parsedMessage = $this->webhookParser->parseWebhook($payload);

        if (!$parsedMessage) {
            // Not a message event (maybe status update), acknowledge and skip
            return $this->json(['status' => 'ok']);
        }

        // Process message asynchronously
        try {
            $this->whatsAppService->processInboundMessage($parsedMessage);

            return $this->json(['status' => 'ok']);

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Webhook: Message processing failed', [
                'error' => $e->getMessage(),
                'parsed_message' => $parsedMessage,
            ]);

            // Still return 200 to avoid webhook retries
            return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
```

### 3.4 Update WhatsAppService

**File**: `/home/user/inf/app/src/Service/WhatsApp/WhatsAppService.php`

**Changes needed:**

```php
// OLD (Evolution API)
private function identifyAgentByInstance(string $instanceName): ?Agent
{
    return $this->agentRepository->createQueryBuilder('a')
        ->where('a.whatsappInstanceName = :instanceName')
        ->andWhere('a.whatsappActive = :active')
        ->setParameter('instanceName', $instanceName)
        ->setParameter('active', true)
        ->getQuery()
        ->getOneOrNullResult();
}

// NEW (WhatsApp Cloud API)
private function identifyAgentByPhoneNumberId(string $phoneNumberId): ?Agent
{
    return $this->agentRepository->createQueryBuilder('a')
        ->where("JSON_EXTRACT(a.whatsappMetadata, '$.phone_number_id') = :phone_number_id")
        ->andWhere('a.whatsappActive = :whatsapp_active')
        ->andWhere('a.active = :general_active')
        ->setParameter('phone_number_id', $phoneNumberId)
        ->setParameter('whatsapp_active', true)
        ->setParameter('general_active', true)
        ->getQuery()
        ->getOneOrNullResult();
}

// Update processInboundMessage method
public function processInboundMessage(array $parsedMessage): void
{
    // Multi-tenant routing: phone_number_id → Agent → Organization
    $phoneNumberId = $parsedMessage['phone_number_id'];
    $agent = $this->identifyAgentByPhoneNumberId($phoneNumberId);

    if (!$agent) {
        $this->logger->warning('WhatsApp: No agent found for phone_number_id', [
            'phone_number_id' => $phoneNumberId,
        ]);
        return;
    }

    // Set organization context for multi-tenant filtering
    $this->tenantContext->setOrganization($agent->getOrganization());

    // Rest of processing logic...
    // (existing TalkFlow integration continues as before)
}

// Update sendMessage method to use WhatsAppCloudApiClient
public function sendMessage(Agent $agent, string $to, string $message): void
{
    $metadata = $agent->getWhatsAppMetadata();

    $phoneNumberId = $metadata['phone_number_id'] ?? null;
    $accessToken = $metadata['access_token'] ?? null;

    if (!$phoneNumberId || !$accessToken) {
        throw new \InvalidArgumentException('Agent WhatsApp not configured');
    }

    $this->cloudApiClient->sendTextMessage(
        $to,
        $message,
        previewUrl: true,
        phoneNumberId: $phoneNumberId,
        accessToken: $accessToken
    );
}
```

---

## Phase 4: Update Agent Entity (Day 7)

### 4.1 Add Helper Methods to Agent.php

**File**: `/home/user/inf/app/src/Entity/Agent.php` (extension class - safe to edit)

```php
<?php

namespace App\Entity;

use App\Entity\Generated\AgentGenerated;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
class Agent extends AgentGenerated
{
    /**
     * Get WhatsApp phone_number_id from metadata
     */
    public function getWhatsAppPhoneNumberId(): ?string
    {
        return $this->whatsappMetadata['phone_number_id'] ?? null;
    }

    /**
     * Set WhatsApp phone_number_id in metadata
     */
    public function setWhatsAppPhoneNumberId(string $phoneNumberId): self
    {
        $metadata = $this->whatsappMetadata ?? [];
        $metadata['phone_number_id'] = $phoneNumberId;
        $this->whatsappMetadata = $metadata;

        return $this;
    }

    /**
     * Get WhatsApp access token from metadata
     */
    public function getWhatsAppAccessToken(): ?string
    {
        return $this->whatsappMetadata['access_token'] ?? null;
    }

    /**
     * Set WhatsApp access token in metadata
     */
    public function setWhatsAppAccessToken(string $accessToken): self
    {
        $metadata = $this->whatsappMetadata ?? [];
        $metadata['access_token'] = $accessToken;
        $this->whatsappMetadata = $metadata;

        return $this;
    }

    /**
     * Get full WhatsApp metadata array
     */
    public function getWhatsAppMetadata(): array
    {
        return $this->whatsappMetadata ?? [];
    }

    /**
     * Set full WhatsApp metadata array
     */
    public function setWhatsAppMetadata(array $metadata): self
    {
        $this->whatsappMetadata = $metadata;
        return $this;
    }

    /**
     * Check if Agent is configured for WhatsApp Cloud API
     */
    public function isWhatsAppCloudConfigured(): bool
    {
        return $this->whatsappActive
            && !empty($this->whatsappMetadata['phone_number_id'])
            && !empty($this->whatsappMetadata['access_token']);
    }

    /**
     * Validate WhatsApp Cloud API configuration
     *
     * @throws \InvalidArgumentException If configuration is invalid
     */
    public function validateWhatsAppCloudConfig(): void
    {
        if (!$this->whatsappActive) {
            throw new \InvalidArgumentException('WhatsApp is not active for this Agent');
        }

        if (empty($this->whatsappMetadata['phone_number_id'])) {
            throw new \InvalidArgumentException('phone_number_id is required in whatsappMetadata');
        }

        if (empty($this->whatsappMetadata['access_token'])) {
            throw new \InvalidArgumentException('access_token is required in whatsappMetadata');
        }

        // Validate phone_number_id format (15 digits)
        if (!preg_match('/^\d{15}$/', $this->whatsappMetadata['phone_number_id'])) {
            throw new \InvalidArgumentException('Invalid phone_number_id format (expected 15 digits)');
        }

        // Validate access_token format (starts with EAA)
        if (!str_starts_with($this->whatsappMetadata['access_token'], 'EAA')) {
            throw new \InvalidArgumentException('Invalid access_token format (expected to start with EAA)');
        }
    }
}
```

---

## Phase 5: Remove Evolution API (Day 8)

### 5.1 Update docker-compose.yml

**File**: `/home/user/inf/docker-compose.yml`

**Remove** the entire `evolution-api` service:

```yaml
# DELETE THIS ENTIRE BLOCK:
  evolution-api:
    image: atendai/evolution-api:v2.2.3
    container_name: luminai_evolution_api
    # ... entire service definition
```

**Update** nginx service (remove evolution-api dependency):

```yaml
nginx:
  image: nginx:1.27-alpine
  container_name: luminai_nginx
  depends_on:
    app:
      condition: service_healthy
    # REMOVE: evolution-api dependency
  ports:
    - "80:80"
    - "443:443"
  volumes:
    - ./nginx/conf/default.conf:/etc/nginx/conf.d/default.conf:ro
    - ./nginx/ssl:/etc/nginx/ssl:ro
  networks:
    - luminai_network
```

**Remove** evolution volumes:

```yaml
volumes:
  postgres_data:
  redis_data:
  # DELETE THESE:
  # evolution_instances:
  # evolution_store:
```

### 5.2 Update nginx configuration

**File**: `/home/user/inf/nginx/conf/default.conf`

**Remove** the Evolution API subdomain server block (lines 8-63 in current config):

```nginx
# DELETE THIS ENTIRE BLOCK:
# Evolution API - Dedicated subdomain for Manager UI and API
server {
    listen 443 ssl http2;
    server_name evolution.localhost;
    # ... entire block
}
```

### 5.3 Drop evolution_db database (OPTIONAL - only if you want to free space)

```bash
# Backup first!
docker-compose exec -T database pg_dump -U luminai_user -d evolution_db > backups/evolution_db_final_backup.sql

# Drop database
docker-compose exec -T database psql -U luminai_user -c "DROP DATABASE IF EXISTS evolution_db;"
```

---

## Phase 6: Database Index Optimization (Day 9)

### 6.1 Add Index via Genmax Configuration

**IMPORTANT**: Luminai uses Genmax for all database schema management. Indexes should be configured through Genmax, not direct migrations.

#### Option 1: Add Index via generator_entity Configuration

If Genmax supports custom indexes in `generator_entity` table, add index configuration there.

#### Option 2: Performance Optimization Script (Temporary)

Since this is a performance optimization index (not a schema change), you can optionally add it manually **after** confirming with your team:

```sql
-- OPTIONAL: Only run if approved by team
-- This is a performance optimization, not a schema change
CREATE INDEX IF NOT EXISTS idx_agent_whatsapp_phone_number_id
ON agent USING GIN ((whatsapp_metadata->'phone_number_id'));
```

**Note**: This index is optional. PostgreSQL can still query the JSON field without it, just slower at scale.

#### Query Performance Impact

**Without index:**
- Full table scan on `agent` table
- JSON field extraction for every row
- Acceptable for < 1,000 agents

**With index:**
- GIN index lookup (fast)
- Direct row access
- Recommended for > 1,000 agents

#### How to Add Index (If Approved)

```bash
# Connect to database
docker-compose exec -T database psql -U luminai_user -d luminai_db

# Run index creation (manual - outside Genmax)
CREATE INDEX IF NOT EXISTS idx_agent_whatsapp_phone_number_id
ON agent USING GIN ((whatsapp_metadata->'phone_number_id'));

# Verify index
\d agent
```

**⚠️ CAUTION**: This bypasses Genmax. Only do this if:
1. Team approves manual performance optimization
2. Genmax doesn't support custom JSON indexes
3. You have > 1,000 agents and experience slow queries

Otherwise, **skip this phase** - the feature works without the index.

---

## Phase 7: Testing (Day 10-11)

### 7.1 Manual Testing Checklist

#### Test 1: Webhook Verification (GET)

```bash
# Meta will send this during webhook setup
curl -X GET "https://yourdomain.com/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=luminai_webhook_verify_2025_secure_token&hub.challenge=CHALLENGE_STRING"

# Expected response: CHALLENGE_STRING (200 OK)
```

#### Test 2: Send Text Message

```bash
# Configure test Agent with phone_number_id and access_token
docker-compose exec app php bin/console dbal:run-sql "
UPDATE agent
SET whatsapp_metadata = '{
  \"phone_number_id\": \"123456789012345\",
  \"access_token\": \"EAAGxxx...\"
}'::jsonb
WHERE id = 'YOUR_AGENT_UUID';
"

# Test sending message via WhatsAppService
docker-compose exec app php -r "
require 'vendor/autoload.php';
\$kernel = new \App\Kernel('dev', true);
\$kernel->boot();
\$container = \$kernel->getContainer();

\$service = \$container->get('App\Service\WhatsApp\WhatsAppService');
\$agentRepo = \$container->get('App\Repository\AgentRepository');

\$agent = \$agentRepo->find('YOUR_AGENT_UUID');
\$service->sendMessage(\$agent, '+5511999999999', 'Hello from Luminai!');

echo 'Message sent successfully';
"
```

#### Test 3: Simulate Inbound Webhook (POST)

```bash
# Generate signature
APP_SECRET="your_app_secret"
PAYLOAD='{"object":"whatsapp_business_account","entry":[{"id":"123","changes":[{"value":{"messaging_product":"whatsapp","metadata":{"display_phone_number":"15550000000","phone_number_id":"123456789012345"},"contacts":[{"profile":{"name":"Test User"},"wa_id":"5511999999999"}],"messages":[{"from":"5511999999999","id":"wamid.test123","timestamp":"1234567890","type":"text","text":{"body":"Hello Luminai"}}]},"field":"messages"}]}]}'

SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$APP_SECRET" | sed 's/.* //')

curl -X POST https://yourdomain.com/api/whatsapp/webhook \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=$SIGNATURE" \
  -d "$PAYLOAD"

# Expected response: {"status":"ok"}
```

#### Test 4: Multi-Tenant Routing

```bash
# Create 2 agents in different organizations
# Agent A: org_id=ORG_A, phone_number_id=111111111111111
# Agent B: org_id=ORG_B, phone_number_id=222222222222222

# Send webhook with phone_number_id=111111111111111
# Verify: Message routed to Agent A and ORG_A

# Send webhook with phone_number_id=222222222222222
# Verify: Message routed to Agent B and ORG_B

# Check logs for organization context:
docker-compose exec app tail -f var/log/app.log | grep "Organization context"
```

### 7.2 Automated Testing

Create PHPUnit test:

**File**: `/home/user/inf/app/tests/Service/WhatsApp/WhatsAppCloudApiClientTest.php`

```php
<?php

namespace App\Tests\Service\WhatsApp;

use App\Service\WhatsApp\WhatsAppCloudApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class WhatsAppCloudApiClientTest extends TestCase
{
    public function testSendTextMessage(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
            'messages' => [['id' => 'wamid.test123']],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $client = new WhatsAppCloudApiClient(
            $httpClient,
            'https://graph.facebook.com',
            'v23.0',
            '123456789012345',
            'EAAtest',
            new NullLogger()
        );

        $result = $client->sendTextMessage('+5511999999999', 'Test message');

        $this->assertEquals('wamid.test123', $result['messages'][0]['id']);
    }
}
```

Run tests:

```bash
docker-compose exec app php bin/phpunit tests/Service/WhatsApp/
```

---

## Phase 8: Deployment & Monitoring (Day 12-14)

### 8.1 Deployment Steps

```bash
# 1. Stop Evolution API service
docker-compose stop evolution-api

# 2. Update code
git pull origin main

# 3. Clear cache
docker-compose exec app php bin/console cache:clear --env=prod

# 4. Run migrations
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 5. Rebuild and restart services
docker-compose up -d --build

# 6. Verify services
docker-compose ps

# 7. Test webhook endpoint
curl -k https://yourdomain.com/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123
```

### 8.2 Configure Webhook in Meta

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Select your WhatsApp app
3. Go to WhatsApp > Configuration
4. Set Callback URL: `https://yourdomain.com/api/whatsapp/webhook`
5. Set Verify Token: (your `WHATSAPP_VERIFY_TOKEN`)
6. Click "Verify and Save"
7. Subscribe to webhook fields:
   - ✅ messages
   - ✅ message_status

### 8.3 Migrate Existing Agents

Run migration script:

**File**: `/home/user/inf/app/scripts/migrate-agents-to-cloud-api.php`

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine')->getManager();
$agentRepository = $container->get('App\Repository\AgentRepository');

// Find all active WhatsApp agents
$agents = $agentRepository->createQueryBuilder('a')
    ->where('a.whatsappActive = :active')
    ->setParameter('active', true)
    ->getQuery()
    ->getResult();

echo "Found " . count($agents) . " active WhatsApp agents\n\n";

foreach ($agents as $agent) {
    echo "Agent: " . $agent->getName() . " (ID: " . $agent->getId() . ")\n";
    echo "Organization: " . $agent->getOrganization()->getName() . "\n";
    echo "Current Instance: " . ($agent->getWhatsappInstanceName() ?? 'N/A') . "\n\n";

    echo "Enter WhatsApp Configuration:\n";
    echo "phone_number_id (15 digits): ";
    $phoneNumberId = trim(fgets(STDIN));

    echo "access_token (starts with EAA): ";
    $accessToken = trim(fgets(STDIN));

    echo "display_phone_number (optional, e.g., +5511999999999): ";
    $displayPhoneNumber = trim(fgets(STDIN));

    // Validate
    if (!preg_match('/^\d{15}$/', $phoneNumberId)) {
        echo "ERROR: Invalid phone_number_id format\n\n";
        continue;
    }

    if (!str_starts_with($accessToken, 'EAA')) {
        echo "ERROR: Invalid access_token format\n\n";
        continue;
    }

    // Update metadata
    $metadata = [
        'phone_number_id' => $phoneNumberId,
        'access_token' => $accessToken,
    ];

    if ($displayPhoneNumber) {
        $metadata['display_phone_number'] = $displayPhoneNumber;
    }

    $agent->setWhatsAppMetadata($metadata);
    $entityManager->persist($agent);

    echo "✅ Configuration saved for " . $agent->getName() . "\n";
    echo "---\n\n";
}

$entityManager->flush();

echo "Migration complete!\n";
```

Run the script:

```bash
docker-compose exec app php scripts/migrate-agents-to-cloud-api.php
```

### 8.4 Monitoring

```bash
# Watch WhatsApp logs
docker-compose exec app tail -f var/log/app.log | grep WhatsApp

# Monitor webhook requests
docker-compose exec nginx tail -f /var/log/nginx/access.log | grep webhook

# Check message processing
docker-compose exec app php bin/console messenger:stats

# Health check
curl -k https://localhost/health/detailed | jq .
```

---

## Rollback Strategy

If migration fails, rollback with these steps:

### Step 1: Restore Git State

```bash
git checkout whatsapp-migration-pre-$(date +%Y%m%d)
```

### Step 2: Restore Database

```bash
# Restore luminai_db
docker-compose exec -T database psql -U luminai_user -d luminai_db < backups/luminai_db_YYYYMMDD_HHMMSS.sql

# Restore evolution_db
docker-compose exec -T database psql -U luminai_user -d evolution_db < backups/evolution_db_YYYYMMDD_HHMMSS.sql
```

### Step 3: Restore Environment

```bash
cp .env.backup.YYYYMMDD .env
```

### Step 4: Rebuild Services

```bash
docker-compose down
docker-compose up -d --build
```

---

## Post-Migration Cleanup

After successful migration (wait 1-2 weeks for stability):

### Optional: Remove Evolution Data

```bash
# 1. Drop evolution_db database
docker-compose exec -T database psql -U luminai_user -c "DROP DATABASE IF EXISTS evolution_db;"

# 2. Remove Evolution container volumes
docker volume rm inf_evolution_instances inf_evolution_store

# 3. Clean up Agent fields (optional - keep for audit trail)
# You can set whatsappInstanceName = NULL on all agents if desired
```

---

## Cost Analysis

### WhatsApp Cloud API Pricing (2025)

| Component | Free Tier | Paid Tier |
|-----------|-----------|-----------|
| **Service Conversations** | Unlimited | FREE |
| **User-Initiated Conversations** | First 1,000/month | $0.005 - $0.08 per conversation* |
| **Business-Initiated Conversations** | First 1,000/month | $0.005 - $0.08 per conversation* |

*Pricing varies by country. Brazil: ~$0.02 per conversation

**Service Conversations** (always FREE):
- Utility messages (OTP, shipping updates, etc.)
- Authentication messages

**24-hour Conversation Window**:
- Each conversation window = 24 hours
- Multiple messages in 24h = 1 conversation
- Cost-effective for active conversations

**Example Monthly Cost**:
- 5,000 conversations/month = $80-$100/month
- 10,000 conversations/month = $180-$200/month

**Compared to Evolution API**:
- Evolution: FREE but unstable (blocked by WhatsApp)
- Cloud API: Paid but official, stable, compliant

---

## Technical Reference

### WhatsApp Cloud API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/{phone-number-id}/messages` | POST | Send messages |
| `/{phone-number-id}/media` | POST | Upload media |
| `/{media-id}` | GET | Get media URL |
| `/webhook` | GET | Verify webhook |
| `/webhook` | POST | Receive messages |

### Webhook Event Types

| Type | Description |
|------|-------------|
| `messages` | Incoming messages |
| `message_status` | Delivery/read receipts |
| `errors` | Error notifications |

### Message Types Supported

- ✅ Text
- ✅ Image
- ✅ Video
- ✅ Audio
- ✅ Document
- ✅ Location
- ✅ Interactive (buttons, lists)
- ✅ Template (pre-approved messages)

---

## Security Checklist

- [ ] Use System User access token (doesn't expire)
- [ ] Store tokens in environment variables (never commit to Git)
- [ ] Validate webhook signatures (X-Hub-Signature-256)
- [ ] Use HTTPS for webhook endpoint
- [ ] Implement rate limiting on webhook endpoint
- [ ] Log all WhatsApp API calls for audit
- [ ] Rotate access tokens periodically
- [ ] Monitor for suspicious activity

---

## Support & Resources

### Official Documentation

- [WhatsApp Cloud API Docs](https://developers.facebook.com/docs/whatsapp/cloud-api/)
- [Webhook Components](https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/components)
- [Message Templates](https://developers.facebook.com/docs/whatsapp/message-templates/)

### Luminai Internal Docs

- [GENMAX.md](./Genmax/GENMAX.md) - Code generation system
- [MULTI_TENANT.md](./MULTI_TENANT.md) - Multi-tenant architecture
- [DEVELOPMENT_WORKFLOW.md](./DEVELOPMENT_WORKFLOW.md) - Development workflow

### Meta Support

- [Meta for Developers Community](https://developers.facebook.com/community/)
- [WhatsApp Business Support](https://business.whatsapp.com/support)

---

## Appendix A: Environment Variables Reference

```bash
# WhatsApp Cloud API Configuration
WHATSAPP_CLOUD_API_VERSION=v23.0                           # API version
WHATSAPP_CLOUD_BASE_URL=https://graph.facebook.com         # Base URL
WHATSAPP_PHONE_NUMBER_ID=123456789012345                   # Default phone number ID (15 digits)
WHATSAPP_ACCESS_TOKEN=EAAGxxx...                           # System user access token
WHATSAPP_APP_SECRET=abc123def456...                        # App secret for signature validation
WHATSAPP_VERIFY_TOKEN=luminai_webhook_verify_2025_secure_token  # Webhook verification token
```

---

## Appendix B: Database Schema Reference

### Agent.whatsappMetadata Structure

```json
{
  "phone_number_id": "123456789012345",
  "access_token": "EAAGxxx...",
  "display_phone_number": "+5511999999999",
  "business_account_id": "123456789012345",
  "configured_at": "2025-11-14T10:30:00Z",
  "verified": true
}
```

### Multi-Tenant Routing Flow

```
Inbound Webhook
  ↓
phone_number_id: "123456789012345"
  ↓
Query: SELECT * FROM agent WHERE whatsapp_metadata->>'phone_number_id' = '123456789012345'
  ↓
Agent (UUID: abc-123, Organization ID: xyz-789)
  ↓
TenantContext::setOrganization(xyz-789)
  ↓
Process message in Organization context
```

---

## Appendix C: Testing Commands Quick Reference

```bash
# Webhook Verification
curl -X GET "https://yourdomain.com/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123"

# Send Test Message
docker-compose exec app php -r "
require 'vendor/autoload.php';
\$kernel = new \App\Kernel('dev', true);
\$kernel->boot();
\$container = \$kernel->getContainer();
\$client = \$container->get('App\Service\WhatsApp\WhatsAppCloudApiClient');
\$client->sendTextMessage('+5511999999999', 'Test from Luminai');
"

# Check Agent Configuration
docker-compose exec app php bin/console dbal:run-sql "SELECT id, name, whatsapp_metadata FROM agent WHERE whatsapp_active = true;"

# Monitor Logs
docker-compose exec app tail -f var/log/app.log | grep WhatsApp

# Test Multi-Tenant Routing
docker-compose exec app php bin/console dbal:run-sql "
SELECT a.id, a.name, o.name as organization, a.whatsapp_metadata->>'phone_number_id' as phone_number_id
FROM agent a
JOIN organization o ON a.organization_id = o.id
WHERE a.whatsapp_active = true;
"
```

---

**End of Migration Plan**

**Next Steps**: Review plan → Get approval → Begin Phase 0 (Pre-flight checks)
