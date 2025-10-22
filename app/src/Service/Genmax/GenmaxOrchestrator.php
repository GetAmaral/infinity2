<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Repository\Generator\GeneratorEntityRepository;
use App\Service\BackupService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Genmax Orchestrator
 *
 * Next-generation code generator that uses GeneratorEntity and GeneratorProperty
 * entities directly from the database as the source of truth.
 *
 * Key differences from old Generator:
 * - NO CSV files
 * - Direct use of Doctrine entities
 * - Simplified architecture
 */
class GenmaxOrchestrator
{
    // ====================================
    // FEATURE FLAGS - Toggle generators on/off
    // ====================================

    private const ENTITY_ACTIVE = true;           // ✅ Phase 1 - ACTIVE
    private const API_ACTIVE = true;              // ✅ Phase 2 - ACTIVE
    private const DTO_ACTIVE = true;              // ✅ Phase 2.5 - ACTIVE
    private const STATE_PROCESSOR_ACTIVE = true;  // ✅ Phase 2.5 - ACTIVE
    private const REPOSITORY_ACTIVE = false;      // ⏸️ Phase 2 - Future
    private const CONTROLLER_ACTIVE = false;      // ⏸️ Phase 3 - Future
    private const VOTER_ACTIVE = false;           // ⏸️ Phase 3 - Future
    private const FORM_ACTIVE = false;            // ⏸️ Phase 3 - Future
    private const TEMPLATE_ACTIVE = false;        // ⏸️ Phase 4 - Future
    private const NAVIGATION_ACTIVE = false;      // ⏸️ Phase 4 - Future
    private const TRANSLATION_ACTIVE = false;     // ⏸️ Phase 4 - Future
    private const TESTS_ACTIVE = false;           // ⏸️ Phase 5 - Future

