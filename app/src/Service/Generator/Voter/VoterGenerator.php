<?php

declare(strict_types=1);

namespace App\Service\Generator\Voter;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class VoterGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate voter files (Generated base + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        if (!$entity->voterEnabled) {
            $this->logger->info('Skipping voter generation (not enabled)', ['entity' => $entity->entityName]);
            return [];
        }

        $generatedFiles = [];

        $this->logger->info('Generating voter', ['entity' => $entity->entityName]);

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
     * Generate Security/Voter/Generated/{Entity}VoterGenerated.php
     */
    private function generateBaseClass(EntityDefinitionDto $entity): string
    {
        $filePath = sprintf(
            '%s/src/Security/Voter/Generated/%sVoterGenerated.php',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Render from template
        $content = $this->twig->render('Generator/php/voter_generated.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Security\\Voter\\Generated',
            'className' => $entity->entityName . 'VoterGenerated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated voter base class', ['file' => $filePath]);

        return $filePath;
    }

    /**
     * Generate Security/Voter/{Entity}Voter.php (ONCE only)
     */
    private function generateExtensionClass(EntityDefinitionDto $entity): ?string
    {
        $filePath = sprintf(
            '%s/src/Security/Voter/%sVoter.php',
            $this->projectDir,
            $entity->entityName
        );

        // Skip if exists (user may have added custom logic)
        if (file_exists($filePath)) {
            $this->logger->info('Skipping voter extension (already exists)', ['file' => $filePath]);
            return null;
        }

        // Render from template
        $content = $this->twig->render('Generator/php/voter_extension.php.twig', [
            'entity' => $entity,
            'namespace' => 'App\\Security\\Voter',
            'className' => $entity->entityName . 'Voter',
            'extendsClass' => $entity->entityName . 'VoterGenerated',
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated voter extension class', ['file' => $filePath]);

        return $filePath;
    }
}
