<?php

declare(strict_types=1);

namespace App\Service\Generator\Template;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class TemplateGenerator
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate all templates for an entity
     *
     * @return array<string>
     */
    public function generate(EntityDefinitionDto $entity): array
    {
        $templateDir = sprintf(
            '%s/templates/%s',
            $this->projectDir,
            $entity->getLowercaseName()
        );

        // Create directory
        if (!is_dir($templateDir)) {
            $this->filesystem->mkdir($templateDir, 0755);
        }

        $this->logger->info('Generating templates', [
            'entity' => $entity->entityName,
            'dir' => $templateDir
        ]);

        $generatedFiles = [];

        // Generate index.html.twig
        $generatedFiles[] = $this->generateIndexTemplate($entity, $templateDir);

        // Generate form.html.twig
        $generatedFiles[] = $this->generateFormTemplate($entity, $templateDir);

        // Generate show.html.twig
        $generatedFiles[] = $this->generateShowTemplate($entity, $templateDir);

        // Generate Turbo Stream templates
        $generatedFiles[] = $this->generateTurboStreamCreate($entity, $templateDir);
        $generatedFiles[] = $this->generateTurboStreamUpdate($entity, $templateDir);
        $generatedFiles[] = $this->generateTurboStreamDelete($entity, $templateDir);

        $this->logger->info('Templates generated', [
            'entity' => $entity->entityName,
            'file_count' => count($generatedFiles)
        ]);

        return $generatedFiles;
    }

    private function generateIndexTemplate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/index.html.twig';

        $content = $this->twig->render('generator/twig/index.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateFormTemplate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/form.html.twig';

        $content = $this->twig->render('generator/twig/form.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateShowTemplate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/show.html.twig';

        $content = $this->twig->render('generator/twig/show.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateTurboStreamCreate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/_turbo_stream_create.html.twig';

        $content = $this->twig->render('generator/twig/turbo_stream_create.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateTurboStreamUpdate(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/_turbo_stream_update.html.twig';

        $content = $this->twig->render('generator/twig/turbo_stream_update.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateTurboStreamDelete(EntityDefinitionDto $entity, string $dir): string
    {
        $filePath = $dir . '/_turbo_stream_delete.html.twig';

        $content = $this->twig->render('generator/twig/turbo_stream_delete.html.twig.twig', [
            'entity' => $entity,
        ]);

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
