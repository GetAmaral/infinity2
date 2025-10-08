<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Repository;

use App\Service\Generator\Repository\RepositoryGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class RepositoryGeneratorTest extends GeneratorTestCase
{
    private RepositoryGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RepositoryGenerator(
            $this->testDir,
            $this->twig,
            $this->filesystem,
            new NullLogger()
        );
    }

    public function testGenerateCreatesBaseClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertNotEmpty($files);
        $this->assertFileExists($this->testDir . '/src/Repository/Generated/ContactRepositoryGenerated.php');
    }

    public function testGenerateCreatesExtensionClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertCount(2, $files);
        $this->assertFileExists($this->testDir . '/src/Repository/ContactRepository.php');
    }

    public function testGeneratedRepositoryContainsSearchMethod(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Repository/Generated/ContactRepositoryGenerated.php';

        $this->assertFileContainsString($filePath, 'public function search(');
        $this->assertFileContainsString($filePath, 'protected function getSearchableFields()');
    }

    public function testGeneratedRepositoryContainsPaginationMethod(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Repository/Generated/ContactRepositoryGenerated.php';

        $this->assertFileContainsString($filePath, 'public function findPaginated(');
    }

    public function testGeneratedRepositoryContainsSaveAndRemove(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Repository/Generated/ContactRepositoryGenerated.php';

        $this->assertFileContainsString($filePath, 'public function save(');
        $this->assertFileContainsString($filePath, 'public function remove(');
    }
}
