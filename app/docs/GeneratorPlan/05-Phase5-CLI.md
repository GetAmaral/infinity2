# Phase 5: CLI & Orchestrator (Week 6)

## Overview

Phase 5 integrates all generators into a cohesive CLI command with proper orchestration, progress reporting, and error handling.

**Duration:** Week 6 (5 working days)

**Deliverables:**
- ✅ Symfony Console Command
- ✅ Generator Orchestrator Service
- ✅ Progress Bar & Logging
- ✅ Error Handling & Rollback
- ✅ Dry-Run Mode

---

## Day 1-2: Orchestrator Service

### File: `src/Service/GeneratorOrchestrator.php`

**Purpose:** Coordinate all generators in correct execution order.

**Key Features:**
- Dependency resolution (OrganizationTrait before entities)
- Progress tracking
- Backup before generation
- Transaction-like behavior (all or nothing)
- Rollback on error

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;
use App\Service\Generator\Entity\OrganizationTraitGenerator;
use App\Service\Generator\Entity\EntityGenerator;
use App\Service\Generator\ApiPlatform\ApiPlatformGenerator;
use App\Service\Generator\Repository\RepositoryGenerator;
use App\Service\Generator\Controller\ControllerGenerator;
use App\Service\Generator\Voter\VoterGenerator;
use App\Service\Generator\Form\FormGenerator;
use App\Service\Generator\Template\TemplateGenerator;
use App\Service\Generator\Navigation\NavigationGenerator;
use App\Service\Generator\Translation\TranslationGenerator;
use App\Service\Generator\Test\EntityTestGenerator;
use App\Service\Generator\Test\RepositoryTestGenerator;
use App\Service\Generator\Test\ControllerTestGenerator;
use App\Service\Generator\Test\VoterTestGenerator;
use App\Service\BackupService;
use Psr\Log\LoggerInterface;

