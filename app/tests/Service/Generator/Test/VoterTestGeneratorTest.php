<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Test;

use App\Service\Generator\Test\VoterTestGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class VoterTestGeneratorTest extends GeneratorTestCase
{
    private VoterTestGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new VoterTestGenerator(
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
        $this->assertFileExists($this->testDir . '/tests/Security/Voter/ContactVoterTest.php');
    }

    public function testGeneratedTestContainsVoterTests(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Security/Voter/ContactVoterTest.php';

        $this->assertFileContainsString($filePath, 'class ContactVoterTest extends TestCase');
        $this->assertFileContainsString($filePath, 'ContactVoter');
    }

    public function testGeneratedTestContainsAttributeTests(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Security/Voter/ContactVoterTest.php';

        $this->assertFileContainsString($filePath, 'testVIEW');
        $this->assertFileContainsString($filePath, 'testEDIT');
        $this->assertFileContainsString($filePath, 'testDELETE');
    }

    public function testGeneratedTestContainsHelperMethods(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/tests/Security/Voter/ContactVoterTest.php';

        $this->assertFileContainsString($filePath, 'private function createUser');
        $this->assertFileContainsString($filePath, 'private function createContact');
    }
}
