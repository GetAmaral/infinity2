<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Genmax\GenmaxOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'genmax:generate',
    description: 'Generate entities and API Platform configuration from database metadata'
)]
class GenmaxGenerateCommand extends Command
{
    public function __construct(
        private readonly GenmaxOrchestrator $orchestrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'entities',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Entity names to generate (leave empty to generate all)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview what would be generated without writing files'
            )
            ->setHelp(<<<'HELP'
The <info>genmax:generate</info> command generates Symfony entities and API Platform configuration from database metadata.

<comment>Generate all entities:</comment>
  <info>php bin/console genmax:generate</info>

<comment>Generate specific entity:</comment>
  <info>php bin/console genmax:generate Contact</info>

<comment>Generate multiple entities:</comment>
  <info>php bin/console genmax:generate Contact Organization User</info>

<comment>Preview without writing files:</comment>
  <info>php bin/console genmax:generate --dry-run</info>
  <info>php bin/console genmax:generate Contact --dry-run</info>

<comment>Generated Files:</comment>
  - src/Entity/Generated/{Name}Generated.php (base class, always regenerated)
  - src/Entity/{Name}.php (extension class, created once only)
  - config/api_platform/{Name}.yaml (API configuration, always regenerated)

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entities = $input->getArgument('entities');
        $dryRun = $input->getOption('dry-run');

        // Display header
        $io->title('Genmax Code Generator');

        if ($dryRun) {
            $io->note('DRY RUN MODE - No files will be written');
        }

        if (empty($entities)) {
            $io->info('Generating all entities from database...');
            $result = $this->orchestrator->generate(null, $dryRun);
        } else {
            $io->info(sprintf('Generating %d entity(ies): %s', count($entities), implode(', ', $entities)));

            // Generate each entity
            $allResults = [];
            foreach ($entities as $entityName) {
                $io->section("Generating: {$entityName}");
                $result = $this->orchestrator->generate($entityName, $dryRun);
                $allResults[] = $result;

                if (!$result['success']) {
                    $io->error("Failed to generate {$entityName}");
                    foreach ($result['errors'] as $error) {
                        $io->writeln("  - {$error}");
                    }
                    return Command::FAILURE;
                }

                $io->success("Generated {$entityName} successfully");
            }

            // Combine results
            $result = [
                'success' => true,
                'generated_files' => array_merge(...array_column($allResults, 'generated_files')),
                'backup_dir' => $allResults[0]['backup_dir'] ?? null,
                'errors' => [],
                'entity_count' => count($entities)
            ];
        }

        // Display results
        if ($result['success']) {
            $io->success([
                sprintf('Successfully generated %d entity(ies)', $result['entity_count']),
                sprintf('Generated %d file(s)', count($result['generated_files']))
            ]);

            if (!empty($result['generated_files'])) {
                $io->section('Generated Files');
                $io->listing($result['generated_files']);
            }

            if ($result['backup_dir'] && !$dryRun) {
                $io->note("Backup created at: {$result['backup_dir']}");
            }

            if ($dryRun) {
                $io->warning('DRY RUN - No files were actually written');
            } else {
                $io->info('Next steps:');
                $io->listing([
                    'Review generated files',
                    'Run migrations: php bin/console doctrine:migrations:diff',
                    'Apply migrations: php bin/console doctrine:migrations:migrate',
                    'Clear cache: php bin/console cache:clear'
                ]);
            }

            return Command::SUCCESS;
        } else {
            $io->error('Code generation failed');

            if (!empty($result['errors'])) {
                $io->section('Errors');
                foreach ($result['errors'] as $error) {
                    $io->writeln("  - {$error}");
                }
            }

            if ($result['backup_dir']) {
                $io->note("Backup is available at: {$result['backup_dir']}");
            }

            return Command::FAILURE;
        }
    }
}
