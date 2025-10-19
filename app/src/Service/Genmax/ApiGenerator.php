<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * API Platform YAML Configuration Generator for Genmax
 *
 * Generates API Platform 4 YAML configuration files using GeneratorEntity
 * and GeneratorProperty as the source of truth.
 *
 * WHY YAML INSTEAD OF PHP ATTRIBUTES?
 * ====================================
 * PHP attributes DO NOT inherit from parent to child classes. This makes them
 * incompatible with our Generated/Extension pattern where the abstract base
 * class contains all the generated code and the concrete class is for
 * customization.
 *
 * YAML configuration references the concrete class directly, bypassing PHP's
 * attribute inheritance limitation and allowing full control of API Platform
 * configuration from the database.
 *
 * @see GeneratorEntity For entity-level API configuration
 * @see GeneratorProperty For property-level API configuration
 */
class ApiGenerator
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
     * Generate API Platform YAML configuration file
     *
     * @param GeneratorEntity $entity
     * @return string|null File path if generated, null if skipped
     */
    public function generate(GeneratorEntity $entity): ?string
    {
        if (!$entity->isApiEnabled()) {
            $this->logger->info('[GENMAX] Skipping API Platform config (API not enabled)', [
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        $filePath = sprintf(
            '%s/%s/%s.yaml',
            $this->projectDir,
            $this->paths['api_platform_config_dir'],
            $entity->getEntityName()
        );

        try {
            // Create directory if needed
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                $this->filesystem->mkdir($dir, 0755);
            }

            // Render from template
            $content = $this->twig->render($this->templates['api_platform_config'], [
                'entity' => $entity,
            ]);

            // Write file atomically
            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated API Platform configuration', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate API Platform configuration', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate API Platform configuration for {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
