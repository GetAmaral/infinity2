<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Test;

use App\Service\Generator\Test\RepositoryTestGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class RepositoryTestGeneratorTest extends GeneratorTestCase
{
    private RepositoryTestGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RepositoryTestGenerator(
            $this->testDir,
            $this->twig,
            $this->filesystem,
            new NullLogger()
        );
    }

    public function testGenerateCreatesTestFile(): void
    {
        $entity = $this->createContactEntity();

        $file = $this->generator->generate($entity);

        $this->assertNotEmpty($file);
        $this->assertFileExists($this->testDir . '/tests/Repository/ContactRepositoryTest.php');
    }

    public function testGeneratedTestContainsRepositoryTests(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Repository/ContactRepositoryTest.php';

        $this->assertFileContainsString($filePath, 'class ContactRepositoryTest extends KernelTestCase');
        $this->assertFileContainsString($filePath, 'public function testFindAll');
        $this->assertFileContainsString($filePath, 'public function testCount');
    }

    public function testGeneratedTestContainsSearchTest(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Repository/ContactRepositoryTest.php';

        $this->assertFileContainsString($filePath, 'testSearch');
    }

    public function testGeneratedTestContainsHelperMethods(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Repository/ContactRepositoryTest.php';

        $this->assertFileContainsString($filePath, 'private function createTestContact');
    }
}
