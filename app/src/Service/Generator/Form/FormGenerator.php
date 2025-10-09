<?php

declare(strict_types=1);

namespace App\Service\Generator\Form;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class FormGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate form files (Generated base + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        $generatedFiles = [];

        $this->logger->info('Generating form', ['entity' => $entity->entityName]);

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
     * Generate Form/Generated/{Entity}TypeGenerated.php
     */
    private function generateBaseClass(EntityDefinitionDto $entity): string
    {
        $filePath = sprintf(
            '%s/src/Form/Generated/%sTypeGenerated.php',
            $this->projectDir,
            $entity->entityName
        );

        try {
            // Create directory
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                $this->filesystem->mkdir($dir, 0755);
            }

            // Render from template
            $content = $this->twig->render('generator/php/form_generated.php.twig', [
                'entity' => $entity,
                'namespace' => 'App\\Form\\Generated',
                'className' => $entity->entityName . 'TypeGenerated',
            ]);

            // Atomic write using Filesystem component
            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('Generated form base class', ['file' => $filePath]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate form base class', [
                'entity' => $entity->entityName,
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate form base class {$entity->entityName}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate Form/{Entity}Type.php (ONCE only)
     */
    private function generateExtensionClass(EntityDefinitionDto $entity): ?string
    {
        $filePath = sprintf(
            '%s/src/Form/%sType.php',
            $this->projectDir,
            $entity->entityName
        );

        // Skip if exists (user may have added custom fields)
        if (file_exists($filePath)) {
            $this->logger->info('Skipping form extension (already exists)', ['file' => $filePath]);
            return null;
        }

        try {
            // Render from template
            $content = $this->twig->render('generator/php/form_extension.php.twig', [
                'entity' => $entity,
                'namespace' => 'App\\Form',
                'className' => $entity->entityName . 'Type',
                'extendsClass' => $entity->entityName . 'TypeGenerated',
            ]);

            // Atomic write using Filesystem component
            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('Generated form extension class', ['file' => $filePath]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate form extension class', [
                'entity' => $entity->entityName,
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate form extension class {$entity->entityName}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
