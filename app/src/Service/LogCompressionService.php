<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class LogCompressionService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Compress log files older than specified number of days.
     *
     * @param string $logDir The directory containing log files
     * @param int $olderThanDays Number of days - files older than this will be compressed
     * @return array Statistics about compression: ['compressed' => int, 'space_saved' => int, 'errors' => array]
     */
    public function compressOldLogs(string $logDir, int $olderThanDays = 7): array
    {
        $stats = [
            'compressed' => 0,
            'space_saved' => 0,
            'errors' => [],
        ];

        if (!is_dir($logDir)) {
            $stats['errors'][] = "Directory does not exist: {$logDir}";
            $this->logger->error('Log compression failed: directory not found', ['dir' => $logDir]);
            return $stats;
        }

        $cutoffTimestamp = time() - ($olderThanDays * 24 * 60 * 60);

        // Find all .log files (excluding already compressed .gz files)
        $logFiles = glob($logDir . '/*.log');

        if ($logFiles === false) {
            $stats['errors'][] = "Failed to read directory: {$logDir}";
            return $stats;
        }

        foreach ($logFiles as $logFile) {
            try {
                // Skip if file is too recent
                $fileModTime = filemtime($logFile);
                if ($fileModTime === false || $fileModTime >= $cutoffTimestamp) {
                    continue;
                }

                // Skip if file is already compressed or is the current day's log
                $basename = basename($logFile);
                if (str_ends_with($basename, '.gz') || $this->isCurrentLog($basename)) {
                    continue;
                }

                $this->compressFile($logFile, $stats);
            } catch (\Exception $e) {
                $stats['errors'][] = "Error processing {$logFile}: " . $e->getMessage();
                $this->logger->error('Log compression error', [
                    'file' => $logFile,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Log compression completed', $stats);

        return $stats;
    }

    /**
     * Check if this is a current log file that should not be compressed.
     */
    private function isCurrentLog(string $filename): bool
    {
        $today = date('Y-m-d');

        // Check if filename contains today's date
        if (str_contains($filename, $today)) {
            return true;
        }

        // Check if it's a base log file without date (current active file)
        $baseLogFiles = [
            'audit.log',
            'app.log',
            'performance.log',
            'security.log',
            'business.log',
            'video_processing.log',
            'dev.log',
            'prod.log',
            'test.log',
        ];

        return in_array($filename, $baseLogFiles, true);
    }

    /**
     * Compress a single log file.
     */
    private function compressFile(string $logFile, array &$stats): void
    {
        $originalSize = filesize($logFile);
        if ($originalSize === false) {
            throw new \RuntimeException("Cannot get file size: {$logFile}");
        }

        // Read file content
        $content = file_get_contents($logFile);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file: {$logFile}");
        }

        // Compress content
        $compressed = gzencode($content, 9); // Maximum compression level
        if ($compressed === false) {
            throw new \RuntimeException("Compression failed: {$logFile}");
        }

        // Write compressed file
        $compressedFile = $logFile . '.gz';
        if (file_put_contents($compressedFile, $compressed) === false) {
            throw new \RuntimeException("Cannot write compressed file: {$compressedFile}");
        }

        // Verify compressed file was created successfully
        if (!file_exists($compressedFile)) {
            throw new \RuntimeException("Compressed file not created: {$compressedFile}");
        }

        // Delete original file
        if (!unlink($logFile)) {
            // Try to clean up compressed file on failure
            @unlink($compressedFile);
            throw new \RuntimeException("Cannot delete original file: {$logFile}");
        }

        $compressedSize = filesize($compressedFile);
        $spaceSaved = $originalSize - ($compressedSize ?: 0);

        $stats['compressed']++;
        $stats['space_saved'] += $spaceSaved;

        $this->logger->info('Log file compressed', [
            'file' => basename($logFile),
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'space_saved' => $spaceSaved,
            'compression_ratio' => $originalSize > 0 ? round(($compressedSize / $originalSize) * 100, 2) . '%' : 'N/A',
        ]);
    }

    /**
     * Delete compressed log files older than retention period.
     *
     * @param string $logDir The directory containing log files
     * @param int $retentionDays Number of days to keep compressed logs
     * @return array Statistics about deletion: ['deleted' => int, 'space_freed' => int, 'errors' => array]
     */
    public function deleteOldCompressedLogs(string $logDir, int $retentionDays = 90): array
    {
        $stats = [
            'deleted' => 0,
            'space_freed' => 0,
            'errors' => [],
        ];

        if (!is_dir($logDir)) {
            $stats['errors'][] = "Directory does not exist: {$logDir}";
            return $stats;
        }

        $cutoffTimestamp = time() - ($retentionDays * 24 * 60 * 60);

        // Find all compressed .gz files
        $compressedFiles = glob($logDir . '/*.log.gz');

        if ($compressedFiles === false) {
            $stats['errors'][] = "Failed to read directory: {$logDir}";
            return $stats;
        }

        foreach ($compressedFiles as $compressedFile) {
            try {
                $fileModTime = filemtime($compressedFile);
                if ($fileModTime === false || $fileModTime >= $cutoffTimestamp) {
                    continue;
                }

                $fileSize = filesize($compressedFile);

                if (unlink($compressedFile)) {
                    $stats['deleted']++;
                    $stats['space_freed'] += $fileSize ?: 0;

                    $this->logger->info('Old compressed log deleted', [
                        'file' => basename($compressedFile),
                        'size' => $fileSize,
                    ]);
                } else {
                    $stats['errors'][] = "Cannot delete: {$compressedFile}";
                }
            } catch (\Exception $e) {
                $stats['errors'][] = "Error deleting {$compressedFile}: " . $e->getMessage();
            }
        }

        $this->logger->info('Old compressed logs cleanup completed', $stats);

        return $stats;
    }

    /**
     * Get human-readable file size.
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
