<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Form;

use App\Service\Generator\Form\FormGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class FormGeneratorTest extends GeneratorTestCase
{
    private FormGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new FormGenerator(
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
        $this->assertFileExists($this->testDir . '/src/Form/Generated/ContactTypeGenerated.php');
    }

    public function testGenerateCreatesExtensionClass(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertCount(2, $files);
        $this->assertFileExists($this->testDir . '/src/Form/ContactType.php');
    }

    public function testGeneratedFormContainsFields(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Form/Generated/ContactTypeGenerated.php';

        // Check form fields are added
        $this->assertFileContainsString($filePath, "->add('name'");
        $this->assertFileContainsString($filePath, "->add('email'");
        $this->assertFileContainsString($filePath, "->add('phone'");
        $this->assertFileContainsString($filePath, "->add('status'");
        $this->assertFileContainsString($filePath, "->add('active'");
    }

    public function testGeneratedFormExtendsAbstractType(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Form/Generated/ContactTypeGenerated.php';

        $this->assertFileContainsString($filePath, 'extends AbstractType');
        $this->assertFileContainsString($filePath, 'public function buildForm(');
        $this->assertFileContainsString($filePath, 'public function configureOptions(');
    }

    public function testGeneratedFormUsesCorrectFormTypes(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/src/Form/Generated/ContactTypeGenerated.php';

        // Check that appropriate form types are used
        $this->assertFileContainsString($filePath, 'TextType');
        $this->assertFileContainsString($filePath, 'EmailType');
        $this->assertFileContainsString($filePath, 'CheckboxType');
    }
}
