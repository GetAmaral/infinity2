<?php

declare(strict_types=1);

namespace App\Service\Generator\Entity;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EntityGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate entity files (Generated base + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        $generatedFiles = [];

        $this->logger->info('Generating entity', ['entity' => $entity->entityName]);

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
     * Generate Entity/Generated/{Entity}Generated.php
     */
    private function generateBaseClass(EntityDefinitionDto $entity): string
    {
        $filePath = sprintf(
            '%s/src/Entity/Generated/%sGenerated.php',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Render from template
        $content = $this->twig->render('Generator/php/entity_generated.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Entity\\Generated',
            'className' => $entity->entityName . 'Generated',
            'extendsClass' => 'EntityBase',
            'usesOrganizationTrait' => $entity->hasOrganization,
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated entity base class', ['file' => $filePath]);

        return $filePath;
    }

    /**
     * Generate Entity/{Entity}.php (ONCE only)
     */
    private function generateExtensionClass(EntityDefinitionDto $entity): ?string
    {
        $filePath = sprintf(
            '%s/src/Entity/%s.php',
            $this->projectDir,
            $entity->entityName
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('Skipping extension class (already exists)', ['file' => $filePath]);
            return null;
        }

        // Render from template
        $content = $this->twig->render('Generator/php/entity_extension.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Entity',
            'className' => $entity->entityName,
            'extendsClass' => $entity->entityName . 'Generated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated entity extension class', ['file' => $filePath]);

        return $filePath;
    }
}