    public function __construct(
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        private readonly GeneratorEntityRepository $generatorEntityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly BackupService $backupService,
        private readonly EntityGenerator $entityGenerator,
        private readonly ApiGenerator $apiGenerator,
        private readonly DtoGenerator $dtoGenerator,
        private readonly StateProcessorGenerator $stateProcessorGenerator,
        // Future generators will be injected here:
        // private readonly RepositoryGenerator $repositoryGenerator,
        // private readonly ControllerGenerator $controllerGenerator,
        // private readonly VoterGenerator $voterGenerator,
        // private readonly FormGenerator $formGenerator,
        // private readonly TemplateGenerator $templateGenerator,
        // private readonly NavigationGenerator $navigationGenerator,
        // private readonly TranslationGenerator $translationGenerator,
        // private readonly TestGenerator $testGenerator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate code from database entities
     *
     * @param string|null $entityFilter Filter to specific entity name
     * @param bool $dryRun Preview mode without actual file writes
     * @return array{success: bool, generated_files: array<string>, backup_dir: ?string, errors: array<string>, entity_count: int}
     */
    public function generate(?string $entityFilter = null, bool $dryRun = false): array
    {
        $this->logger->info('[GENMAX] Starting code generation', [
            'entity_filter' => $entityFilter,
            'dry_run' => $dryRun,
            'active_generators' => $this->getActiveGenerators()
        ]);

        $generatedFiles = [];
        $backupDir = null;
        $errors = [];

        try {
            // 1. Load GeneratorEntity entities from database via repository
            $this->logger->info('[GENMAX] Loading entities from database...');
            $entities = $this->generatorEntityRepository->findForGeneration($entityFilter);

            if (empty($entities)) {
                throw new \RuntimeException('No entities found in database');
            }

            $this->logger->info('[GENMAX] Loaded entities', ['count' => count($entities)]);

            // 2. Create backup (unless dry run)
            if (!$dryRun) {
                $this->logger->info('[GENMAX] Creating backup...');
                $filesToBackup = $this->collectFilesToBackup($entities);
                $backupDir = $this->backupService->createBackup($filesToBackup, 'genmax_generation');
                $this->logger->info('[GENMAX] Backup created', ['dir' => $backupDir]);
            } else {
                $this->logger->info('[GENMAX] Dry run mode: skipping backup');
            }

            // 3. Calculate total steps for progress tracking
            $activeGeneratorCount = $this->countActiveGenerators();
            $totalSteps = count($entities) * $activeGeneratorCount;
            $currentStep = 0;

            // 4. Generate code for each entity
            foreach ($entities as $entity) {
                $this->logger->info("[GENMAX] Generating code for {$entity->getEntityName()}...");

                if (!$dryRun) {
                    // Entity Generation (ACTIVE)
                    if (self::ENTITY_ACTIVE) {
                        try {
                            $files = $this->entityGenerator->generate($entity);
                            $generatedFiles = array_merge($generatedFiles, $files);
                            $currentStep++;

                            // Mark entity as generated
                            $entity->markAsGenerated("Successfully generated at " . date('Y-m-d H:i:s'));
                        } catch (\Throwable $e) {
                            $this->logger->error("[GENMAX] Entity generation failed", [
                                'entity' => $entity->getEntityName(),
                                'error' => $e->getMessage()
                            ]);
                            $entity->markAsFailed($e->getMessage());
                            throw $e;
                        }
                    }

                    // API Platform
                    if (self::API_ACTIVE && $entity->isApiEnabled()) {
                        try {
                            $file = $this->apiGenerator->generate($entity);
                            if ($file) {
                                $generatedFiles[] = $file;
                            }
                            $currentStep++;
                        } catch (\Throwable $e) {
                            $this->logger->error("[GENMAX] API Platform generation failed", [
                                'entity' => $entity->getEntityName(),
                                'error' => $e->getMessage()
                            ]);
                            throw $e;
                        }
                    }

                    // DTOs
                    if (self::DTO_ACTIVE && $entity->isDtoEnabled()) {
                        try {
                            $files = $this->dtoGenerator->generate($entity);
                            $generatedFiles = array_merge($generatedFiles, $files);
                            $currentStep++;
                        } catch (\Throwable $e) {
                            $this->logger->error("[GENMAX] DTO generation failed", [
                                'entity' => $entity->getEntityName(),
                                'error' => $e->getMessage()
                            ]);
                            throw $e;
                        }
                    }

                    // State Processors
                    if (self::STATE_PROCESSOR_ACTIVE && $entity->isDtoEnabled()) {
                        try {
                            $files = $this->stateProcessorGenerator->generate($entity);
                            $generatedFiles = array_merge($generatedFiles, $files);
                            $currentStep++;
                        } catch (\Throwable $e) {
                            $this->logger->error("[GENMAX] State Processor generation failed", [
                                'entity' => $entity->getEntityName(),
                                'error' => $e->getMessage()
                            ]);
                            throw $e;
                        }
                    }

                    // Repository (Future)
                    if (self::REPOSITORY_ACTIVE) {
                        // $files = $this->repositoryGenerator->generate($entity);
                        // $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Controller (Future)
                    if (self::CONTROLLER_ACTIVE) {
                        // $files = $this->controllerGenerator->generate($entity);
                        // $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Voter (Future)
                    if (self::VOTER_ACTIVE && $entity->isVoterEnabled()) {
                        // $files = $this->voterGenerator->generate($entity);
                        // $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Form (Future)
                    if (self::FORM_ACTIVE) {
                        // $files = $this->formGenerator->generate($entity);
                        // $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Templates (Future)
                    if (self::TEMPLATE_ACTIVE) {
                        // $files = $this->templateGenerator->generate($entity);
                        // $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    // Tests (Future)
                    if (self::TESTS_ACTIVE && $entity->isTestEnabled()) {
                        // $files = $this->testGenerator->generate($entity);
                        // $generatedFiles = array_merge($generatedFiles, $files);
                        $currentStep++;
                    }

                    $progress = $totalSteps > 0 ? round(($currentStep / $totalSteps) * 100, 1) : 100;
                    $this->logger->info("[GENMAX] Completed {$entity->getEntityName()}", [
                        'progress' => $progress . '%',
                        'step' => $currentStep,
                        'total' => $totalSteps
                    ]);
                } else {
                    // Dry run: just log
                    $this->logger->info("[GENMAX] [DRY RUN] Would generate {$entity->getEntityName()}");
                    $currentStep += $activeGeneratorCount;
                }
            }

            // 5. Generate navigation (Future)
            if (self::NAVIGATION_ACTIVE && !$dryRun) {
                $this->logger->info('[GENMAX] Generating navigation...');
                // $this->navigationGenerator->generate($entities);
            }

            // 6. Generate translations (Future)
            if (self::TRANSLATION_ACTIVE && !$dryRun) {
                $this->logger->info('[GENMAX] Generating translations...');
                // $this->translationGenerator->generate($entities);
            }

            // 7. Persist entity generation status
            if (!$dryRun) {
                $this->entityManager->flush();
            }

            $this->logger->info('[GENMAX] Code generation completed successfully', [
                'files_generated' => count($generatedFiles),
                'entities' => count($entities),
                'dry_run' => $dryRun
            ]);

            return [
                'success' => true,
                'generated_files' => array_filter($generatedFiles),
                'backup_dir' => $backupDir,
                'errors' => [],
                'entity_count' => count($entities)
            ];

        } catch (\Throwable $e) {
            $this->logger->error('[GENMAX] Code generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $errors[] = $e->getMessage();

            // Rollback if backup was created
            if ($backupDir && !$dryRun) {
                $this->logger->warning('[GENMAX] Rolling back changes...');
                try {
                    $this->backupService->restoreBackup($backupDir);
                    $this->logger->info('[GENMAX] Rollback completed');
                } catch (\Throwable $rollbackError) {
                    $this->logger->critical('[GENMAX] Rollback failed', [
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
     * @param array<GeneratorEntity> $entities
     * @return array<string>
     */
    private function collectFilesToBackup(array $entities): array
    {
        $files = [];

        foreach ($entities as $entity) {
            $entityName = $entity->getEntityName();

            // Entity files
            $files[] = sprintf('%s/%s/%sGenerated.php', $this->projectDir, $this->paths['entity_generated_dir'], $entityName);
            $files[] = sprintf('%s/%s/%s.php', $this->projectDir, $this->paths['entity_dir'], $entityName);

            // Future: Repository files
            if (self::REPOSITORY_ACTIVE) {
                $files[] = sprintf('%s/%s/%sRepositoryGenerated.php', $this->projectDir, $this->paths['repository_generated_dir'], $entityName);
                $files[] = sprintf('%s/%s/%sRepository.php', $this->projectDir, $this->paths['repository_dir'], $entityName);
            }

            // Future: Controller files
            if (self::CONTROLLER_ACTIVE) {
                $files[] = sprintf('%s/%s/%sControllerGenerated.php', $this->projectDir, $this->paths['controller_generated_dir'], $entityName);
                $files[] = sprintf('%s/%s/%sController.php', $this->projectDir, $this->paths['controller_dir'], $entityName);
            }

            // Future: Voter files
            if (self::VOTER_ACTIVE && $entity->isVoterEnabled()) {
                $files[] = sprintf('%s/%s/%sVoterGenerated.php', $this->projectDir, $this->paths['voter_generated_dir'], $entityName);
                $files[] = sprintf('%s/%s/%sVoter.php', $this->projectDir, $this->paths['voter_dir'], $entityName);
            }

            // Future: Form files
            if (self::FORM_ACTIVE) {
                $files[] = sprintf('%s/%s/%sTypeGenerated.php', $this->projectDir, $this->paths['form_generated_dir'], $entityName);
                $files[] = sprintf('%s/%s/%sType.php', $this->projectDir, $this->paths['form_dir'], $entityName);
            }

            // Future: Templates
            if (self::TEMPLATE_ACTIVE) {
                $slug = $entity->getSlug();
                $files[] = sprintf('%s/%s/%s/index.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
                $files[] = sprintf('%s/%s/%s/form.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
                $files[] = sprintf('%s/%s/%s/show.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
            }

            // Future: API Platform config
            if (self::API_ACTIVE && $entity->isApiEnabled()) {
                $files[] = sprintf('%s/%s/%s.yaml', $this->projectDir, $this->paths['api_platform_config_dir'], $entityName);
            }

            // Future: Tests
            if (self::TESTS_ACTIVE && $entity->isTestEnabled()) {
                $files[] = sprintf('%s/%s/%sTest.php', $this->projectDir, $this->paths['test_entity_dir'], $entityName);
                $files[] = sprintf('%s/%s/%sRepositoryTest.php', $this->projectDir, $this->paths['test_repository_dir'], $entityName);
                $files[] = sprintf('%s/%s/%sControllerTest.php', $this->projectDir, $this->paths['test_controller_dir'], $entityName);
            }
        }

        // Future: Navigation and translations
        if (self::NAVIGATION_ACTIVE) {
            $files[] = sprintf('%s/%s', $this->projectDir, $this->paths['base_template']);
        }
        if (self::TRANSLATION_ACTIVE) {
            $files[] = sprintf('%s/%s/messages.en.yaml', $this->projectDir, $this->paths['translations_dir']);
        }

        return $files;
    }

    /**
     * Count active generators
     */
    private function countActiveGenerators(): int
    {
        $count = 0;
        $count += self::ENTITY_ACTIVE ? 1 : 0;
        $count += self::API_ACTIVE ? 1 : 0;
        $count += self::DTO_ACTIVE ? 1 : 0;
        $count += self::STATE_PROCESSOR_ACTIVE ? 1 : 0;
        $count += self::REPOSITORY_ACTIVE ? 1 : 0;
        $count += self::CONTROLLER_ACTIVE ? 1 : 0;
        $count += self::VOTER_ACTIVE ? 1 : 0;
        $count += self::FORM_ACTIVE ? 1 : 0;
        $count += self::TEMPLATE_ACTIVE ? 1 : 0;
        $count += self::TESTS_ACTIVE ? 1 : 0;
        return $count;
    }

    /**
     * Get list of active generators
     *
     * @return array<string>
     */
    private function getActiveGenerators(): array
    {
        $active = [];
        if (self::ENTITY_ACTIVE) $active[] = 'entity';
        if (self::API_ACTIVE) $active[] = 'api';
        if (self::DTO_ACTIVE) $active[] = 'dto';
        if (self::STATE_PROCESSOR_ACTIVE) $active[] = 'state_processor';
        if (self::REPOSITORY_ACTIVE) $active[] = 'repository';
        if (self::CONTROLLER_ACTIVE) $active[] = 'controller';
        if (self::VOTER_ACTIVE) $active[] = 'voter';
        if (self::FORM_ACTIVE) $active[] = 'form';
        if (self::TEMPLATE_ACTIVE) $active[] = 'template';
        if (self::NAVIGATION_ACTIVE) $active[] = 'navigation';
        if (self::TRANSLATION_ACTIVE) $active[] = 'translation';
        if (self::TESTS_ACTIVE) $active[] = 'tests';
        return $active;
    }
}
