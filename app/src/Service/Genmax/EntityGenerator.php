<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Entity Generator for Genmax
 *
 * Generates Doctrine entity files using GeneratorEntity as source.
 * Creates two files per entity:
 * 1. Base class (Generated/{Entity}Generated.php) - ALWAYS regenerated
 * 2. Extension class ({Entity}.php) - Generated ONCE only
 */
class EntityGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        private readonly array $templates,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate entity files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        $generatedFiles = [];

        $this->logger->info('[GENMAX] Generating entity', [
            'entity' => $entity->getEntityName(),
            'properties' => $entity->getProperties()->count()
        ]);

        // Always generate base class (can be regenerated safely)
        $generatedFiles[] = $this->generateBaseClass($entity);

        // Generate extension class ONCE only (user can customize)
        $extensionFile = $this->generateExtensionClass($entity);
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        return array_filter($generatedFiles);
    }

    /**
     * Generate base entity class: src/Entity/Generated/{Entity}Generated.php
     *
     * This file is ALWAYS regenerated and should NOT be edited manually.
     */
    private function generateBaseClass(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sGenerated.php',
            $this->projectDir,
            $this->paths['entity_generated_dir'],
            $entity->getEntityName()
        );

        try {
            // Create directory if needed
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                $this->filesystem->mkdir($dir, 0755);
            }

            // Render from template
            $content = $this->twig->render($this->templates['entity_generated'], [
                'entity' => $entity,
            ]);

            // Write file atomically
            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated entity base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate entity base class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate entity base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate extension entity class: src/Entity/{Entity}.php
     *
     * This file is generated ONCE only. If it exists, it's skipped to preserve
     * user customizations.
     */
    private function generateExtensionClass(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%s.php',
            $this->projectDir,
            $this->paths['entity_dir'],
            $entity->getEntityName()
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping extension class (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            // Render from template
            $content = $this->twig->render($this->templates['entity_extension'], [
                'entity' => $entity,
            ]);

            // Write file atomically
            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated entity extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate entity extension class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate entity extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
