<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AuditRetentionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to enforce audit log retention policies
 *
 * Deletes old audit logs and anonymizes user data based on configured
 * retention policies. Should be run daily via cron.
 */
#[AsCommand(
    name: 'app:audit:retention',
    description: 'Enforce audit log retention policies and anonymize old data'
)]
class AuditRetentionCommand extends Command
{
    public function __construct(
        private readonly AuditRetentionService $retentionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be deleted without actually deleting'
            )
            ->addOption(
                'skip-anonymize',
                null,
                InputOption::VALUE_NONE,
                'Skip GDPR anonymization step'
            )
            ->setHelp(<<<'HELP'
This command enforces audit log retention policies by:
1. Deleting audit logs older than the configured retention period for each entity type
2. Anonymizing personally identifiable information (PII) in old audit logs for GDPR compliance

Configuration is defined in config/packages/audit.yaml

Examples:
  # Dry run to see what would be deleted
  php bin/console app:audit:retention --dry-run

  # Execute retention policies
  php bin/console app:audit:retention

  # Execute without anonymization
  php bin/console app:audit:retention --skip-anonymize

Cron Job Setup:
  0 3 * * 0 cd /app && php bin/console app:audit:retention --env=prod
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $skipAnonymize = $input->getOption('skip-anonymize');

        $io->title('Audit Log Retention Policy Enforcement');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
        }

        // Step 1: Enforce retention policies
        $io->section('Step 1: Enforcing Retention Policies');

        if ($dryRun) {
            $io->info('Would delete audit logs older than configured retention periods');
            $stats = $this->simulateRetentionEnforcement();
        } else {
            try {
                $stats = $this->retentionService->enforceRetentionPolicies();
                $io->success('Retention policies enforced successfully');
            } catch (\Exception $e) {
                $io->error('Failed to enforce retention policies: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Display deletion statistics
        $this->displayStatistics($io, $stats);

        // Step 2: GDPR Anonymization
        if (!$skipAnonymize && $this->retentionService->isGdprEnabled()) {
            $io->section('Step 2: GDPR Data Anonymization');

            if ($dryRun) {
                $io->info('Would anonymize user data in audit logs older than configured period');
                $anonymized = 0; // Placeholder
            } else {
                try {
                    $anonymized = $this->retentionService->anonymizeOldData();
                    $io->success(sprintf('Anonymized %d audit log records', $anonymized));
                } catch (\Exception $e) {
                    $io->error('Failed to anonymize data: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        } else {
            if ($skipAnonymize) {
                $io->info('Skipping GDPR anonymization (--skip-anonymize flag)');
            } else {
                $io->info('GDPR compliance disabled in configuration');
            }
        }

        // Summary
        $io->section('Summary');
        $totalDeleted = array_sum($stats);

        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Audit Logs Deleted', $totalDeleted],
                ['Entity Types Processed', count($stats)],
                ['Mode', $dryRun ? 'Dry Run' : 'Production'],
            ]
        );

        if (!$dryRun) {
            $io->success('Audit log retention completed successfully');
        }

        return Command::SUCCESS;
    }

    /**
     * Display deletion statistics in a table
     */
    private function displayStatistics(SymfonyStyle $io, array $stats): void
    {
        if (empty($stats)) {
            $io->info('No audit logs deleted (all within retention period)');
            return;
        }

        $tableRows = [];
        foreach ($stats as $entityClass => $count) {
            $shortName = substr($entityClass, strrpos($entityClass, '\\') + 1);
            $tableRows[] = [$shortName, $count];
        }

        $io->table(['Entity Type', 'Deleted Count'], $tableRows);
    }

    /**
     * Simulate retention enforcement for dry run mode
     */
    private function simulateRetentionEnforcement(): array
    {
        // In dry run mode, we would query counts but not delete
        // For now, return empty stats
        return [
            'App\Entity\User' => 0,
            'App\Entity\Organization' => 0,
            'App\Entity\Course' => 0,
        ];
    }
}
