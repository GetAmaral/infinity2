<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * DTO Generator for Genmax
 *
 * Generates Input and Output DTO files using GeneratorEntity as source.
 * Creates four files per entity:
 * 1. Input DTO Base (Generated/{Entity}InputGenerated.php) - ALWAYS regenerated
 * 2. Input DTO Extension ({Entity}Input.php) - Generated ONCE only
 * 3. Output DTO Base (Generated/{Entity}OutputGenerated.php) - ALWAYS regenerated
 * 4. Output DTO Extension ({Entity}Output.php) - Generated ONCE only
 */
class DtoGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        private readonly array $templates,
        private readonly Environment $twig,
        private readonly SmartFileWriter $fileWriter,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate DTO files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isDtoEnabled()) {
            $this->logger->info('[GENMAX] Skipping DTO generation (DTO not enabled)', [
                'entity' => $entity->getEntityName()
            ]);
            return [];
        }

        $generatedFiles = [];

        $this->logger->info('[GENMAX] Generating DTOs', [
            'entity' => $entity->getEntityName(),
            'properties' => $entity->getProperties()->count()
        ]);

        // Always generate base classes (can be regenerated safely)
        $generatedFiles[] = $this->generateInputDtoGenerated($entity);
        $generatedFiles[] = $this->generateOutputDtoGenerated($entity);

        // Generate extension classes ONCE only (user can customize)
        $inputExtension = $this->generateInputDtoExtension($entity);
        if ($inputExtension) {
            $generatedFiles[] = $inputExtension;
        }

        $outputExtension = $this->generateOutputDtoExtension($entity);
        if ($outputExtension) {
            $generatedFiles[] = $outputExtension;
        }

        return array_filter($generatedFiles);
    }

    /**
     * Generate Input DTO base class: src/Dto/Generated/{Entity}InputDtoGenerated.php
     */
    private function generateInputDtoGenerated(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sInputDtoGenerated.php',
            $this->projectDir,
            $this->paths['dto_generated_dir'],
            $entity->getEntityName()
        );

        try {
            $content = $this->twig->render($this->templates['dto_input_generated'], [
                'entity' => $entity,
                'generated_namespace' => $this->paths['dto_generated_namespace'],
                'dto_namespace' => $this->paths['dto_namespace'],
                'entity_namespace' => $this->paths['entity_namespace'],
            ]);

            $status = $this->fileWriter->writeFile($filePath, $content, 0666);

            $this->logger->info('[GENMAX] Generated Input DTO base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate Input DTO base class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate Input DTO base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate Input DTO extension class: src/Dto/{Entity}InputDto.php
     */
    private function generateInputDtoExtension(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sInputDto.php',
            $this->projectDir,
            $this->paths['dto_dir'],
            $entity->getEntityName()
        );

        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping Input DTO extension class (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            $content = $this->twig->render($this->templates['dto_input_extension'], [
                'entity' => $entity,
                'namespace' => $this->paths['dto_namespace'],
                'generated_namespace' => $this->paths['dto_generated_namespace'],
            ]);

            $status = $this->fileWriter->writeFile($filePath, $content, 0666);

            $this->logger->info('[GENMAX] Generated Input DTO extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate Input DTO extension class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate Input DTO extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate Output DTO base class: src/Dto/Generated/{Entity}OutputDtoGenerated.php
     */
    private function generateOutputDtoGenerated(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sOutputDtoGenerated.php',
            $this->projectDir,
            $this->paths['dto_generated_dir'],
            $entity->getEntityName()
        );

        try {
            $content = $this->twig->render($this->templates['dto_output_generated'], [
                'entity' => $entity,
                'generated_namespace' => $this->paths['dto_generated_namespace'],
                'dto_namespace' => $this->paths['dto_namespace'],
                'entity_namespace' => $this->paths['entity_namespace'],
            ]);

            $status = $this->fileWriter->writeFile($filePath, $content, 0666);

            $this->logger->info('[GENMAX] Generated Output DTO base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate Output DTO base class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate Output DTO base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate Output DTO extension class: src/Dto/{Entity}OutputDto.php
     */
    private function generateOutputDtoExtension(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sOutputDto.php',
            $this->projectDir,
            $this->paths['dto_dir'],
            $entity->getEntityName()
        );

        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping Output DTO extension class (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            $content = $this->twig->render($this->templates['dto_output_extension'], [
                'entity' => $entity,
                'namespace' => $this->paths['dto_namespace'],
                'generated_namespace' => $this->paths['dto_generated_namespace'],
            ]);

            $status = $this->fileWriter->writeFile($filePath, $content, 0666);

            $this->logger->info('[GENMAX] Generated Output DTO extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate Output DTO extension class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate Output DTO extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
