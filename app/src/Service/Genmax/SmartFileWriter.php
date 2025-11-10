<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Smart File Writer for Genmax
 *
 * Compares new content with existing file content before writing.
 * Only writes if content differs, preventing unnecessary file modifications
 * and Git pollution from timestamp-only changes.
 *
 * Benefits:
 * - Reduces unnecessary Git changes
 * - Prevents timestamp-only modifications
 * - Improves generation performance by skipping unchanged files
 * - Provides clear logging of what actually changed
 */
class SmartFileWriter
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Write file only if content differs from existing file
     *
     * @param string $filePath Absolute path to file
     * @param string $newContent New content to write
     * @param int $chmod File permissions (default: 0666)
     * @return string Status: 'written' (new/changed), 'skipped' (unchanged), or 'created' (new file)
     */
    public function writeFile(string $filePath, string $newContent, int $chmod = 0666): string
    {
        $fileExists = file_exists($filePath);

        // If file doesn't exist, create it
        if (!$fileExists) {
            $this->doWrite($filePath, $newContent, $chmod);
            $this->logger->info('[GENMAX] File created', [
                'file' => $this->getRelativePath($filePath),
                'size' => strlen($newContent)
            ]);
            return 'created';
        }

        // File exists - compare content
        $existingContent = file_get_contents($filePath);

        // Content is identical - skip writing
        if ($existingContent === $newContent) {
            $this->logger->info('[GENMAX] File unchanged (skipped)', [
                'file' => $this->getRelativePath($filePath),
                'size' => strlen($newContent)
            ]);
            return 'skipped';
        }

        // Content differs - write new content
        $this->doWrite($filePath, $newContent, $chmod);

        $this->logger->info('[GENMAX] File updated', [
            'file' => $this->getRelativePath($filePath),
            'old_size' => strlen($existingContent),
            'new_size' => strlen($newContent),
            'diff' => strlen($newContent) - strlen($existingContent)
        ]);

        return 'written';
    }

    /**
     * Write file and set permissions
     */
    private function doWrite(string $filePath, string $content, int $chmod): void
    {
        // Create directory if needed
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Write file atomically
        $this->filesystem->dumpFile($filePath, $content);

        // Set permissions
        chmod($filePath, $chmod);
    }

    /**
     * Get relative path for logging (removes project dir prefix)
     */
    private function getRelativePath(string $absolutePath): string
    {
        // Try to make path relative for cleaner logs
        $cwd = getcwd();
        if ($cwd && str_starts_with($absolutePath, $cwd)) {
            return substr($absolutePath, strlen($cwd) + 1);
        }
        return $absolutePath;
    }

    /**
     * Get statistics about file write operations
     *
     * @param array<string> $statuses Array of status strings ('written', 'skipped', 'created')
     * @return array{created: int, written: int, skipped: int, total: int}
     */
    public function getStatistics(array $statuses): array
    {
        return [
            'created' => count(array_filter($statuses, fn($s) => $s === 'created')),
            'written' => count(array_filter($statuses, fn($s) => $s === 'written')),
            'skipped' => count(array_filter($statuses, fn($s) => $s === 'skipped')),
            'total' => count($statuses)
        ];
    }
}
