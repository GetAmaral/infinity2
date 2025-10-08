<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\ApiPlatform;

use App\Service\Generator\ApiPlatform\ApiPlatformGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class ApiPlatformGeneratorTest extends GeneratorTestCase
{
    private ApiPlatformGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ApiPlatformGenerator(
            $this->testDir,
            $this->twig,
            $this->filesystem,
            new NullLogger()
        );
    }

    public function testGenerateCreatesYamlConfig(): void
    {
        $entity = $this->createContactEntity();

        $file = $this->generator->generate($entity);

        $this->assertNotNull($file);
        $this->assertFileExists($this->testDir . '/config/api_platform/Contact.yaml');
    }

    public function testGeneratedYamlContainsResource(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/config/api_platform/Contact.yaml';

        $this->assertFileContainsString($filePath, 'App\Entity\Contact:');
        $this->assertFileContainsString($filePath, 'shortName: Contact');
    }

    public function testGeneratedYamlContainsOperations(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/config/api_platform/Contact.yaml';

        $this->assertFileContainsString($filePath, 'operations:');
        $this->assertFileContainsString($filePath, 'GetCollection:');
        $this->assertFileContainsString($filePath, 'Get:');
        $this->assertFileContainsString($filePath, 'Post:');
        $this->assertFileContainsString($filePath, 'Put:');
        $this->assertFileContainsString($filePath, 'Delete:');
    }

    public function testGeneratedYamlContainsPagination(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/config/api_platform/Contact.yaml';

        $this->assertFileContainsString($filePath, 'paginationEnabled: true');
        $this->assertFileContainsString($filePath, 'paginationItemsPerPage: 30');
    }

    public function testGeneratedYamlContainsSerializationContext(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/config/api_platform/Contact.yaml';

        $this->assertFileContainsString($filePath, 'normalizationContext:');
        $this->assertFileContainsString($filePath, 'denormalizationContext:');
    }
}
