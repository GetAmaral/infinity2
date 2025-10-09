<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Entity;

use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use App\Service\Generator\Entity\EntityGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class EntityGeneratorTest extends TestCase
{
    private string $testDir;
    private EntityGenerator $generator;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/entity_generator_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testDir);

        // Set up Twig with template directory
        $templateDir = __DIR__ . '/../../../../templates';
        $loader = new FilesystemLoader($templateDir);
        $twig = new Environment($loader);

        $this->generator = new EntityGenerator(
            $this->testDir,
            $twig,
            $this->filesystem,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->remove($this->testDir);
        }
    }

    public function testGenerateCreatesBaseClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertNotEmpty($files);
        $this->assertFileExists($this->testDir . '/src/Entity/Generated/ContactGenerated.php');
    }

    public function testGenerateCreatesExtensionClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertCount(2, $files); // Base + Extension
        $this->assertFileExists($this->testDir . '/src/Entity/Contact.php');
    }

    public function testGenerateSkipsExistingExtension(): void
    {
        $entity = $this->createContactEntity();

        // First generation
        $files1 = $this->generator->generate($entity);
        $this->assertCount(2, $files1);

        // Second generation should skip extension
        $files2 = $this->generator->generate($entity);
        $this->assertCount(1, $files2); // Only base class
    }

    public function testGeneratedBaseClassContainsProperties(): void
    {
        $entity = $this->createContactEntity();

        $this->generator->generate($entity);

        $content = file_get_contents($this->testDir . '/src/Entity/Generated/ContactGenerated.php');

        // Check namespace
        $this->assertStringContainsString('namespace App\Entity\Generated;', $content);

        // Check class declaration
        $this->assertStringContainsString('abstract class ContactGenerated', $content);

        // Check OrganizationTrait usage
        $this->assertStringContainsString('use OrganizationTrait;', $content);

        // Check properties
        $this->assertStringContainsString('protected string $name', $content);
        $this->assertStringContainsString('protected string $email', $content);

        // Check getters/setters
        $this->assertStringContainsString('public function getName()', $content);
        $this->assertStringContainsString('public function setName(', $content);
    }

    private function createContactEntity(): EntityDefinitionDto
    {
        // Parse from actual CSV
        $parser = new CsvParserService();
        $result = $parser->parseAll();

        return EntityDefinitionDto::fromArray($result['entities'][0]);
    }
}
