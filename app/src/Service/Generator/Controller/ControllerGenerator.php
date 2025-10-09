<?php

declare(strict_types=1);

namespace App\Service\Generator\Controller;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class ControllerGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate controller files (Generated base + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        $generatedFiles = [];

        $this->logger->info('Generating controller', ['entity' => $entity->entityName]);

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
     * Generate Controller/Generated/{Entity}ControllerGenerated.php
     */
    private function generateBaseClass(EntityDefinitionDto $entity): string
    {
        $filePath = sprintf(
            '%s/src/Controller/Generated/%sControllerGenerated.php',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Render from template
        $content = $this->twig->render('Generator/php/controller_generated.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Controller\\Generated',
            'className' => $entity->entityName . 'ControllerGenerated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated controller base class', ['file' => $filePath]);

        return $filePath;
    }

    /**
     * Generate Controller/{Entity}Controller.php (ONCE only)
     */
    private function generateExtensionClass(EntityDefinitionDto $entity): ?string
    {
        $filePath = sprintf(
            '%s/src/Controller/%sController.php',
            $this->projectDir,
            $entity->entityName
        );

        // Skip if exists (user may have added custom actions)
        if (file_exists($filePath)) {
            $this->logger->info('Skipping controller extension (already exists)', ['file' => $filePath]);
            return null;
        }

        // Render from template
        $content = $this->twig->render('Generator/php/controller_extension.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Controller',
            'className' => $entity->entityName . 'Controller',
            'extendsClass' => $entity->entityName . 'ControllerGenerated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated controller extension class', ['file' => $filePath]);

        return $filePath;
    }
}
