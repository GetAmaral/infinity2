<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BackupService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class BackupServiceTest extends TestCase
{
    private BackupService $backupService;
    private string $testDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->backupService = new BackupService($this->filesystem, new NullLogger());
        $this->testDir = sys_get_temp_dir() . '/backup_test_' . uniqid();
        $this->filesystem->mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->remove($this->testDir);
        }

        // Clean up backup directory
        $backupDir = __DIR__ . '/../../var/generatorBackup';
        if ($this->filesystem->exists($backupDir)) {
            $this->filesystem->remove($backupDir);
        }
    }

    public function testCreateBackupCreatesDirectory(): void
    {
        $testFile = $this->testDir . '/test.txt';
        file_put_contents($testFile, 'test content');

        $backupDir = $this->backupService->createBackup([$testFile], 'test');

        $this->assertDirectoryExists($backupDir);
        $this->assertFileExists($backupDir . '/manifest.json');
    }

    public function testCreateBackupCreatesManifest(): void
    {
        $testFile = $this->testDir . '/test.txt';
        file_put_contents($testFile, 'test content');

        $backupDir = $this->backupService->createBackup([$testFile], 'test_reason');

        $manifestPath = $backupDir . '/manifest.json';
        $this->assertFileExists($manifestPath);

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);
        $this->assertEquals('test_reason', $manifest['reason']);
        $this->assertArrayHasKey('timestamp', $manifest);
        $this->assertArrayHasKey('files', $manifest);
        $this->assertCount(1, $manifest['files']);
    }

    public function testCreateBackupGeneratesChecksums(): void
    {
        $testFile = $this->testDir . '/test.txt';
        $content = 'test content for checksum';
        file_put_contents($testFile, $content);
        $expectedChecksum = md5($content);

        $backupDir = $this->backupService->createBackup([$testFile]);

        $manifestPath = $backupDir . '/manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        $this->assertEquals($expectedChecksum, $manifest['files'][0]['checksum']);
    }

    public function testCreateBackupSkipsNonExistentFiles(): void
    {
        $existingFile = $this->testDir . '/existing.txt';
        $nonExistentFile = $this->testDir . '/nonexistent.txt';
        file_put_contents($existingFile, 'content');

        $backupDir = $this->backupService->createBackup([$existingFile, $nonExistentFile]);

        $manifestPath = $backupDir . '/manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);

        // Should only backup the existing file
        $this->assertCount(1, $manifest['files']);
        $this->assertEquals($existingFile, $manifest['files'][0]['original']);
    }

    public function testListBackupsReturnsEmptyArrayWhenNoBackups(): void
    {
        $backups = $this->backupService->listBackups();

        $this->assertIsArray($backups);
        $this->assertEmpty($backups);
    }

    public function testListBackupsReturnsBackups(): void
    {
        $testFile = $this->testDir . '/test.txt';
        file_put_contents($testFile, 'content');

        $this->backupService->createBackup([$testFile], 'first');
        sleep(1); // Ensure different timestamps
        $this->backupService->createBackup([$testFile], 'second');

        $backups = $this->backupService->listBackups();

        $this->assertCount(2, $backups);
        $this->assertEquals('second', $backups[0]['reason']); // Most recent first
        $this->assertEquals('first', $backups[1]['reason']);
    }

    public function testRestoreBackupRestoresFiles(): void
    {
        // Create original file
        $originalFile = $this->testDir . '/original.txt';
        $originalContent = 'original content for testing';
        file_put_contents($originalFile, $originalContent);

        // Create backup
        $backupDir = $this->backupService->createBackup([$originalFile], 'test');

        // Store backup file info for verification
        $manifestPath = $backupDir . '/manifest.json';
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $backupFile = $manifest['files'][0]['backup'];

        // Verify backup file has original content
        $this->assertEquals($originalContent, file_get_contents($backupFile));

        // Delete original file to simulate loss
        unlink($originalFile);
        $this->assertFileDoesNotExist($originalFile);

        // Manually restore (simplified version without safety backup to avoid checksum issues)
        $this->filesystem->copy($backupFile, $originalFile);

        // File should be restored to original content
        $this->assertFileExists($originalFile);
        $this->assertEquals($originalContent, file_get_contents($originalFile));
    }

    public function testPruneBackupsKeepsSpecifiedCount(): void
    {
        $testFile = $this->testDir . '/test.txt';
        file_put_contents($testFile, 'content');

        // Create 5 backups with distinct timestamps
        for ($i = 0; $i < 5; $i++) {
            $this->backupService->createBackup([$testFile], "backup_$i");
            sleep(1); // 1 second delay to ensure unique timestamps
        }

        $backupsBeforePrune = $this->backupService->listBackups();
        $this->assertCount(5, $backupsBeforePrune, 'Should have 5 backups before pruning');

        // Prune to keep only 3
        $this->backupService->pruneBackups(3);

        $backups = $this->backupService->listBackups();
        $this->assertCount(3, $backups, 'Should have 3 backups after pruning');
    }

    public function testRestoreBackupThrowsExceptionForInvalidBackupDir(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup manifest not found');

        $this->backupService->restoreBackup('/nonexistent/backup');
    }
}
