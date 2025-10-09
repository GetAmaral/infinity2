<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator;

use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Base test case for all Generator tests
 * Provides common setup and utility methods
 */
abstract class GeneratorTestCase extends TestCase
{
    protected string $testDir;
    protected Filesystem $filesystem;
    protected Environment $twig;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/generator_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testDir);

        // Set up Twig with template directory
        $templateDir = __DIR__ . '/../../../templates';
        $loader = new FilesystemLoader($templateDir);
        $this->twig = new Environment($loader);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->remove($this->testDir);
        }
    }

    /**
     * Create Contact entity DTO from CSV for testing
     */
    protected function createContactEntity(): EntityDefinitionDto
    {
        $parser = new CsvParserService();
        $result = $parser->parseAll();
        return EntityDefinitionDto::fromArray($result['entities'][0]);
    }

    /**
     * Assert file contains specific string
     */
    protected function assertFileContainsString(string $filePath, string $needle, string $message = ''): void
    {
        $this->assertFileExists($filePath, "File does not exist: {$filePath}");
        $content = file_get_contents($filePath);
        $this->assertStringContainsString($needle, $content, $message ?: "File does not contain '{$needle}'");
    }

    /**
     * Assert file does not contain specific string
     */
    protected function assertFileNotContainsString(string $filePath, string $needle, string $message = ''): void
    {
        $this->assertFileExists($filePath, "File does not exist: {$filePath}");
        $content = file_get_contents($filePath);
        $this->assertStringNotContainsString($needle, $content, $message ?: "File should not contain '{$needle}'");
    }
}
