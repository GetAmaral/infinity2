<?php

declare(strict_types=1);

namespace App\Service\Generator\Test;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class VoterTestGenerator
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate voter test file
     */
    public function generate(EntityDefinitionDto $entity): string
    {
        if (!$entity->testEnabled || !$entity->voterEnabled) {
            $this->logger->info('Skipping voter test generation', [
                'entity' => $entity->entityName,
                'testEnabled' => $entity->testEnabled,
                'voterEnabled' => $entity->voterEnabled
            ]);
            return '';
        }

        $filePath = sprintf(
            '%s/tests/Security/Voter/%sVoterTest.php',
            $this->projectDir,
            $entity->entityName
        );

        // Create directory
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        $this->logger->info('Generating voter test', [
            'entity' => $entity->entityName,
            'path' => $filePath
        ]);

        $content = $this->twig->render('generator/test/voter_test.php.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        $this->logger->info('Voter test generated', [
            'entity' => $entity->entityName
        ]);

        return $filePath;
    }
}
