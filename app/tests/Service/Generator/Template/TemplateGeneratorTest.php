<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Template;

use App\Service\Generator\Template\TemplateGenerator;
use App\Tests\Service\Generator\GeneratorTestCase;
use Psr\Log\NullLogger;

class TemplateGeneratorTest extends GeneratorTestCase
{
    private TemplateGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new TemplateGenerator(
            $this->testDir,
            $this->twig,
            $this->filesystem,
            new NullLogger()
        );
    }

    public function testGenerateCreatesAllTemplates(): void
    {
        $entity = $this->createContactEntity();

        $files = $this->generator->generate($entity);

        $this->assertCount(6, $files); // 6 template files
        $this->assertFileExists($this->testDir . '/templates/contact/index.html.twig');
        $this->assertFileExists($this->testDir . '/templates/contact/form.html.twig');
        $this->assertFileExists($this->testDir . '/templates/contact/show.html.twig');
        $this->assertFileExists($this->testDir . '/templates/contact/_turbo_stream_create.html.twig');
        $this->assertFileExists($this->testDir . '/templates/contact/_turbo_stream_update.html.twig');
        $this->assertFileExists($this->testDir . '/templates/contact/_turbo_stream_delete.html.twig');
    }

    public function testIndexTemplateContainsSearch(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/templates/contact/index.html.twig';

        $this->assertFileContainsString($filePath, 'type="search"');
        $this->assertFileContainsString($filePath, 'name="q"');
        $this->assertFileContainsString($filePath, 'action.search');
    }

    public function testIndexTemplateContainsTable(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/templates/contact/index.html.twig';

        $this->assertFileContainsString($filePath, '<table');
        $this->assertFileContainsString($filePath, '<thead>');
        $this->assertFileContainsString($filePath, '<tbody');
        $this->assertFileContainsString($filePath, 'for item in items');
    }

    public function testFormTemplateContainsFormFields(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/templates/contact/form.html.twig';

        $this->assertFileContainsString($filePath, 'form_start(form');
        $this->assertFileContainsString($filePath, 'form_end(form');
        $this->assertFileContainsString($filePath, 'form_widget');
    }

    public function testShowTemplateContainsFields(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $filePath = $this->testDir . '/templates/contact/show.html.twig';

        $this->assertFileContainsString($filePath, '<dl');
        $this->assertFileContainsString($filePath, '<dt');
        $this->assertFileContainsString($filePath, '<dd');
    }

    public function testTurboStreamTemplatesExist(): void
    {
        $entity = $this->createContactEntity();
        $this->generator->generate($entity);

        $createPath = $this->testDir . '/templates/contact/_turbo_stream_create.html.twig';
        $updatePath = $this->testDir . '/templates/contact/_turbo_stream_update.html.twig';
        $deletePath = $this->testDir . '/templates/contact/_turbo_stream_delete.html.twig';

        $this->assertFileContainsString($createPath, '<turbo-stream');
        $this->assertFileContainsString($createPath, 'action="prepend"');

        $this->assertFileContainsString($updatePath, '<turbo-stream');
        $this->assertFileContainsString($updatePath, 'action="replace"');

        $this->assertFileContainsString($deletePath, '<turbo-stream');
        $this->assertFileContainsString($deletePath, 'action="remove"');
    }
}
