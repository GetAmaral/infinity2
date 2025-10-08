<?php

declare(strict_types=1);

namespace App\Service\Generator\Entity;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class OrganizationTraitGenerator
{
    private const TRAIT_PATH = '/src/Entity/Trait/OrganizationTrait.php';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem
    ) {}

    /**
     * Generate OrganizationTrait if it doesn't exist
     */
    public function generate(): bool
    {
        $filePath = $this->projectDir . self::TRAIT_PATH;

        // Only generate if file doesn't exist
        if (file_exists($filePath)) {
            return false; // Already exists, skip
        }

        // Create directory if needed
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        // Render trait from template
        $content = $this->twig->render('generator/php/organization_trait.php.twig');

        // Write file
        file_put_contents($filePath, $content);

        return true; // Generated
    }

    /**
     * Check if trait exists
     */
    public function exists(): bool
    {
        return file_exists($this->projectDir . self::TRAIT_PATH);
    }

    /**
     * Get trait file path
     */
    public function getFilePath(): string
    {
        return $this->projectDir . self::TRAIT_PATH;
    }
}
