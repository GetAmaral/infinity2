<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * State Provider Generator for Genmax
 *
 * Generates API Platform State Provider files for handling
 * data retrieval (GET, GetCollection) with repository integration.
 *
 * State Providers delegate to Repositories for all query logic,
 * allowing reuse of BaseRepository's search/filter/pagination features.
 */
class StateProviderGenerator
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
     * Generate State Provider file for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isApiEnabled()) {
            $this->logger->info('[GENMAX] Skipping State Provider generation (API not enabled)', [
                'entity' => $entity->getEntityName()
            ]);
            return [];
        }

        $generatedFiles = [];

        // Generate State Provider (single file, no extension pattern)
        $providerPath = sprintf(
            '%s/%s/%sProvider.php',
            $this->projectDir,
            $this->paths['provider_dir'],
            $entity->getEntityName()
        );

        try {
            // Render from template
            $content = $this->twig->render($this->templates['state_provider'], [
                'entity' => $entity,
                'entity_name' => $entity->getEntityName(),
                'entity_namespace' => $this->paths['entity_namespace'],
                'repository_namespace' => $this->paths['repository_namespace'],
                'provider_namespace' => $this->paths['provider_namespace'],
            ]);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($providerPath, $content);

            $this->logger->info('[GENMAX] Generated State Provider', [
                'file' => $providerPath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            $generatedFiles[] = $providerPath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate State Provider', [
                'entity' => $entity->getEntityName(),
                'file' => $providerPath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate State Provider for {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }

        return $generatedFiles;
    }
}
