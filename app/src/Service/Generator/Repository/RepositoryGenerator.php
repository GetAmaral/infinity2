<?php

declare(strict_types=1);

namespace App\Service\Generator\Repository;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class RepositoryGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate repository files (Generated base + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        $generatedFiles = [];

        $this->logger->info('Generating repository', ['entity' => $entity->entityName]);

        // Generate base class (ALWAYS regenerated)
        $generatedFiles[] = $this->generateBaseClass($entity);

        // Generate extension class (ONCE only)
        $extensionFile = $this->generateExtensionClass($entity);
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        return $generatedFiles;
    }

    /**
     * Generate Repository/Generated/{Entity}RepositoryGenerated.php
     */
    private function generateBaseClass(EntityDefinitionDto $entity): string
    {
        $filePath = sprintf(
            '%s/src/Repository/Generated/%sRepositoryGenerated.php',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Render from template
        $content = $this->twig->render('generator/php/repository_generated.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Repository\\Generated',
            'className' => $entity->entityName . 'RepositoryGenerated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated repository base class', ['file' => $filePath]);

        return $filePath;
    }

    /**
     * Generate Repository/{Entity}Repository.php (ONCE only)
     */
    private function generateExtensionClass(EntityDefinitionDto $entity): ?string
    {
        $filePath = sprintf(
            '%s/src/Repository/%sRepository.php',
            $this->projectDir,
            $entity->entityName
        );

        // Skip if exists (user may have added custom queries)
        if (file_exists($filePath)) {
            $this->logger->info('Skipping repository extension (already exists)', ['file' => $filePath]);
            return null;
        }

        // Render from template
        $content = $this->twig->render('generator/php/repository_extension.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Repository',
            'className' => $entity->entityName . 'Repository',
            'extendsClass' => $entity->entityName . 'RepositoryGenerated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated repository extension class', ['file' => $filePath]);

        return $filePath;
    }
}
