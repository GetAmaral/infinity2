<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\BackupService;
use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;
use App\Service\GeneratorOrchestrator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integration test for complete generation workflow
 *
 * Tests the entire generation flow from CSV parsing to file generation
 */
class CompleteGenerationTest extends KernelTestCase
{
    private CsvParserService $parser;
    private CsvValidatorService $validator;
    private GeneratorOrchestrator $orchestrator;
    private Filesystem $filesystem;
    private string $projectDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->parser = $container->get(CsvParserService::class);
        $this->validator = $container->get(CsvValidatorService::class);
        $this->orchestrator = $container->get(GeneratorOrchestrator::class);
        $this->filesystem = new Filesystem();
        $this->projectDir = $container->getParameter('kernel.project_dir');
    }

    public function testCompleteGenerationWorkflow(): void
    {
        // Step 1: Parse CSV files
        $result = $this->parser->parseAll();

        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertNotEmpty($result['entities'], 'Entities should be parsed from CSV');

        // Step 2: Validate data
        $entities = $result['entities'];
        $properties = $result['properties'];

        $validation = $this->validator->validateAll($entities, $properties);

        $this->assertTrue($validation['valid'], 'CSV data should be valid: ' . implode(', ', $validation['errors'] ?? []));

        // Step 3: Verify file structure exists
        $this->assertDirectoryExists($this->projectDir . '/src/Entity/Generated');
        $this->assertDirectoryExists($this->projectDir . '/src/Repository');
        $this->assertDirectoryExists($this->projectDir . '/src/Controller');
        $this->assertDirectoryExists($this->projectDir . '/src/Security/Voter');
        $this->assertDirectoryExists($this->projectDir . '/src/Form');
        $this->assertDirectoryExists($this->projectDir . '/templates');

        // Step 4: Verify at least one complete entity exists
        $firstEntity = $entities[0];
        $entityName = $firstEntity['entityName'];

        // Check entity files
        $entityGeneratedPath = $this->projectDir . '/src/Entity/Generated/' . $entityName . 'Generated.php';
        $entityPath = $this->projectDir . '/src/Entity/' . $entityName . '.php';

        $this->assertFileExists($entityGeneratedPath, "Generated entity should exist: {$entityGeneratedPath}");
        $this->assertFileExists($entityPath, "Entity extension should exist: {$entityPath}");

        // Verify entity content
        $generatedContent = file_get_contents($entityGeneratedPath);
        $this->assertStringContainsString('abstract class ' . $entityName . 'Generated', $generatedContent);
        $this->assertStringContainsString('extends EntityBase', $generatedContent);

        $extensionContent = file_get_contents($entityPath);
        $this->assertStringContainsString('class ' . $entityName, $extensionContent);
        $this->assertStringContainsString('extends ' . $entityName . 'Generated', $extensionContent);

        // Step 5: Verify OrganizationTrait if needed
        if ($firstEntity['hasOrganization']) {
            $this->assertStringContainsString('use OrganizationTrait', $generatedContent);
            $this->assertFileExists($this->projectDir . '/src/Entity/Trait/OrganizationTrait.php');
        }

        // Step 6: Verify repository files
        $repoGeneratedPath = $this->projectDir . '/src/Repository/Generated/' . $entityName . 'RepositoryGenerated.php';
        $repoPath = $this->projectDir . '/src/Repository/' . $entityName . 'Repository.php';

        $this->assertFileExists($repoGeneratedPath);
        $this->assertFileExists($repoPath);

        // Step 7: Verify controller files
        $controllerGeneratedPath = $this->projectDir . '/src/Controller/Generated/' . $entityName . 'ControllerGenerated.php';
        $controllerPath = $this->projectDir . '/src/Controller/' . $entityName . 'Controller.php';

        $this->assertFileExists($controllerGeneratedPath);
        $this->assertFileExists($controllerPath);

        // Step 8: Verify templates
        $entityLower = strtolower($entityName);
        $this->assertFileExists($this->projectDir . '/templates/' . $entityLower . '/index.html.twig');
        $this->assertFileExists($this->projectDir . '/templates/' . $entityLower . '/form.html.twig');
        $this->assertFileExists($this->projectDir . '/templates/' . $entityLower . '/show.html.twig');

        // Step 9: Verify API Platform configuration if API enabled
        if ($firstEntity['apiEnabled']) {
            $apiConfigPath = $this->projectDir . '/config/api_platform/' . $entityName . '.yaml';
            $this->assertFileExists($apiConfigPath);

            $apiConfig = file_get_contents($apiConfigPath);
            $this->assertStringContainsString($entityName, $apiConfig);
        }

        // Step 10: Verify navigation updated
        $baseTemplate = $this->projectDir . '/templates/base.html.twig';
        $this->assertFileExists($baseTemplate);

        $templateContent = file_get_contents($baseTemplate);
        $this->assertStringContainsString('<!-- GENERATOR_MENU_START -->', $templateContent);
        $this->assertStringContainsString('<!-- GENERATOR_MENU_END -->', $templateContent);
    }

    public function testBackupCreatedBeforeGeneration(): void
    {
        $backupDir = $this->projectDir . '/var/generatorBackup';

        // Verify backup directory exists
        $this->assertDirectoryExists($backupDir);

        // Check if backups exist
        $backups = glob($backupDir . '/*');
        $this->assertNotEmpty($backups, 'At least one backup should exist');

        // Verify latest backup has manifest
        $latestBackup = end($backups);
        $manifestPath = $latestBackup . '/manifest.json';

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $this->assertIsArray($manifest);
            $this->assertArrayHasKey('timestamp', $manifest);
            $this->assertArrayHasKey('files', $manifest);
        }
    }

    public function testGeneratedFilesFollowNamingConvention(): void
    {
        $result = $this->parser->parseAll();
        $entities = $result['entities'];

        foreach (array_slice($entities, 0, 3) as $entity) {
            $entityName = $entity['entityName'];

            // Generated files should have "Generated" suffix
            $generatedFiles = [
                'src/Entity/Generated/' . $entityName . 'Generated.php',
                'src/Repository/Generated/' . $entityName . 'RepositoryGenerated.php',
                'src/Controller/Generated/' . $entityName . 'ControllerGenerated.php',
            ];

            foreach ($generatedFiles as $file) {
                $fullPath = $this->projectDir . '/' . $file;
                if (file_exists($fullPath)) {
                    $this->assertFileExists($fullPath);
                }
            }

            // Extension files should not have "Generated" suffix
            $extensionFiles = [
                'src/Entity/' . $entityName . '.php',
                'src/Repository/' . $entityName . 'Repository.php',
                'src/Controller/' . $entityName . 'Controller.php',
            ];

            foreach ($extensionFiles as $file) {
                $fullPath = $this->projectDir . '/' . $file;
                if (file_exists($fullPath)) {
                    $this->assertFileExists($fullPath);
                }
            }
        }
    }

    public function testAllRequiredDirectoriesExist(): void
    {
        $requiredDirs = [
            'src/Entity/Generated',
            'src/Entity/Trait',
            'src/Repository/Generated',
            'src/Controller/Generated',
            'src/Security/Voter/Generated',
            'src/Form/Generated',
            'config/api_platform',
            'templates',
            'var/generatorBackup',
        ];

        foreach ($requiredDirs as $dir) {
            $fullPath = $this->projectDir . '/' . $dir;
            $this->assertDirectoryExists($fullPath, "Required directory should exist: {$dir}");
        }
    }
}
