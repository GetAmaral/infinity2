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
