<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AuditLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Command to verify audit log integrity
 *
 * Checks all audit logs for tampering by verifying their checksums.
 * Should be run daily via cron to detect any unauthorized modifications.
 */
#[AsCommand(
    name: 'app:audit:verify',
    description: 'Verify audit log integrity and detect tampering'
)]
class AuditVerifyCommand extends Command
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        #[Autowire(env: 'AUDIT_INTEGRITY_SALT')]
        private readonly string $integritySalt
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit number of audit logs to check (default: all)',
                null
            )
            ->addOption(
                'fail-fast',
                null,
                InputOption::VALUE_NONE,
                'Stop verification on first tampered record'
            )
            ->addOption(
                'verbose-failures',
                'v',
                InputOption::VALUE_NONE,
                'Show details of tampered records'
            )
            ->setHelp(<<<'HELP'
This command verifies the integrity of audit logs by checking their checksums.
Any records that fail verification have been tampered with or corrupted.

The command:
1. Loads all audit logs (or limited set with --limit)
2. Verifies the checksum of each record
3. Reports any tampered or missing checksums
4. Returns exit code 0 if all verified, 1 if tampering detected

Examples:
  # Verify all audit logs
  php bin/console app:audit:verify

  # Verify only recent 1000 logs
  php bin/console app:audit:verify --limit=1000

  # Stop on first tampered record
  php bin/console app:audit:verify --fail-fast

  # Show details of tampered records
  php bin/console app:audit:verify --verbose-failures

Cron Job Setup:
  0 4 * * * cd /app && php bin/console app:audit:verify --env=prod || mail -s "Audit Tampering Detected" admin@example.com

Security Alert:
  If tampering is detected, investigate immediately. This may indicate:
  - Unauthorized database access
  - Malicious insider activity
  - Database corruption
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = $input->getOption('limit');
        $failFast = $input->getOption('fail-fast');
        $verboseFailures = $input->getOption('verbose-failures');

        $io->title('Audit Log Integrity Verification');

        // Load audit logs
        $io->section('Loading Audit Logs');

        if ($limit !== null) {
            $logs = $this->auditLogRepository->findRecent((int)$limit);
            $io->info(sprintf('Loaded %d most recent audit logs', count($logs)));
        } else {
            $logs = $this->auditLogRepository->findAll();
            $io->info(sprintf('Loaded all %d audit logs', count($logs)));
        }

        if (empty($logs)) {
            $io->success('No audit logs to verify');
            return Command::SUCCESS;
        }

        // Verify integrity
        $io->section('Verifying Integrity');

        $progressBar = $io->createProgressBar(count($logs));
        $progressBar->setFormat('very_verbose');

        $verified = 0;
        $tampered = [];
        $missingChecksum = [];

        foreach ($logs as $log) {
            $progressBar->advance();

            // Check if checksum exists
            if ($log->getChecksum() === null) {
                $missingChecksum[] = $log;
                continue;
            }

            // Verify integrity
            if (!$log->verifyIntegrity($this->integritySalt)) {
                $tampered[] = $log;

                if ($failFast) {
                    $progressBar->finish();
                    $io->newLine(2);
                    $io->error('Tampering detected! Stopping verification (--fail-fast)');
                    $this->displayTamperedRecord($io, $log, $verboseFailures);
                    return Command::FAILURE;
                }

                continue;
            }

            $verified++;
        }

        $progressBar->finish();
        $io->newLine(2);

        // Display results
        $io->section('Verification Results');

        $io->table(
            ['Status', 'Count'],
            [
                ['Verified', $verified],
                ['Tampered', count($tampered)],
                ['Missing Checksum', count($missingChecksum)],
                ['Total Checked', count($logs)],
            ]
        );

        // Handle tampered records
        if (!empty($tampered)) {
            $io->error(sprintf('⚠️  TAMPERING DETECTED: %d audit log(s) failed verification', count($tampered)));

            if ($verboseFailures) {
                $io->section('Tampered Records Details');
                foreach ($tampered as $log) {
                    $this->displayTamperedRecord($io, $log, true);
                }
            } else {
                $io->info('Use --verbose-failures to see tampered record details');

                // Show IDs only
                $tamperedIds = array_map(fn($log) => $log->getId()->toString(), array_slice($tampered, 0, 10));
                $io->listing($tamperedIds);

                if (count($tampered) > 10) {
                    $io->note(sprintf('... and %d more', count($tampered) - 10));
                }
            }

            return Command::FAILURE;
        }

        // Handle missing checksums
        if (!empty($missingChecksum)) {
            $io->warning(sprintf('%d audit log(s) have no checksum (created before checksums were implemented)', count($missingChecksum)));
        }

        // Success
        if ($verified === count($logs)) {
            $io->success('✅ All audit logs verified successfully - No tampering detected');
            return Command::SUCCESS;
        } else {
            $io->success(sprintf('✅ %d/%d audit logs verified successfully', $verified, count($logs)));
            return Command::SUCCESS;
        }
    }

    /**
     * Display details of a tampered record
     */
    private function displayTamperedRecord(SymfonyStyle $io, $log, bool $verbose): void
    {
        $details = [
            'ID' => $log->getId()->toString(),
            'Action' => $log->getAction(),
            'Entity' => $log->getEntityClass(),
            'Entity ID' => $log->getEntityId()->toString(),
            'Created At' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            'User' => $log->getUser()?->getEmail() ?? 'System',
            'Stored Checksum' => substr($log->getChecksum() ?? '', 0, 16) . '...',
        ];

        if ($verbose) {
            $io->table(array_keys($details), [array_values($details)]);
        } else {
            $io->writeln(sprintf(
                '  - %s (%s on %s at %s)',
                $log->getId()->toString(),
                $log->getAction(),
                substr($log->getEntityClass(), strrpos($log->getEntityClass(), '\\') + 1),
                $log->getCreatedAt()->format('Y-m-d H:i:s')
            ));
        }
    }
}
