<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * State Processor Generator for Genmax
 *
 * Generates API Platform State Processor files for handling
 * Input DTO -> Entity transformations with nested object support.
 *
 * NOTE: Processors are GENERATED-ONLY (no extension pattern)
 * For custom processing logic, use Event Subscribers or custom processors.
 */
class StateProcessorGenerator
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
     * Generate State Processor file for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isDtoEnabled()) {
            $this->logger->info('[GENMAX] Skipping State Processor generation (DTO not enabled)', [
                'entity' => $entity->getEntityName()
            ]);
            return [];
        }

        $generatedFiles = [];

        $this->logger->info('[GENMAX] Generating State Processor', [
            'entity' => $entity->getEntityName()
        ]);

        $generatedFiles[] = $this->generateProcessor($entity);

        return array_filter($generatedFiles);
    }

    /**
     * Generate State Processor: src/State/{Entity}Processor.php
     *
     * This file is ALWAYS regenerated
     */
    private function generateProcessor(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sProcessor.php',
            $this->projectDir,
            $this->paths['processor_dir'],
            $entity->getEntityName()
        );

        try {
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                $this->filesystem->mkdir($dir, 0755);
            }

            $content = $this->twig->render($this->templates['state_processor'], [
                'entity' => $entity,
                'namespace' => $this->paths['processor_namespace'],
                'dto_namespace' => $this->paths['dto_namespace'],
                'entity_namespace' => $this->paths['entity_namespace'],
            ]);

            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated State Processor', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate State Processor', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate State Processor for {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
