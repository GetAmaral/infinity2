<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\GenerateFromCsvCommand;
use App\Service\GeneratorOrchestrator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateFromCsvCommandTest extends TestCase
{
    private GeneratorOrchestrator&MockObject $orchestrator;
    private GenerateFromCsvCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->orchestrator = $this->createMock(GeneratorOrchestrator::class);
        $this->command = new GenerateFromCsvCommand($this->orchestrator);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandNameAndDescription(): void
    {
        $this->assertEquals('app:generate-from-csv', $this->command->getName());
        $this->assertEquals(
            'Generate complete CRUD code from CSV definition files',
            $this->command->getDescription()
        );
    }

    public function testExecuteWithDryRunSuccess(): void
    {
        $this->orchestrator->method('generate')->willReturn([
            'success' => true,
            'generated_files' => [
                'src/Entity/ContactGenerated.php',
                'src/Controller/ContactControllerGenerated.php',
            ],
            'backup_dir' => null,
            'errors' => [],
            'entity_count' => 1,
        ]);

        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('DRY RUN MODE', $output);
        $this->assertStringContainsString('DRY RUN completed', $output);
        $this->assertStringContainsString('2 files', $output);
        $this->assertStringContainsString('1 entities', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithEntityFilterDryRun(): void
    {
        $this->orchestrator->expects($this->once())
            ->method('generate')
            ->with('Contact', true)
            ->willReturn([
                'success' => true,
                'generated_files' => ['src/Entity/ContactGenerated.php'],
                'backup_dir' => null,
                'errors' => [],
                'entity_count' => 1,
            ]);

        $this->commandTester->execute([
            '--entity' => 'Contact',
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Generating code for entity: Contact', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteSuccessWithBackup(): void
    {
        $this->orchestrator->method('generate')->willReturn([
            'success' => true,
            'generated_files' => ['src/Entity/ContactGenerated.php'],
            'backup_dir' => '/var/backups/backup-20250101-120000',
            'errors' => [],
            'entity_count' => 1,
        ]);

        $this->commandTester->execute(
            ['--dry-run' => true], // Use dry-run to skip confirmation prompt
        );

        $output = $this->commandTester->getDisplay();

        // In dry-run mode, different message is shown
        $this->assertStringContainsString('DRY RUN completed', $output);
        $this->assertStringContainsString('Statistics', $output);
        $this->assertStringContainsString('Entities Processed', $output);
        $this->assertStringContainsString('Files Generated', $output);
        // Next Steps is NOT shown in dry-run mode
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithGenerationFailure(): void
    {
        $this->orchestrator->method('generate')->willReturn([
            'success' => false,
            'generated_files' => [],
            'backup_dir' => '/var/backups/backup-20250101-120000',
            'errors' => ['CSV validation failed', 'Invalid entity name'],
            'entity_count' => 0,
        ]);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Generation failed', $output);
        $this->assertStringContainsString('CSV validation failed', $output);
        $this->assertStringContainsString('Invalid entity name', $output);
        $this->assertStringContainsString('Changes have been rolled back', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithException(): void
    {
        $this->orchestrator->method('generate')->willThrowException(
            new \RuntimeException('Unexpected error occurred')
        );

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Generation failed with exception', $output);
        $this->assertStringContainsString('Unexpected error occurred', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithAllEntitiesInDryRun(): void
    {
        $this->orchestrator->expects($this->once())
            ->method('generate')
            ->with(null, true)
            ->willReturn([
                'success' => true,
                'generated_files' => array_fill(0, 15, 'test.php'),
                'backup_dir' => null,
                'errors' => [],
                'entity_count' => 3,
            ]);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Generating code for ALL entities from CSV', $output);
        $this->assertStringContainsString('15 files', $output);
        $this->assertStringContainsString('3 entities', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testStatisticsTableDisplayed(): void
    {
        $this->orchestrator->method('generate')->willReturn([
            'success' => true,
            'generated_files' => ['file1.php', 'file2.php', 'file3.php'],
            'backup_dir' => null,
            'errors' => [],
            'entity_count' => 2,
        ]);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Statistics', $output);
        $this->assertStringContainsString('Entities Processed', $output);
        $this->assertStringContainsString('2', $output);
        $this->assertStringContainsString('Files Generated', $output);
        $this->assertStringContainsString('3', $output);
    }

    public function testNextStepsNotDisplayedInDryRun(): void
    {
        $this->orchestrator->method('generate')->willReturn([
            'success' => true,
            'generated_files' => ['file.php'],
            'backup_dir' => null,
            'errors' => [],
            'entity_count' => 1,
        ]);

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();

        // Next Steps should NOT be shown in dry-run mode
        $this->assertStringNotContainsString('Next Steps', $output);
        $this->assertStringContainsString('DRY RUN completed', $output);
    }

    public function testNextStepsDisplayedWithoutDryRun(): void
    {
        $this->orchestrator->method('generate')->willReturn([
            'success' => true,
            'generated_files' => ['file.php'],
            'backup_dir' => '/backups/backup-123',
            'errors' => [],
            'entity_count' => 1,
        ]);

        // Set interactive input to answer 'yes' to confirmation
        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        // Next Steps SHOULD be shown in non-dry-run mode
        $this->assertStringContainsString('Next Steps', $output);
        $this->assertStringContainsString('doctrine:migrations:migrate', $output);
        $this->assertStringContainsString('cache:clear', $output);
        $this->assertStringContainsString('php bin/phpunit', $output);
    }
}
