<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\LogCompressionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:logs:cleanup',
    description: 'Compress old log files and delete logs older than retention policy'
)]
final class LogsCleanupCommand extends Command
{
    public function __construct(
        private readonly LogCompressionService $compressionService,
        #[Autowire('%kernel.logs_dir%')]
        private readonly string $logsDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'compress-after',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Compress logs older than N days',
                '7'
            )
            ->addOption(
                'delete-after',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Delete compressed logs older than N days',
                '90'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be done without actually doing it'
            )
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command manages log file rotation and cleanup:

  <info>php %command.full_name%</info>

By default, it:
  - Compresses log files older than 7 days
  - Deletes compressed logs older than 90 days

You can customize these values:
  <info>php %command.full_name% --compress-after=14 --delete-after=180</info>

To preview actions without executing them:
  <info>php %command.full_name% --dry-run</info>

This command is designed to run via cron job:
  <comment>0 2 * * * cd /app && php bin/console app:logs:cleanup --env=prod</comment>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $compressAfterDays = (int) $input->getOption('compress-after');
        $deleteAfterDays = (int) $input->getOption('delete-after');
        $dryRun = $input->getOption('dry-run');

        $io->title('Log Cleanup & Compression');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
        }

        $io->section('Configuration');
        $io->table(
            ['Setting', 'Value'],
            [
                ['Log Directory', $this->logsDir],
                ['Compress logs older than', $compressAfterDays . ' days'],
                ['Delete compressed logs older than', $deleteAfterDays . ' days'],
                ['Mode', $dryRun ? 'Dry Run' : 'Active'],
            ]
        );

        // Step 1: Compress old log files
        $io->section('Step 1: Compressing Old Logs');

        if ($dryRun) {
            $io->text('Would compress log files older than ' . $compressAfterDays . ' days');
            $compressionStats = $this->previewCompression($compressAfterDays);
        } else {
            $compressionStats = $this->compressionService->compressOldLogs(
                $this->logsDir,
                $compressAfterDays
            );
        }

        if ($compressionStats['compressed'] > 0) {
            $io->success(sprintf(
                'Compressed %d file(s), saved %s',
                $compressionStats['compressed'],
                $this->compressionService->formatBytes($compressionStats['space_saved'])
            ));
        } else {
            $io->info('No files to compress');
        }

        if (!empty($compressionStats['errors'])) {
            $io->warning('Compression errors occurred:');
            foreach ($compressionStats['errors'] as $error) {
                $io->text('  - ' . $error);
            }
        }

        // Step 2: Delete old compressed logs
        $io->section('Step 2: Deleting Old Compressed Logs');

        if ($dryRun) {
            $io->text('Would delete compressed logs older than ' . $deleteAfterDays . ' days');
            $deletionStats = $this->previewDeletion($deleteAfterDays);
        } else {
            $deletionStats = $this->compressionService->deleteOldCompressedLogs(
                $this->logsDir,
                $deleteAfterDays
            );
        }

        if ($deletionStats['deleted'] > 0) {
            $io->success(sprintf(
                'Deleted %d compressed file(s), freed %s',
                $deletionStats['deleted'],
                $this->compressionService->formatBytes($deletionStats['space_freed'])
            ));
        } else {
            $io->info('No compressed files to delete');
        }

        if (!empty($deletionStats['errors'])) {
            $io->warning('Deletion errors occurred:');
            foreach ($deletionStats['errors'] as $error) {
                $io->text('  - ' . $error);
            }
        }

        // Summary
        $io->section('Summary');

        $totalSpaceSaved = $compressionStats['space_saved'] + $deletionStats['space_freed'];

        $io->table(
            ['Action', 'Count', 'Space Impact'],
            [
                [
                    'Files Compressed',
                    $compressionStats['compressed'],
                    $this->compressionService->formatBytes($compressionStats['space_saved']) . ' saved'
                ],
                [
                    'Files Deleted',
                    $deletionStats['deleted'],
                    $this->compressionService->formatBytes($deletionStats['space_freed']) . ' freed'
                ],
                [
                    '<info>Total</info>',
                    '<info>' . ($compressionStats['compressed'] + $deletionStats['deleted']) . '</info>',
                    '<info>' . $this->compressionService->formatBytes($totalSpaceSaved) . '</info>'
                ],
            ]
        );

        $hasErrors = !empty($compressionStats['errors']) || !empty($deletionStats['errors']);

        if ($dryRun) {
            $io->note('This was a dry run. Run without --dry-run to execute changes.');
            return Command::SUCCESS;
        }

        if ($hasErrors) {
            $io->error('Log cleanup completed with errors');
            return Command::FAILURE;
        }

        $io->success('Log cleanup completed successfully');

        return Command::SUCCESS;
    }

    /**
     * Preview what files would be compressed (dry-run).
     */
    private function previewCompression(int $olderThanDays): array
    {
        $stats = [
            'compressed' => 0,
            'space_saved' => 0,
            'errors' => [],
        ];

        if (!is_dir($this->logsDir)) {
            $stats['errors'][] = "Directory does not exist: {$this->logsDir}";
            return $stats;
        }

        $cutoffTimestamp = time() - ($olderThanDays * 24 * 60 * 60);
        $logFiles = glob($this->logsDir . '/*.log');

        if ($logFiles === false) {
            return $stats;
        }

        foreach ($logFiles as $logFile) {
            $fileModTime = filemtime($logFile);
            if ($fileModTime !== false && $fileModTime < $cutoffTimestamp) {
                $basename = basename($logFile);
                if (!str_ends_with($basename, '.gz') && !$this->isCurrentLog($basename)) {
                    $stats['compressed']++;
                    $fileSize = filesize($logFile);
                    // Estimate 70% compression ratio
                    $stats['space_saved'] += (int) ($fileSize * 0.7);
                }
            }
        }

        return $stats;
    }

    /**
     * Preview what compressed files would be deleted (dry-run).
     */
    private function previewDeletion(int $retentionDays): array
    {
        $stats = [
            'deleted' => 0,
            'space_freed' => 0,
            'errors' => [],
        ];

        if (!is_dir($this->logsDir)) {
            $stats['errors'][] = "Directory does not exist: {$this->logsDir}";
            return $stats;
        }

        $cutoffTimestamp = time() - ($retentionDays * 24 * 60 * 60);
        $compressedFiles = glob($this->logsDir . '/*.log.gz');

        if ($compressedFiles === false) {
            return $stats;
        }

        foreach ($compressedFiles as $compressedFile) {
            $fileModTime = filemtime($compressedFile);
            if ($fileModTime !== false && $fileModTime < $cutoffTimestamp) {
                $stats['deleted']++;
                $stats['space_freed'] += filesize($compressedFile) ?: 0;
            }
        }

        return $stats;
    }

    /**
     * Check if this is a current log file that should not be compressed.
     */
    private function isCurrentLog(string $filename): bool
    {
        $today = date('Y-m-d');

        if (str_contains($filename, $today)) {
            return true;
        }

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
}
