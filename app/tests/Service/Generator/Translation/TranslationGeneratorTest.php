<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Translation;

use App\Service\Generator\Translation\TranslationGenerator;
use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class TranslationGeneratorTest extends TestCase
{
    private string $testDir;
    private TranslationGenerator $generator;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/generator_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testDir);

        $this->generator = new TranslationGenerator(
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

    public function testGenerateCreatesTranslationFile(): void
    {
        $entity = $this->createContactEntity();

        $this->generator->generate([$entity]);

        $translationPath = $this->testDir . '/translations/messages.en.yaml';
        $this->assertFileExists($translationPath);
    }

    public function testGenerateIncludesEntityLabels(): void
    {
        $entity = $this->createContactEntity();

        $this->generator->generate([$entity]);

        $translationPath = $this->testDir . '/translations/messages.en.yaml';
        $translations = Yaml::parseFile($translationPath);

        $this->assertArrayHasKey('Contact', $translations);
        $this->assertArrayHasKey('Contacts', $translations);
    }

    public function testGenerateIncludesCommonActions(): void
    {
        $entity = $this->createContactEntity();

        $this->generator->generate([$entity]);

        $translationPath = $this->testDir . '/translations/messages.en.yaml';
        $translations = Yaml::parseFile($translationPath);

        $this->assertArrayHasKey('action', $translations);
        $this->assertArrayHasKey('create', $translations['action']);
        $this->assertArrayHasKey('edit', $translations['action']);
        $this->assertArrayHasKey('delete', $translations['action']);
        $this->assertArrayHasKey('save', $translations['action']);
    }

    public function testGenerateIncludesFieldLabels(): void
    {
        $entity = $this->createContactEntity();

        $this->generator->generate([$entity]);

        $translationPath = $this->testDir . '/translations/messages.en.yaml';
        $translations = Yaml::parseFile($translationPath);

        $this->assertArrayHasKey('field', $translations);
        $this->assertArrayHasKey('created_at', $translations['field']);
        $this->assertArrayHasKey('updated_at', $translations['field']);
    }

    public function testGeneratePreservesExistingTranslations(): void
    {
        // Create existing translations
        $existingTranslations = [
            'Contact' => 'Custom Contact Translation',
            'custom_key' => 'Custom Value'
        ];

        $translationPath = $this->testDir . '/translations/messages.en.yaml';
        $this->filesystem->mkdir(dirname($translationPath));
        file_put_contents($translationPath, Yaml::dump($existingTranslations));

        $entity = $this->createContactEntity();

        $this->generator->generate([$entity]);

        $translations = Yaml::parseFile($translationPath);

        // Existing translations should be preserved
        $this->assertEquals('Custom Contact Translation', $translations['Contact']);
        $this->assertEquals('Custom Value', $translations['custom_key']);
    }

    private function createContactEntity(): EntityDefinitionDto
    {
        $parser = new CsvParserService();
        $result = $parser->parseAll();
        return EntityDefinitionDto::fromArray($result['entities'][0]);
    }
}
