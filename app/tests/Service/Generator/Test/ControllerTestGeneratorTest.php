<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Test;

use App\Service\Generator\Test\ControllerTestGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class ControllerTestGeneratorTest extends GeneratorTestCase
{
    private ControllerTestGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ControllerTestGenerator(
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
        $this->assertFileExists($this->testDir . '/tests/Controller/ContactControllerTest.php');
    }

    public function testGeneratedTestContainsCrudTests(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Controller/ContactControllerTest.php';

        $this->assertFileContainsString($filePath, 'class ContactControllerTest extends WebTestCase');
        $this->assertFileContainsString($filePath, 'public function testIndex');
        $this->assertFileContainsString($filePath, 'public function testShow');
        $this->assertFileContainsString($filePath, 'public function testNew');
        $this->assertFileContainsString($filePath, 'public function testCreate');
        $this->assertFileContainsString($filePath, 'public function testEdit');
        $this->assertFileContainsString($filePath, 'public function testUpdate');
        $this->assertFileContainsString($filePath, 'public function testDelete');
    }

    public function testGeneratedTestContainsHelperMethods(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Controller/ContactControllerTest.php';

        $this->assertFileContainsString($filePath, 'private function createTestContact');
        $this->assertFileContainsString($filePath, 'private function cleanDatabase');
    }
}
