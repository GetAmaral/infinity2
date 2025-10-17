<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

class BackupService
{
    private const BACKUP_DIR = __DIR__ . '/../../var/generatorBackup';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create backup of files before generation
     *
     * @param array<string> $filePaths
     * @return string Backup directory path
     */
    public function createBackup(array $filePaths, string $reason = 'generation'): string
    {
        $timestamp = date('Ymd_His');
        $backupDir = self::BACKUP_DIR . '/' . $timestamp;

        $this->filesystem->mkdir($backupDir);
        $this->logger->info('Creating backup', ['dir' => $backupDir, 'reason' => $reason]);

        $manifest = [
            'timestamp' => $timestamp,
            'reason' => $reason,
            'files' => []
        ];

        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            // Backup file
            $relativePath = $this->getRelativePath($filePath);
            $backupPath = $backupDir . '/' . str_replace('/', '_', $relativePath) . '.bak';

            $this->filesystem->copy($filePath, $backupPath);

            // Generate checksum
            $checksum = md5_file($filePath);
            file_put_contents($backupPath . '.md5', $checksum);

            $manifest['files'][] = [
                'original' => $filePath,
                'backup' => $backupPath,
                'checksum' => $checksum,
                'size' => filesize($filePath)
            ];

            $this->logger->debug('Backed up file', [
                'original' => $filePath,
                'backup' => $backupPath
            ]);
        }

        // Write manifest
        file_put_contents(
            $backupDir . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );

        $this->logger->info('Backup created', [
            'dir' => $backupDir,
            'file_count' => count($manifest['files'])
        ]);

        return $backupDir;
    }

    /**
     * Restore files from backup
     */
    public function restoreBackup(string $backupDir): void
    {
        $manifestPath = $backupDir . '/manifest.json';

        if (!file_exists($manifestPath)) {
            throw new \RuntimeException("Backup manifest not found: {$manifestPath}");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $this->logger->warning('Restoring backup', ['dir' => $backupDir]);

        // Create safety backup before restore
        $originalFiles = array_column($manifest['files'], 'original');
        $safetyBackup = $this->createBackup($originalFiles, 'safety_before_restore');

        $this->logger->info('Safety backup created', ['dir' => $safetyBackup]);

        foreach ($manifest['files'] as $fileInfo) {
            $backupPath = $fileInfo['backup'];
            $originalPath = $fileInfo['original'];

            if (!file_exists($backupPath)) {
                $this->logger->error('Backup file not found', ['path' => $backupPath]);
                continue;
            }

            // Verify checksum
            $storedChecksum = trim(file_get_contents($backupPath . '.md5'));
            $actualChecksum = md5_file($backupPath);

            if ($storedChecksum !== $actualChecksum) {
                throw new \RuntimeException(
                    "Checksum mismatch for {$backupPath}: expected {$storedChecksum}, got {$actualChecksum}"
                );
            }

            // Restore file
            $this->filesystem->copy($backupPath, $originalPath);

            $this->logger->info('Restored file', [
                'from' => $backupPath,
                'to' => $originalPath
            ]);
        }

        $this->logger->warning('Backup restored', [
            'dir' => $backupDir,
            'file_count' => count($manifest['files'])
        ]);
    }

    /**
     * List all backups
     *
     * @return array<array<string, mixed>>
     */
    public function listBackups(): array
    {
        if (!is_dir(self::BACKUP_DIR)) {
            return [];
        }

        $backups = [];
        $dirs = scandir(self::BACKUP_DIR, SCANDIR_SORT_DESCENDING);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $backupDir = self::BACKUP_DIR . '/' . $dir;
            $manifestPath = $backupDir . '/manifest.json';

            if (!file_exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            $backups[] = [
                'timestamp' => $manifest['timestamp'],
                'reason' => $manifest['reason'],
                'file_count' => count($manifest['files']),
                'path' => $backupDir
            ];
        }

        return $backups;
    }

    /**
     * Delete old backups (keep last N)
     */
    public function pruneBackups(int $keepCount = 10): void
    {
        $backups = $this->listBackups();

        if (count($backups) <= $keepCount) {
            return;
        }

        $toDelete = array_slice($backups, $keepCount);

        foreach ($toDelete as $backup) {
            $this->filesystem->remove($backup['path']);
            $this->logger->info('Pruned old backup', ['path' => $backup['path']]);
        }
    }

    /**
     * Get relative path from project root
     */
    private function getRelativePath(string $absolutePath): string
    {
        $projectRoot = realpath(__DIR__ . '/../..');
        return str_replace($projectRoot . '/', '', $absolutePath);
    }
}
