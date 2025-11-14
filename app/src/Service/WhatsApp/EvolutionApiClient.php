<?php

declare(strict_types=1);

namespace App\Service\WhatsApp;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Evolution API Client
 *
 * HTTP client for Evolution API v2 operations.
 * Handles sending messages and checking instance status.
 */
class EvolutionApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send text message via Evolution API
     *
     * @param string $serverUrl Evolution API base URL (e.g., "https://evolution-api.yourdomain.com")
     * @param string $apiKey Instance API key
     * @param string $instanceName Instance identifier (e.g., "sdr-agent-1")
     * @param string $phoneNumber Recipient phone number (digits only, no + or @)
     * @param string $text Message text
     * @return array API response
     * @throws \Exception on HTTP errors
     */
    public function sendTextMessage(
        string $serverUrl,
        string $apiKey,
        string $instanceName,
        string $phoneNumber,
        string $text
    ): array {
        $url = rtrim($serverUrl, '/') . '/message/sendText/' . $instanceName;

        $payload = [
            'number' => $phoneNumber,
            'textMessage' => [
                'text' => $text,
            ],
        ];

        $this->logger->debug('Sending text message to Evolution API', [
            'url' => $url,
            'instance' => $instanceName,
            'phone' => $phoneNumber,
            'textLength' => strlen($text),
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'apikey' => $apiKey,
                ],
                'json' => $payload,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false); // false = don't throw on error status

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error('Evolution API returned error status', [
                    'status' => $statusCode,
                    'response' => $content,
                    'url' => $url,
                ]);
                throw new \Exception('Evolution API error: ' . ($content['message'] ?? 'Unknown error'));
            }

            $this->logger->info('Message sent successfully via Evolution API', [
                'instance' => $instanceName,
                'phone' => $phoneNumber,
                'status' => $statusCode,
            ]);

            return $content;

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Failed to connect to Evolution API', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Evolution API connection failed: ' . $e->getMessage(), 0, $e);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $this->logger->error('Evolution API HTTP error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Evolution API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get Evolution API instance connection status
     *
     * @param string $serverUrl Evolution API base URL
     * @param string $apiKey Instance API key
     * @param string $instanceName Instance identifier
     * @return array Status object
     * @throws \Exception on HTTP errors
     */
    public function getInstanceStatus(
        string $serverUrl,
        string $apiKey,
        string $instanceName
    ): array {
        $url = rtrim($serverUrl, '/') . '/instance/connectionState/' . $instanceName;

        $this->logger->debug('Checking Evolution API instance status', [
            'url' => $url,
            'instance' => $instanceName,
        ]);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'apikey' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error('Failed to get instance status', [
                    'status' => $statusCode,
                    'response' => $content,
                    'url' => $url,
                ]);
                throw new \Exception('Evolution API error: ' . ($content['message'] ?? 'Unknown error'));
            }

            $this->logger->info('Instance status retrieved', [
                'instance' => $instanceName,
                'status' => $content['instance']['state'] ?? 'unknown',
            ]);

            return $content;

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Failed to connect to Evolution API', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Evolution API connection failed: ' . $e->getMessage(), 0, $e);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $this->logger->error('Evolution API HTTP error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Evolution API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send media message via Evolution API
     *
     * @param string $serverUrl Evolution API base URL
     * @param string $apiKey Instance API key
     * @param string $instanceName Instance identifier
     * @param string $phoneNumber Recipient phone (digits only)
     * @param string $mediaUrl URL of the media file to send
     * @param string $caption Optional caption
     * @param string $mediaType Type: image, video, audio, document
     * @return array API response
     * @throws \Exception on HTTP errors
     */
    public function sendMediaMessage(
        string $serverUrl,
        string $apiKey,
        string $instanceName,
        string $phoneNumber,
        string $mediaUrl,
        string $caption = '',
        string $mediaType = 'image'
    ): array {
        // Determine the correct endpoint based on media type
        $endpoint = match ($mediaType) {
            'image' => '/message/sendImage/',
            'video' => '/message/sendVideo/',
            'audio' => '/message/sendAudio/',
            'document' => '/message/sendDocument/',
            default => '/message/sendImage/',
        };

        $url = rtrim($serverUrl, '/') . $endpoint . $instanceName;

        $payload = [
            'number' => $phoneNumber,
        ];

        // Different media types have different payload structures
        switch ($mediaType) {
            case 'image':
                $payload['imageMessage'] = [
                    'image' => $mediaUrl,
                    'caption' => $caption,
                ];
                break;

            case 'video':
                $payload['videoMessage'] = [
                    'video' => $mediaUrl,
                    'caption' => $caption,
                ];
                break;

            case 'audio':
                $payload['audioMessage'] = [
                    'audio' => $mediaUrl,
                ];
                break;

            case 'document':
                $payload['documentMessage'] = [
                    'document' => $mediaUrl,
                    'caption' => $caption,
                    'fileName' => basename($mediaUrl),
                ];
                break;
        }

        $this->logger->debug('Sending media message to Evolution API', [
            'url' => $url,
            'instance' => $instanceName,
            'phone' => $phoneNumber,
            'media_type' => $mediaType,
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'apikey' => $apiKey,
                ],
                'json' => $payload,
                'timeout' => 60, // Longer timeout for media
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error('Evolution API returned error status for media', [
                    'status' => $statusCode,
                    'response' => $content,
                    'url' => $url,
                ]);
                throw new \Exception('Evolution API error: ' . ($content['message'] ?? 'Unknown error'));
            }

            $this->logger->info('Media message sent successfully via Evolution API', [
                'instance' => $instanceName,
                'phone' => $phoneNumber,
                'media_type' => $mediaType,
                'status' => $statusCode,
            ]);

            return $content;

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Failed to connect to Evolution API', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Evolution API connection failed: ' . $e->getMessage(), 0, $e);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $this->logger->error('Evolution API HTTP error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Evolution API HTTP error: ' . $e->getMessage(), 0, $e);
        }
    }
}
