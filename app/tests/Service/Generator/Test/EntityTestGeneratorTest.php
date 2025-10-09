<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Test;

use App\Service\Generator\Test\EntityTestGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class EntityTestGeneratorTest extends GeneratorTestCase
{
    private EntityTestGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new EntityTestGenerator(
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
        $this->assertFileExists($this->testDir . '/tests/Entity/ContactTest.php');
    }

    public function testGeneratedTestContainsTestMethods(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Entity/ContactTest.php';

        $this->assertFileContainsString($filePath, 'class ContactTest extends TestCase');
        $this->assertFileContainsString($filePath, 'public function testGet');
        $this->assertFileContainsString($filePath, 'public function testCreatedAtIsSet');
        $this->assertFileContainsString($filePath, 'public function testUpdatedAtIsSet');
    }

    public function testGeneratedTestContainsPropertyTests(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Entity/ContactTest.php';

        $this->assertFileContainsString($filePath, 'testGetName');
        $this->assertFileContainsString($filePath, 'testGetEmail');
    }

    public function testGeneratedTestContainsOrganizationTest(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Entity/ContactTest.php';

        // Contact has organization, so should have organization test
        $this->assertFileContainsString($filePath, 'testOrganization');
    }
}
