<?php

declare(strict_types=1);

namespace App\Service\Generator\ApiPlatform;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class ApiPlatformGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate API Platform YAML configuration
     *
     * @return string|null File path if generated, null if skipped
     */
    public function generate(EntityDefinitionDto $entity): ?string
    {
        if (!$entity->apiEnabled) {
            $this->logger->info('Skipping API Platform config (API not enabled)', [
                'entity' => $entity->entityName
            ]);
            return null; // Skip if API not enabled
        }

        $filePath = sprintf(
            '%s/config/api_platform/%s.yaml',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Render from template
        $content = $this->twig->render('Generator/yaml/api_platform_resource.yaml.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Generated API Platform configuration', ['file' => $filePath]);

        return $filePath;
    }
}
