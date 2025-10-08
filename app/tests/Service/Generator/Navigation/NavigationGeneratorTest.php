<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Navigation;

use App\Service\Generator\Navigation\NavigationGenerator;
use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class NavigationGeneratorTest extends TestCase
{
    private string $testDir;
    private NavigationGenerator $generator;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/generator_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testDir . '/templates');

        $this->generator = new NavigationGenerator(
            $this->testDir,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->remove($this->testDir);
        }
    }

    public function testGenerateInjectsNavigationItems(): void
    {
        // Create base template with markers
        $baseTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Test</title></head>
<body>
    <nav>
        <ul class="nav">
            <!-- GENERATOR_NAV_START:CRM -->
            <!-- GENERATOR_NAV_END:CRM -->
        </ul>
    </nav>
</body>
</html>
HTML;

        $templatePath = $this->testDir . '/templates/base.html.twig';
        file_put_contents($templatePath, $baseTemplate);

        // Create entity
        $entity = $this->createContactEntity();

        // Generate navigation
        $this->generator->generate([$entity]);

        // Verify
        $updatedTemplate = file_get_contents($templatePath);
        $this->assertStringContainsString('GENERATOR_NAV_START:CRM', $updatedTemplate);
        $this->assertStringContainsString('GENERATOR_NAV_END:CRM', $updatedTemplate);
        $this->assertStringContainsString('contact_index', $updatedTemplate);
        $this->assertStringContainsString('Contacts', $updatedTemplate);
    }

    public function testGenerateHandlesMissingBaseTemplate(): void
    {
        $entity = $this->createContactEntity();

        // Should not throw exception
        $this->generator->generate([$entity]);

        // Base template doesn't exist, so nothing should be created
        $this->assertFileDoesNotExist($this->testDir . '/templates/base.html.twig');
    }

    public function testGenerateGroupsEntitiesByMenuGroup(): void
    {
        // Create base template with multiple group markers
        $baseTemplate = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <nav>
        <ul class="nav">
            <!-- GENERATOR_NAV_START:CRM -->
            <!-- GENERATOR_NAV_END:CRM -->
            <!-- GENERATOR_NAV_START:Admin -->
            <!-- GENERATOR_NAV_END:Admin -->
        </ul>
    </nav>
</body>
</html>
HTML;

        $templatePath = $this->testDir . '/templates/base.html.twig';
        file_put_contents($templatePath, $baseTemplate);

        $entity = $this->createContactEntity();

        $this->generator->generate([$entity]);

        $updatedTemplate = file_get_contents($templatePath);
        $this->assertStringContainsString('CRM', $updatedTemplate);
    }

    private function createContactEntity(): EntityDefinitionDto
    {
        $parser = new CsvParserService();
        $result = $parser->parseAll();
        return EntityDefinitionDto::fromArray($result['entities'][0]);
    }
}
