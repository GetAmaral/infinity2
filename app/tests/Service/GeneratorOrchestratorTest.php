<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GeneratorOrchestrator;
use App\Service\BackupService;
use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use App\Service\Generator\Entity\EntityGenerator;
use App\Service\Generator\ApiPlatform\ApiPlatformGenerator;
use App\Service\Generator\Repository\RepositoryGenerator;
use App\Service\Generator\Controller\ControllerGenerator;
use App\Service\Generator\Voter\VoterGenerator;
use App\Service\Generator\Form\FormGenerator;
use App\Service\Generator\Template\TemplateGenerator;
use App\Service\Generator\Navigation\NavigationGenerator;
use App\Service\Generator\Translation\TranslationGenerator;
use App\Service\Generator\Test\EntityTestGenerator;
use App\Service\Generator\Test\RepositoryTestGenerator;
use App\Service\Generator\Test\ControllerTestGenerator;
use App\Service\Generator\Test\VoterTestGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class GeneratorOrchestratorTest extends TestCase
{
    private GeneratorOrchestrator $orchestrator;
    private CsvParserService&MockObject $csvParser;
    private CsvValidatorService&MockObject $csvValidator;
    private BackupService&MockObject $backupService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->csvParser = $this->createMock(CsvParserService::class);
        $this->csvValidator = $this->createMock(CsvValidatorService::class);
        $this->backupService = $this->createMock(BackupService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->orchestrator = new GeneratorOrchestrator(
            '/test/project',
            $this->csvParser,
            $this->csvValidator,
            $this->backupService,
            $this->createMock(EntityGenerator::class),
            $this->createMock(ApiPlatformGenerator::class),
            $this->createMock(RepositoryGenerator::class),
            $this->createMock(ControllerGenerator::class),
            $this->createMock(VoterGenerator::class),
            $this->createMock(FormGenerator::class),
            $this->createMock(TemplateGenerator::class),
            $this->createMock(NavigationGenerator::class),
            $this->createMock(TranslationGenerator::class),
            $this->createMock(EntityTestGenerator::class),
            $this->createMock(RepositoryTestGenerator::class),
            $this->createMock(ControllerTestGenerator::class),
            $this->createMock(VoterTestGenerator::class),
            $this->logger
        );
    }

    public function testGenerateSuccessfully(): void
    {
        // Mock CSV parsing
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [$this->getTestEntityArray()],
            'properties' => []
        ]);

        // Mock validation success
        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => true,
            'errors' => []
        ]);

        // Mock backup creation
        $this->backupService->expects($this->once())
            ->method('createBackup')
            ->willReturn('/backups/backup-123');

        $result = $this->orchestrator->generate();

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['entity_count']);
        $this->assertEquals('/backups/backup-123', $result['backup_dir']);
        $this->assertEmpty($result['errors']);
    }

    public function testGenerateWithEntityFilter(): void
    {
        // Mock CSV parsing with multiple entities
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [
                $this->getTestEntityArray('Contact'),
                $this->getTestEntityArray('Course'),
            ],
            'properties' => []
        ]);

        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => true,
            'errors' => []
        ]);

        $this->backupService->method('createBackup')->willReturn('/backups/backup-123');

        // Generate only Contact entity
        $result = $this->orchestrator->generate('Contact');

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['entity_count']);
    }

    public function testGenerateWithDryRun(): void
    {
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [$this->getTestEntityArray()],
            'properties' => []
        ]);

        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => true,
            'errors' => []
        ]);

        // Backup should NOT be created in dry-run mode
        $this->backupService->expects($this->never())->method('createBackup');

        $result = $this->orchestrator->generate(null, true);

        $this->assertTrue($result['success']);
        $this->assertNull($result['backup_dir']);
    }

    public function testGenerateWithValidationErrors(): void
    {
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [$this->getTestEntityArray()],
            'properties' => []
        ]);

        // Mock validation failure
        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => false,
            'errors' => ['Invalid entity name', 'Missing required property']
        ]);

        $result = $this->orchestrator->generate();

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('CSV validation failed', $result['errors'][0]);
    }

    public function testGenerateWithEmptyEntityList(): void
    {
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [],
            'properties' => []
        ]);

        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => true,
            'errors' => []
        ]);

        $result = $this->orchestrator->generate();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No entities to generate', $result['errors'][0]);
    }

    public function testGenerateRollsBackOnError(): void
    {
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [$this->getTestEntityArray()],
            'properties' => []
        ]);

        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => true,
            'errors' => []
        ]);

        $backupDir = '/backups/backup-123';
        $this->backupService->method('createBackup')->willReturn($backupDir);

        // Mock a generator throwing exception
        $entityGenerator = $this->createMock(EntityGenerator::class);
        $entityGenerator->method('generate')->willThrowException(
            new \RuntimeException('Generation error')
        );

        $orchestrator = new GeneratorOrchestrator(
            '/test/project',
            $this->csvParser,
            $this->csvValidator,
            $this->backupService,
            $entityGenerator, // Use the failing generator
            $this->createMock(ApiPlatformGenerator::class),
            $this->createMock(RepositoryGenerator::class),
            $this->createMock(ControllerGenerator::class),
            $this->createMock(VoterGenerator::class),
            $this->createMock(FormGenerator::class),
            $this->createMock(TemplateGenerator::class),
            $this->createMock(NavigationGenerator::class),
            $this->createMock(TranslationGenerator::class),
            $this->createMock(EntityTestGenerator::class),
            $this->createMock(RepositoryTestGenerator::class),
            $this->createMock(ControllerTestGenerator::class),
            $this->createMock(VoterTestGenerator::class),
            $this->logger
        );

        // Expect rollback to be called
        $this->backupService->expects($this->once())
            ->method('restoreBackup')
            ->with($backupDir);

        $result = $orchestrator->generate();

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testGenerateWithFilteredEntityNotFound(): void
    {
        $this->csvParser->method('parseAll')->willReturn([
            'entities' => [$this->getTestEntityArray('Contact')],
            'properties' => []
        ]);

        $this->csvValidator->method('validateAll')->willReturn([
            'valid' => true,
            'errors' => []
        ]);

        // Try to generate non-existent entity
        $result = $this->orchestrator->generate('NonExistentEntity');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No entities to generate', $result['errors'][0]);
    }

    private function getTestEntityArray(string $name = 'Contact'): array
    {
        return [
            'entityName' => $name,
            'entityLabel' => $name,
            'pluralLabel' => $name . 's',
            'icon' => 'bi-person',
            'description' => 'Test entity',
            'hasOrganization' => true,
            'apiEnabled' => true,
            'operations' => ['get', 'post', 'put', 'delete'],
            'security' => 'is_granted("ROLE_USER")',
            'normalizationContext' => 'read',
            'denormalizationContext' => 'write',
            'paginationEnabled' => true,
            'itemsPerPage' => 25,
            'order' => ['id' => 'DESC'],
            'searchableFields' => ['name', 'email'],
            'filterableFields' => ['name'],
            'voterEnabled' => true,
            'voterAttributes' => ['VIEW', 'EDIT', 'DELETE'],
            'formTheme' => 'bootstrap_5_layout.html.twig',
            'indexTemplate' => '',
            'formTemplate' => '',
            'showTemplate' => '',
            'menuGroup' => 'CRM',
            'menuOrder' => 10,
            'testEnabled' => true,
            'properties' => [],
        ];
    }
}