class GeneratorOrchestrator
{
    public function __construct(
        private readonly CsvParserService $csvParser,
        private readonly CsvValidatorService $csvValidator,
        private readonly BackupService $backupService,
        private readonly OrganizationTraitGenerator $organizationTraitGenerator,
        private readonly EntityGenerator $entityGenerator,
        private readonly ApiPlatformGenerator $apiPlatformGenerator,
        private readonly RepositoryGenerator $repositoryGenerator,
        private readonly ControllerGenerator $controllerGenerator,
        private readonly VoterGenerator $voterGenerator,
        private readonly FormGenerator $formGenerator,
        private readonly TemplateGenerator $templateGenerator,
        private readonly NavigationGenerator $navigationGenerator,
        private readonly TranslationGenerator $translationGenerator,
        private readonly EntityTestGenerator $entityTestGenerator,
        private readonly RepositoryTestGenerator $repositoryTestGenerator,
        private readonly ControllerTestGenerator $controllerTestGenerator,
        private readonly VoterTestGenerator $voterTestGenerator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate all code from CSV
     *
     * @return array{success: bool, generated_files: array, backup_dir: ?string, errors: array}
     */
    public function generate(?string $entityFilter = null, bool $dryRun = false): array
    {
        $this->logger->info('Starting code generation', [
            'entity_filter' => $entityFilter,
            'dry_run' => $dryRun
        ]);

        $generatedFiles = [];
        $backupDir = null;
        $errors = [];

        try {
            // 1. Parse CSV files
            $this->logger->info('Parsing CSV files...');
            $result = $this->csvParser->parseAll();
            $entities = $result['entities'];

            $this->logger->info('Parsed entities', ['count' => count($entities)]);

            // 2. Validate CSV data
            $this->logger->info('Validating CSV data...');
            $validation = $this->csvValidator->validateAll($entities, $result['properties']);

            if (!$validation['valid']) {
                throw new \RuntimeException(
                    'CSV validation failed: ' . implode(', ', $validation['errors'])
                );
            }

            $this->logger->info('CSV validation passed');

            // 3. Filter entities if specified
            if ($entityFilter) {
                $entities = array_filter(
                    $entities,
                    fn($e) => $e->entityName === $entityFilter
                );
                $this->logger->info('Filtered to single entity', ['entity' => $entityFilter]);
            }

            if (empty($entities)) {
                throw new \RuntimeException('No entities to generate');
            }

            // 4. Create backup (unless dry run)
            if (!$dryRun) {
                $this->logger->info('Creating backup...');
                $filesToBackup = $this->collectFilesToBackup($entities);
                $backupDir = $this->backupService->createBackup($filesToBackup, 'generation');
                $this->logger->info('Backup created', ['dir' => $backupDir]);
            } else {
                $this->logger->info('Dry run mode: skipping backup');
            }

            // 5. Generate OrganizationTrait (if needed)
            $hasOrganizationEntities = !empty(array_filter($entities, fn($e) => $e->hasOrganization));
            if ($hasOrganizationEntities) {
                $this->logger->info('Checking OrganizationTrait...');
                if (!$dryRun) {
                    $traitGenerated = $this->organizationTraitGenerator->generate();
                    if ($traitGenerated) {
                        $this->logger->info('OrganizationTrait generated');
                    } else {
                        $this->logger->info('OrganizationTrait already exists');
                    }
                }
            }

            // 6. Generate code for each entity
            $totalSteps = count($entities) * 11; // 11 generators per entity
            $currentStep = 0;

            foreach ($entities as $entity) {
                $this->logger->info("Generating code for {$entity->entityName}...");

                if (!$dryRun) {
                    // Entity
                    $files = $this->entityGenerator->generate($entity);
                    $generatedFiles = array_merge($generatedFiles, $files);
                    $currentStep++;

                    // API Platform
                    if ($entity->apiEnabled) {
                        $file = $this->apiPlatformGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                    }
                    $currentStep++;

                    // Repository
                    $files = $this->repositoryGenerator->generate($entity);
                    $generatedFiles = array_merge($generatedFiles, $files);
                    $currentStep++;

                    // Controller
                    $files = $this->controllerGenerator->generate($entity);
                    $generatedFiles = array_merge($generatedFiles, $files);
                    $currentStep++;

                    // Voter
                    if ($entity->voterEnabled) {
                        $files = $this->voterGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                    }
                    $currentStep++;

                    // Form
                    $files = $this->formGenerator->generate($entity);
                    $generatedFiles = array_merge($generatedFiles, $files);
                    $currentStep++;

                    // Templates
                    $files = $this->templateGenerator->generate($entity);
                    $generatedFiles = array_merge($generatedFiles, $files);
                    $currentStep++;

                    // Tests
                    if ($entity->testEnabled) {
                        $generatedFiles[] = $this->entityTestGenerator->generate($entity);
                        $generatedFiles[] = $this->repositoryTestGenerator->generate($entity);
                        $generatedFiles[] = $this->controllerTestGenerator->generate($entity);
                        if ($entity->voterEnabled) {
                            $generatedFiles[] = $this->voterTestGenerator->generate($entity);
                        }
                    }
                    $currentStep += 4;

                    $this->logger->info("Completed {$entity->entityName}", [
                        'progress' => round(($currentStep / $totalSteps) * 100, 1) . '%'
                    ]);
                } else {
                    // Dry run: just log
                    $this->logger->info("[DRY RUN] Would generate {$entity->entityName}");
                    $currentStep += 11;
                }
            }

            // 7. Generate navigation
            $this->logger->info('Generating navigation...');
            if (!$dryRun) {
                $this->navigationGenerator->generate($entities);
            }

            // 8. Generate translations
            $this->logger->info('Generating translations...');
            if (!$dryRun) {
                $this->translationGenerator->generate($entities);
            }

            $this->logger->info('Code generation completed successfully', [
                'files_generated' => count($generatedFiles)
            ]);

            return [
                'success' => true,
                'generated_files' => $generatedFiles,
                'backup_dir' => $backupDir,
                'errors' => []
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Code generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errors[] = $e->getMessage();

            // Rollback if backup was created
            if ($backupDir && !$dryRun) {
                $this->logger->warning('Rolling back changes...');
                try {
                    $this->backupService->restoreBackup($backupDir);
                    $this->logger->info('Rollback completed');
                } catch (\Throwable $rollbackError) {
                    $this->logger->critical('Rollback failed', [
                        'error' => $rollbackError->getMessage()
                    ]);
                    $errors[] = 'ROLLBACK FAILED: ' . $rollbackError->getMessage();
                }
            }

            return [
                'success' => false,
                'generated_files' => $generatedFiles,
                'backup_dir' => $backupDir,
                'errors' => $errors
            ];
        }
    }

    /**
     * Collect all files that will be modified (for backup)
     */
    private function collectFilesToBackup(array $entities): array
    {
        $files = [];

        // OrganizationTrait
        $files[] = $this->projectDir . '/src/Entity/Trait/OrganizationTrait.php';

        foreach ($entities as $entity) {
            // Entity files
            $files[] = $this->projectDir . '/src/Entity/Generated/' . $entity->entityName . 'Generated.php';
            $files[] = $this->projectDir . '/src/Entity/' . $entity->entityName . '.php';

            // Repository files
            $files[] = $this->projectDir . '/src/Repository/Generated/' . $entity->entityName . 'RepositoryGenerated.php';
            $files[] = $this->projectDir . '/src/Repository/' . $entity->entityName . 'Repository.php';

            // Controller files
            $files[] = $this->projectDir . '/src/Controller/Generated/' . $entity->entityName . 'ControllerGenerated.php';
            $files[] = $this->projectDir . '/src/Controller/' . $entity->entityName . 'Controller.php';

            // Voter files
            if ($entity->voterEnabled) {
                $files[] = $this->projectDir . '/src/Security/Voter/Generated/' . $entity->entityName . 'VoterGenerated.php';
                $files[] = $this->projectDir . '/src/Security/Voter/' . $entity->entityName . 'Voter.php';
            }

            // Form files
            $files[] = $this->projectDir . '/src/Form/Generated/' . $entity->entityName . 'TypeGenerated.php';
            $files[] = $this->projectDir . '/src/Form/' . $entity->entityName . 'Type.php';

            // Templates
            $files[] = $this->projectDir . '/templates/' . $entity->getLowercaseName() . '/index.html.twig';
            $files[] = $this->projectDir . '/templates/' . $entity->getLowercaseName() . '/form.html.twig';
            $files[] = $this->projectDir . '/templates/' . $entity->getLowercaseName() . '/show.html.twig';

            // API Platform config
            if ($entity->apiEnabled) {
                $files[] = $this->projectDir . '/config/api_platform/' . $entity->entityName . '.yaml';
            }

            // Tests
            if ($entity->testEnabled) {
                $files[] = $this->projectDir . '/tests/Entity/' . $entity->entityName . 'Test.php';
                $files[] = $this->projectDir . '/tests/Repository/' . $entity->entityName . 'RepositoryTest.php';
                $files[] = $this->projectDir . '/tests/Controller/' . $entity->entityName . 'ControllerTest.php';
                if ($entity->voterEnabled) {
                    $files[] = $this->projectDir . '/tests/Security/Voter/' . $entity->entityName . 'VoterTest.php';
                }
            }
        }

        // Navigation and translations
        $files[] = $this->projectDir . '/templates/base.html.twig';
        $files[] = $this->projectDir . '/translations/messages.en.yaml';

        return $files;
    }
}
```

---

## Day 3-4: Console Command

### File: `src/Command/GenerateFromCsvCommand.php`

**Purpose:** User-friendly CLI interface for the generator.

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GeneratorOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-from-csv',
    description: 'Generate entities, controllers, forms, and tests from CSV files'
)]
class GenerateFromCsvCommand extends Command
{
    public function __construct(
        private readonly GeneratorOrchestrator $orchestrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('entity', 'e', InputOption::VALUE_OPTIONAL, 'Generate only specific entity')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview generation without making changes')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command generates complete CRUD functionality from CSV files.

<comment>Generate all entities:</comment>
  <info>php %command.full_name%</info>

<comment>Generate specific entity:</comment>
  <info>php %command.full_name% --entity=Contact</info>

<comment>Preview changes without generating:</comment>
  <info>php %command.full_name% --dry-run</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entity = $input->getOption('entity');
        $dryRun = $input->getOption('dry-run');

        $io->title('Luminai Code Generator');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No files will be created or modified');
        }

        if ($entity) {
            $io->info("Generating code for entity: {$entity}");
        } else {
            $io->info('Generating code for ALL entities from CSV');
        }

        // Confirm before proceeding (unless dry run)
        if (!$dryRun && !$io->confirm('This will regenerate code. Continue?', false)) {
            $io->note('Operation cancelled');
            return Command::SUCCESS;
        }

        // Execute generation
        $result = $this->orchestrator->generate($entity, $dryRun);

        // Display results
        if ($result['success']) {
            $io->success('Code generation completed successfully!');

            $io->section('Statistics');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Files Generated', count($result['generated_files'])],
                    ['Backup Created', $result['backup_dir'] ?? 'N/A (dry run)'],
                ]
            );

            if (!$dryRun) {
                $io->section('Next Steps');
                $io->listing([
                    'Run migrations: php bin/console doctrine:migrations:migrate',
                    'Clear cache: php bin/console cache:clear',
                    'Run tests: php bin/phpunit',
                ]);
            }

            return Command::SUCCESS;
        } else {
            $io->error('Code generation failed');

            if (!empty($result['errors'])) {
                $io->section('Errors');
                $io->listing($result['errors']);
            }

            if ($result['backup_dir']) {
                $io->warning('Changes have been rolled back to backup: ' . $result['backup_dir']);
            }

            return Command::FAILURE;
        }
    }
}
```

---

## Day 5: Testing & Documentation

### Integration Test

```php
<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateFromCsvCommandTest extends KernelTestCase
{
    public function testExecuteDryRun(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:generate-from-csv');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('DRY RUN MODE', $output);
        $this->assertStringContainsString('completed successfully', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
```

---

## Phase 5 Deliverables Checklist

- [ ] GeneratorOrchestrator service implemented
- [ ] Console command implemented
- [ ] Progress reporting working
- [ ] Backup and rollback working
- [ ] Dry-run mode working
- [ ] Error handling comprehensive
- [ ] All tests pass
- [ ] Documentation complete

---

## Next Phase

**Phase 6: CSV Migration** (Weeks 7-8)
- Migrate from current CSV format to new structure
- Migrate existing entities to generated code
