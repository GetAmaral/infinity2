<?php

declare(strict_types=1);

namespace App\Service\Generator;

use App\Service\BackupService;
use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use App\Repository\Generator\GeneratorEntityRepository;
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
use Psr\Log\LoggerInterface;

/**
 * Generator Orchestrator
 *
 * Coordinates all generators in the correct execution order with proper
 * error handling, backup, and rollback capabilities.
 */
class GeneratorOrchestrator
{
    // Feature flags - toggle generators on/off
    private const ENTITY_ACTIVE = true;
    private const API_ACTIVE = false;
    private const REPOSITORY_ACTIVE = false;
    private const CONTROLLER_ACTIVE = false;
    private const VOTER_ACTIVE = false;
    private const FORM_ACTIVE = false;
    private const TEMPLATE_ACTIVE = false;
    private const NAVIGATION_ACTIVE = false;
    private const TRANSLATION_ACTIVE = false;
    private const TESTS_ACTIVE = false;

    public function __construct(
        private readonly string $projectDir,
        private readonly CsvParserService $csvParser,
        private readonly CsvValidatorService $csvValidator,
        private readonly DatabaseDefinitionService $databaseDefinitionService,
        private readonly GeneratorEntityRepository $generatorEntityRepository,
        private readonly BackupService $backupService,
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
     * Generate all code from DATABASE (default and recommended)
     *
     * @return array{success: bool, generated_files: array<string>, backup_dir: ?string, errors: array<string>, entity_count: int}
     */
    public function generateFromDatabase(?string $entityFilter = null, bool $dryRun = false): array
    {
        $this->logger->info('Starting code generation from DATABASE', [
            'entity_filter' => $entityFilter,
            'dry_run' => $dryRun
        ]);

        $generatedFiles = [];
        $backupDir = null;
        $errors = [];

        try {
            // 1. Load entities from DATABASE
            $this->logger->info('Loading entities from database...');

            $qb = $this->generatorEntityRepository->createQueryBuilder('e')
                ->leftJoin('e.properties', 'p')
                ->addSelect('p')
                ->orderBy('e.menuGroup', 'ASC')
                ->addOrderBy('e.menuOrder', 'ASC');

            if ($entityFilter) {
                $qb->where('e.entityName = :name')
                   ->setParameter('name', $entityFilter);
            }

            $dbEntities = $qb->getQuery()->getResult();

            if (empty($dbEntities)) {
                throw new \RuntimeException('No entities found in database');
            }

            // Convert database entities to DTOs
            $entities = array_map(
                fn($entity) => $this->databaseDefinitionService->buildEntityDto($entity),
                $dbEntities
            );

            $this->logger->info('Loaded entities from database', ['count' => count($entities)]);

            // 2. No CSV validation needed - database constraints ensure data integrity
            $this->logger->info('Database entities loaded (validation enforced by DB constraints)');

            // 3. Create backup (unless dry run)
            if (!$dryRun) {
                $this->logger->info('Creating backup...');
                $filesToBackup = $this->collectFilesToBackup($entities);
                $backupDir = $this->backupService->createBackup($filesToBackup, 'generation');
                $this->logger->info('Backup created', ['dir' => $backupDir]);
            } else {
                $this->logger->info('Dry run mode: skipping backup');
            }

            // 4. Generate code for each entity (same logic as CSV)
            $activeGenerators = 0;
            $activeGenerators += self::ENTITY_ACTIVE ? 1 : 0;
            $activeGenerators += self::API_ACTIVE ? 1 : 0;
            $activeGenerators += self::REPOSITORY_ACTIVE ? 1 : 0;
            $activeGenerators += self::CONTROLLER_ACTIVE ? 1 : 0;
            $activeGenerators += self::VOTER_ACTIVE ? 1 : 0;
            $activeGenerators += self::FORM_ACTIVE ? 1 : 0;
            $activeGenerators += self::TEMPLATE_ACTIVE ? 1 : 0;
            $activeGenerators += self::TESTS_ACTIVE ? 4 : 0; // 4 test generators

            $totalSteps = count($entities) * $activeGenerators;
            $currentStep = 0;

            foreach ($entities as $entity) {
                $this->logger->info("Generating code for {$entity->entityName}...");

                if (!$dryRun) {
                    // Entity
                    if (self::ENTITY_ACTIVE) {
                        $files = $this->entityGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // API Platform
                    if (self::API_ACTIVE && $entity->apiEnabled) {
                        $file = $this->apiPlatformGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        $currentStep++;
                    }

                    // Repository
                    if (self::REPOSITORY_ACTIVE) {
                        $files = $this->repositoryGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Controller
                    if (self::CONTROLLER_ACTIVE) {
                        $files = $this->controllerGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Voter
                    if (self::VOTER_ACTIVE && $entity->voterEnabled) {
                        $files = $this->voterGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Form
                    if (self::FORM_ACTIVE) {
                        $files = $this->formGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Templates
                    if (self::TEMPLATE_ACTIVE) {
                        $files = $this->templateGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Tests
                    if (self::TESTS_ACTIVE && $entity->testEnabled) {
                        $file = $this->entityTestGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        $file = $this->repositoryTestGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        $file = $this->controllerTestGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        if ($entity->voterEnabled) {
                            $file = $this->voterTestGenerator->generate($entity);
                            if ($file) {
                                $generatedFiles[] = $file;
                            }
                        }
                        $currentStep += 4;
                    }

                    $progress = round(($currentStep / $totalSteps) * 100, 1);
                    $this->logger->info("Completed {$entity->entityName}", [
                        'progress' => $progress . '%'
                    ]);
                } else {
                    // Dry run: just log
                    $this->logger->info("[DRY RUN] Would generate {$entity->entityName}");
                    $currentStep += $activeGenerators;
                }
            }

            // 5. Generate navigation
            if (self::NAVIGATION_ACTIVE) {
                $this->logger->info('Generating navigation...');
                if (!$dryRun) {
                    $this->navigationGenerator->generate($entities);
                }
            }

            // 6. Generate translations
            if (self::TRANSLATION_ACTIVE) {
                $this->logger->info('Generating translations...');
                if (!$dryRun) {
                    $this->translationGenerator->generate($entities);
                }
            }

            $this->logger->info('Code generation completed successfully', [
                'files_generated' => count($generatedFiles),
                'entities' => count($entities)
            ]);

            return [
                'success' => true,
                'generated_files' => array_filter($generatedFiles), // Remove empty strings
                'backup_dir' => $backupDir,
                'errors' => [],
                'entity_count' => count($entities)
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Code generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
                'errors' => $errors,
                'entity_count' => 0
            ];
        }
    }

    /**
     * LEGACY: Generate all code from CSV files
     *
     * @deprecated Use generateFromDatabase() instead
     * @return array{success: bool, generated_files: array<string>, backup_dir: ?string, errors: array<string>, entity_count: int}
     */
    public function generateFromCsv(?string $entityFilter = null, bool $dryRun = false): array
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
            $rawEntities = $result['entities'];

            // Convert to DTOs
            $entities = array_map(
                fn($entity) => EntityDefinitionDto::fromArray($entity),
                $rawEntities
            );

            $this->logger->info('Parsed entities', ['count' => count($entities)]);

            // 2. Validate CSV data
            $this->logger->info('Validating CSV data...');
            $validation = $this->csvValidator->validateAll($rawEntities, $result['properties']);

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

            // 5. Generate code for each entity
            $activeGenerators = 0;
            $activeGenerators += self::ENTITY_ACTIVE ? 1 : 0;
            $activeGenerators += self::API_ACTIVE ? 1 : 0;
            $activeGenerators += self::REPOSITORY_ACTIVE ? 1 : 0;
            $activeGenerators += self::CONTROLLER_ACTIVE ? 1 : 0;
            $activeGenerators += self::VOTER_ACTIVE ? 1 : 0;
            $activeGenerators += self::FORM_ACTIVE ? 1 : 0;
            $activeGenerators += self::TEMPLATE_ACTIVE ? 1 : 0;
            $activeGenerators += self::TESTS_ACTIVE ? 4 : 0; // 4 test generators

            $totalSteps = count($entities) * $activeGenerators;
            $currentStep = 0;

            foreach ($entities as $entity) {
                $this->logger->info("Generating code for {$entity->entityName}...");

                if (!$dryRun) {
                    // Entity
                    if (self::ENTITY_ACTIVE) {
                        $files = $this->entityGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // API Platform
                    if (self::API_ACTIVE && $entity->apiEnabled) {
                        $file = $this->apiPlatformGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        $currentStep++;
                    }

                    // Repository
                    if (self::REPOSITORY_ACTIVE) {
                        $files = $this->repositoryGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Controller
                    if (self::CONTROLLER_ACTIVE) {
                        $files = $this->controllerGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Voter
                    if (self::VOTER_ACTIVE && $entity->voterEnabled) {
                        $files = $this->voterGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Form
                    if (self::FORM_ACTIVE) {
                        $files = $this->formGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Templates
                    if (self::TEMPLATE_ACTIVE) {
                        $files = $this->templateGenerator->generate($entity);
                        $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Tests
                    if (self::TESTS_ACTIVE && $entity->testEnabled) {
                        $file = $this->entityTestGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        $file = $this->repositoryTestGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        $file = $this->controllerTestGenerator->generate($entity);
                        if ($file) {
                            $generatedFiles[] = $file;
                        }
                        if ($entity->voterEnabled) {
                            $file = $this->voterTestGenerator->generate($entity);
                            if ($file) {
                                $generatedFiles[] = $file;
                            }
                        }
                        $currentStep += 4;
                    }

                    $progress = round(($currentStep / $totalSteps) * 100, 1);
                    $this->logger->info("Completed {$entity->entityName}", [
                        'progress' => $progress . '%'
                    ]);
                } else {
                    // Dry run: just log
                    $this->logger->info("[DRY RUN] Would generate {$entity->entityName}");
                    $currentStep += $activeGenerators;
                }
            }

            // 6. Generate navigation
            if (self::NAVIGATION_ACTIVE) {
                $this->logger->info('Generating navigation...');
                if (!$dryRun) {
                    $this->navigationGenerator->generate($entities);
                }
            }

            // 7. Generate translations
            if (self::TRANSLATION_ACTIVE) {
                $this->logger->info('Generating translations...');
                if (!$dryRun) {
                    $this->translationGenerator->generate($entities);
                }
            }

            $this->logger->info('Code generation completed successfully', [
                'files_generated' => count($generatedFiles),
                'entities' => count($entities)
            ]);

            return [
                'success' => true,
                'generated_files' => array_filter($generatedFiles), // Remove empty strings
                'backup_dir' => $backupDir,
                'errors' => [],
                'entity_count' => count($entities)
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Code generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
                'errors' => $errors,
                'entity_count' => 0
            ];
        }
    }

    /**
     * Collect all files that will be modified (for backup)
     *
     * @param array<EntityDefinitionDto> $entities
     * @return array<string>
     */
    private function collectFilesToBackup(array $entities): array
    {
        $files = [];

        // OrganizationTrait (exists as permanent file)
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
            $files[] = $this->projectDir . '/templates/' . $entity->getLowercaseName() . '/_turbo_stream_create.html.twig';
            $files[] = $this->projectDir . '/templates/' . $entity->getLowercaseName() . '/_turbo_stream_update.html.twig';
            $files[] = $this->projectDir . '/templates/' . $entity->getLowercaseName() . '/_turbo_stream_delete.html.twig';

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
