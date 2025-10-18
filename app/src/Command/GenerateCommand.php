<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Generator\GeneratorOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate',
    description: 'Generate complete CRUD code from database definitions (or legacy CSV)',
    aliases: ['gen']
)]
class GenerateCommand extends Command
{
    public function __construct(
        private readonly GeneratorOrchestrator $orchestrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'entity',
                null,
                InputOption::VALUE_REQUIRED,
                'Generate code for a specific entity only (optional)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview changes without modifying any files'
            )
            ->addOption(
                'from-csv',
                null,
                InputOption::VALUE_NONE,
                'LEGACY: Use CSV files instead of database (deprecated)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entity = $input->getOption('entity');
        $dryRun = $input->getOption('dry-run');
        $fromCsv = $input->getOption('from-csv');

        // Display header
        $io->title('TURBO Code Generator');

        if ($dryRun) {
            $io->warning('DRY RUN MODE: No files will be modified');
        }

        if ($fromCsv) {
            $io->warning('LEGACY MODE: Using CSV files (deprecated - use database instead)');
        }

        // Show what will be generated
        $source = $fromCsv ? 'CSV files' : 'DATABASE';
        if ($entity) {
            $io->section(sprintf('Generating code for entity: %s (from %s)', $entity, $source));
        } else {
            $io->section(sprintf('Generating code for ALL entities from %s', $source));
        }

        // Confirmation prompt (skip if dry-run)
        if (!$dryRun && !$io->confirm('This will generate/overwrite Generated classes. Continue?', false)) {
            $io->info('Generation cancelled');
            return Command::SUCCESS;
        }

        // Execute generation
        $io->section('Starting code generation...');

        try {
            // Use database by default, CSV only if --from-csv is passed
            $result = $fromCsv
                ? $this->orchestrator->generateFromCsv($entity, $dryRun)
                : $this->orchestrator->generateFromDatabase($entity, $dryRun);

            if ($result['success']) {
                $this->displaySuccessResults($io, $result, $dryRun);
                return Command::SUCCESS;
            } else {
                $this->displayErrorResults($io, $result);
                return Command::FAILURE;
            }

        } catch (\Throwable $e) {
            $io->error([
                'Generation failed with exception:',
                $e->getMessage(),
                sprintf('File: %s:%d', $e->getFile(), $e->getLine())
            ]);
            return Command::FAILURE;
        }
    }

    private function displaySuccessResults(SymfonyStyle $io, array $result, bool $dryRun): void
    {
        if ($dryRun) {
            $io->success(sprintf(
                'DRY RUN completed: Would generate %d files for %d entities',
                count($result['generated_files']),
                $result['entity_count']
            ));
        } else {
            $io->success(sprintf(
                'Generation completed successfully: %d files generated for %d entities',
                count($result['generated_files']),
                $result['entity_count']
            ));
        }

        // Display statistics table
        $io->section('Statistics');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Entities Processed', $result['entity_count']],
                ['Files Generated', count($result['generated_files'])],
                ['Backup Created', $result['backup_dir'] ? 'Yes: ' . basename($result['backup_dir']) : 'No (dry-run)'],
                ['Errors', count($result['errors'])],
            ]
        );

        // Show next steps (if not dry-run)
        if (!$dryRun) {
            $io->section('Next Steps');
            $io->listing([
                'Run migrations: php bin/console doctrine:migrations:migrate',
                'Clear cache: php bin/console cache:clear',
                'Run tests: php bin/phpunit',
                'Review generated code in src/*/Generated/ directories',
                'Customize extension classes as needed (safe to edit)',
            ]);

            if ($result['backup_dir']) {
                $io->note(sprintf(
                    'Backup saved to: %s',
                    $result['backup_dir']
                ));
            }
        }
    }

    private function displayErrorResults(SymfonyStyle $io, array $result): void
    {
        $io->error('Generation failed');

        if (!empty($result['errors'])) {
            $io->section('Errors');
            foreach ($result['errors'] as $error) {
                $io->writeln('  • ' . $error);
            }
        }

        if ($result['backup_dir']) {
            $io->warning(sprintf(
                'Changes have been rolled back. Backup available at: %s',
                $result['backup_dir']
            ));
        }

        $io->section('Troubleshooting');
        $io->listing([
            'Check database entities (GeneratorEntity table) for validation errors',
            'Ensure all required directories exist and are writable',
            'Review error messages above',
            'Check logs in var/log/app.log',
            'If using --from-csv: check CSV files for validation errors',
        ]);
    }
}
