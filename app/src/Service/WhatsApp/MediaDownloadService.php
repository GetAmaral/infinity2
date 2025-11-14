<?php

declare(strict_types=1);

namespace App\Service\WhatsApp;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Media Download Service
 *
 * Downloads media files from Evolution API URLs and prepares them for storage.
 */
class MediaDownloadService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $tempDir = '/tmp'
    ) {
    }

    /**
     * Download media file from URL
     *
     * @param string $url Media file URL from Evolution API
     * @param string $mimeType MIME type of the file
     * @return File Downloaded file
     * @throws \Exception on download failure
     */
    public function downloadFromUrl(string $url, string $mimeType): File
    {
        $this->logger->info('Downloading media from URL', [
            'url' => $url,
            'mime_type' => $mimeType,
        ]);

        try {
            // Download file
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 60, // 1 minute for large files
                'max_duration' => 120, // 2 minutes maximum
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception('Failed to download media: HTTP ' . $statusCode);
            }

            // Get file content
            $content = $response->getContent();
            if (empty($content)) {
                throw new \Exception('Downloaded file is empty');
            }

            // Generate unique filename
            $extension = $this->getExtensionFromMimeType($mimeType);
            $tempFilename = uniqid('whatsapp_media_', true) . '.' . $extension;
            $tempPath = $this->tempDir . '/' . $tempFilename;

            // Save to temp file
            file_put_contents($tempPath, $content);

            if (!file_exists($tempPath)) {
                throw new \Exception('Failed to save temp file');
            }

            $this->logger->info('Media downloaded successfully', [
                'temp_path' => $tempPath,
                'size' => filesize($tempPath),
            ]);

            return new File($tempPath);

        } catch (\Exception $e) {
            $this->logger->error('Failed to download media', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Download media with Evolution API authentication
     *
     * @param string $url Media file URL
     * @param string $apiKey Evolution API key
     * @param string $mimeType MIME type
     * @return File Downloaded file
     * @throws \Exception on download failure
     */
    public function downloadWithAuth(string $url, string $apiKey, string $mimeType): File
    {
        $this->logger->info('Downloading media with auth', [
            'url' => $url,
            'mime_type' => $mimeType,
        ]);

        try {
            // Download file with API key header
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'apikey' => $apiKey,
                ],
                'timeout' => 60,
                'max_duration' => 120,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception('Failed to download media: HTTP ' . $statusCode);
            }

            // Get file content
            $content = $response->getContent();
            if (empty($content)) {
                throw new \Exception('Downloaded file is empty');
            }

            // Generate unique filename
            $extension = $this->getExtensionFromMimeType($mimeType);
            $tempFilename = uniqid('whatsapp_media_', true) . '.' . $extension;
            $tempPath = $this->tempDir . '/' . $tempFilename;

            // Save to temp file
            file_put_contents($tempPath, $content);

            if (!file_exists($tempPath)) {
                throw new \Exception('Failed to save temp file');
            }

            $this->logger->info('Media downloaded successfully with auth', [
                'temp_path' => $tempPath,
                'size' => filesize($tempPath),
            ]);

            return new File($tempPath);

        } catch (\Exception $e) {
            $this->logger->error('Failed to download media with auth', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType MIME type
     * @return string File extension
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            // Images
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',

            // Videos
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'video/x-msvideo' => 'avi',

            // Audio
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'audio/webm' => 'weba',
            'audio/aac' => 'aac',
            'audio/opus' => 'opus',

            // Documents
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',

            default => 'bin'
        };
    }

    /**
     * Get media type category from MIME type
     *
     * @param string $mimeType MIME type
     * @return string Category: image, video, audio, document
     */
    public function getMediaTypeCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Check if MIME type is supported
     *
     * @param string $mimeType MIME type
     * @return bool True if supported
     */
    public function isSupportedMimeType(string $mimeType): bool
    {
        $supported = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',

            // Videos
            'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm',

            // Audio
            'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm', 'audio/aac', 'audio/opus',

            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv',
            'application/zip',
        ];

        return in_array($mimeType, $supported);
    }
}
