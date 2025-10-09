<?php

declare(strict_types=1);

namespace App\Service\Generator\Test;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EntityTestGenerator
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate entity test file
     */
    public function generate(EntityDefinitionDto $entity): string
    {
        if (!$entity->testEnabled) {
            $this->logger->info('Skipping entity test generation (testEnabled=false)', [
                'entity' => $entity->entityName
            ]);
            return '';
        }

        $filePath = sprintf(
            '%s/tests/Entity/%sTest.php',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        $this->logger->info('Generating entity test', [
            'entity' => $entity->entityName,
            'path' => $filePath
        ]);

        $content = $this->twig->render('Generator/test/entity_test.php.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Entity test generated', [
            'entity' => $entity->entityName
        ]);

        return $filePath;
    }
}
