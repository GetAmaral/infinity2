<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Controller;

use App\Service\Generator\Controller\ControllerGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class ControllerGeneratorTest extends GeneratorTestCase
{
    private ControllerGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ControllerGenerator(
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
        $this->assertFileExists($this->testDir . '/src/Controller/Generated/ContactControllerGenerated.php');
    }

    public function testGenerateCreatesExtensionClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertCount(2, $files);
        $this->assertFileExists($this->testDir . '/src/Controller/ContactController.php');
    }

    public function testGeneratedBaseClassContainsCrudActions(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Controller/Generated/ContactControllerGenerated.php';

        // Check CRUD methods exist
        $this->assertFileContainsString($filePath, 'public function index(');
        $this->assertFileContainsString($filePath, 'public function show(');
        $this->assertFileContainsString($filePath, 'public function new(');
        $this->assertFileContainsString($filePath, 'public function edit(');
        $this->assertFileContainsString($filePath, 'public function delete(');
    }

    public function testGeneratedControllerUsesOrganizationContext(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Controller/Generated/ContactControllerGenerated.php';

        // Organization context should be injected
        $this->assertFileContainsString($filePath, 'OrganizationContext $organizationContext');
        $this->assertFileContainsString($filePath, '$this->organizationContext');
    }

    public function testGenerateSkipsExistingExtension(): void
    {
        $entity = $this->createContactEntity();

        $files1 = $this->generator->generate($entity);
        $this->assertCount(2, $files1);

        $files2 = $this->generator->generate($entity);
        $this->assertCount(1, $files2); // Only base class regenerated
    }
}
