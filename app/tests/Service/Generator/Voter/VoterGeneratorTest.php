<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Voter;

use App\Service\Generator\Voter\VoterGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class VoterGeneratorTest extends GeneratorTestCase
{
    private VoterGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new VoterGenerator(
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
        $this->assertFileExists($this->testDir . '/src/Security/Voter/Generated/ContactVoterGenerated.php');
    }

    public function testGenerateCreatesExtensionClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertCount(2, $files);
        $this->assertFileExists($this->testDir . '/src/Security/Voter/ContactVoter.php');
    }

    public function testGeneratedVoterContainsAttributes(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Security/Voter/Generated/ContactVoterGenerated.php';

        // Check voter attributes
        $this->assertFileContainsString($filePath, "public const VIEW = 'VIEW'");
        $this->assertFileContainsString($filePath, "public const EDIT = 'EDIT'");
        $this->assertFileContainsString($filePath, "public const DELETE = 'DELETE'");
    }

    public function testGeneratedVoterContainsAuthorizationMethods(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Security/Voter/Generated/ContactVoterGenerated.php';

        // Check authorization methods
        $this->assertFileContainsString($filePath, 'protected function canView(');
        $this->assertFileContainsString($filePath, 'protected function canEdit(');
        $this->assertFileContainsString($filePath, 'protected function canDelete(');
    }

    public function testGeneratedVoterChecksOrganization(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Security/Voter/Generated/ContactVoterGenerated.php';

        // Should check organization for multi-tenant entities
        $this->assertFileContainsString($filePath, '->getOrganization()');
    }
}
