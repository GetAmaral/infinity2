#!/usr/bin/env php
<?php

/**
 * Pre-Generation Verification Script
 * Verifies system is ready for bulk code generation
 *
 * Usage: php scripts/pre-generation-check.php [--fix]
 *
 * Checks:
 * - CSV files validity
 * - Backup system functionality
 * - Generator services availability
 * - Test environment readiness
 * - Git repository status
 * - Disk space
 * - Database connectivity
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\CsvValidatorService;
use App\Service\BackupService;
use Psr\Log\NullLogger;

class PreGenerationCheck
{
    private bool $autoFix = false;
    private int $errorCount = 0;
    private int $warningCount = 0;
    private int $checkCount = 0;
    private array $issues = [];

    public function __construct(array $options = [])
    {
        $this->autoFix = isset($options['fix']);
    }

    public function run(): int
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Pre-Generation Verification - TURBO Generator System\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($this->autoFix) {
            echo "⚙️  Auto-fix mode enabled - Will attempt to fix issues\n\n";
        }

        // Run all checks
        $this->checkCsvFiles();
        $this->checkBackupSystem();
        $this->checkGeneratorServices();
        $this->checkTestEnvironment();
        $this->checkGitRepository();
        $this->checkDiskSpace();
        $this->checkDatabaseConnection();
        $this->checkPhpExtensions();
        $this->checkDirectories();

        // Display summary
        $this->displaySummary();

        return $this->errorCount > 0 ? 1 : 0;
    }

    private function checkCsvFiles(): void
    {
        echo "📋 Checking CSV Files...\n";

        $projectDir = dirname(__DIR__);
        $entityCsv = $projectDir . '/config/EntityNew.csv';
        $propertyCsv = $projectDir . '/config/PropertyNew.csv';

        // Check files exist
        if (!file_exists($entityCsv)) {
            $this->error('Entity CSV not found', $entityCsv);
            return;
        }
        $this->check('Entity CSV exists');

        if (!file_exists($propertyCsv)) {
            $this->error('Property CSV not found', $propertyCsv);
            return;
        }
        $this->check('Property CSV exists');

        // Parse and validate
        try {
            $parser = new CsvParserService($projectDir);
            $result = $parser->parseAll();

            $entityCount = count($result['entities']);
            $propertyCount = 0;
            foreach ($result['properties'] as $props) {
                $propertyCount += count($props);
            }

            $this->check("Parsed {$entityCount} entities and {$propertyCount} properties");

            // Validate
            $validator = new CsvValidatorService(new NullLogger());
            $validation = $validator->validateAll($result['entities'], $result['properties']);

            if ($validation['valid']) {
                $this->check('CSV validation passed');
            } else {
                $this->error('CSV validation failed', implode(', ', array_slice($validation['errors'], 0, 3)));
                if (count($validation['errors']) > 3) {
                    $this->issues[] = '... and ' . (count($validation['errors']) - 3) . ' more errors';
                }
            }

        } catch (\Throwable $e) {
            $this->error('CSV parsing failed', $e->getMessage());
        }

        echo "\n";
    }

    private function checkBackupSystem(): void
    {
        echo "💾 Checking Backup System...\n";

        $projectDir = dirname(__DIR__);
        $backupDir = $projectDir . '/var/generatorBackup';

        // Check backup directory exists
        if (!is_dir($backupDir)) {
            if ($this->autoFix) {
                mkdir($backupDir, 0755, true);
                $this->check('Created backup directory', true);
            } else {
                $this->warning('Backup directory does not exist', 'Run with --fix to create');
            }
        } else {
            $this->check('Backup directory exists');
        }

        // Check writable
        if (is_dir($backupDir) && !is_writable($backupDir)) {
            if ($this->autoFix) {
                chmod($backupDir, 0755);
                $this->check('Fixed backup directory permissions', true);
            } else {
                $this->error('Backup directory not writable', 'Run with --fix');
            }
        } else if (is_dir($backupDir)) {
            $this->check('Backup directory is writable');
        }

        // Test backup service
        try {
            $backupService = new BackupService($backupDir);
            $this->check('BackupService instantiated');
        } catch (\Throwable $e) {
            $this->error('BackupService failed', $e->getMessage());
        }

        echo "\n";
    }

    private function checkGeneratorServices(): void
    {
        echo "🔧 Checking Generator Services...\n";

        $services = [
            'EntityGenerator',
            'ApiPlatformGenerator',
            'RepositoryGenerator',
            'ControllerGenerator',
            'VoterGenerator',
            'FormGenerator',
            'TemplateGenerator',
            'NavigationGenerator',
            'TranslationGenerator',
            'EntityTestGenerator',
            'RepositoryTestGenerator',
            'ControllerTestGenerator',
            'VoterTestGenerator',
        ];

        foreach ($services as $service) {
            $class = "App\\Service\\Generator\\" . $this->getServiceNamespace($service) . "\\{$service}";
            if (class_exists($class)) {
                $this->check("{$service} class exists");
            } else {
                $this->error("{$service} class not found", $class);
            }
        }

        echo "\n";
    }

    private function getServiceNamespace(string $service): string
    {
        if (str_contains($service, 'Test')) {
            return 'Test';
        }
        if (str_contains($service, 'Entity')) {
            return 'Entity';
        }
        if (str_contains($service, 'ApiPlatform')) {
            return 'ApiPlatform';
        }
        if (str_contains($service, 'Repository')) {
            return 'Repository';
        }
        if (str_contains($service, 'Controller')) {
            return 'Controller';
        }
        if (str_contains($service, 'Voter')) {
            return 'Voter';
        }
        if (str_contains($service, 'Form')) {
            return 'Form';
        }
        if (str_contains($service, 'Template')) {
            return 'Template';
        }
        if (str_contains($service, 'Navigation')) {
            return 'Navigation';
        }
        if (str_contains($service, 'Translation')) {
            return 'Translation';
        }
        return '';
    }

    private function checkTestEnvironment(): void
    {
        echo "🧪 Checking Test Environment...\n";

        // Check PHPUnit
        $phpunitPath = dirname(__DIR__) . '/bin/phpunit';
        if (file_exists($phpunitPath)) {
            $this->check('PHPUnit binary exists');
        } else {
            $this->error('PHPUnit binary not found', $phpunitPath);
        }

        // Check test directories
        $testDirs = ['tests/Entity', 'tests/Repository', 'tests/Controller', 'tests/Security/Voter'];
        foreach ($testDirs as $dir) {
            $fullPath = dirname(__DIR__) . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->check("{$dir}/ directory exists");
            } else {
                if ($this->autoFix) {
                    mkdir($fullPath, 0755, true);
                    $this->check("Created {$dir}/ directory", true);
                } else {
                    $this->warning("{$dir}/ directory missing", 'Run with --fix to create');
                }
            }
        }

        echo "\n";
    }

    private function checkGitRepository(): void
    {
        echo "📦 Checking Git Repository...\n";

        $projectDir = dirname(__DIR__);
        $gitDir = $projectDir . '/.git';

        if (!is_dir($gitDir)) {
            $this->warning('Not a git repository', 'Consider initializing git');
            echo "\n";
            return;
        }

        $this->check('Git repository initialized');

        // Check for uncommitted changes
        exec('cd ' . escapeshellarg($projectDir) . ' && git status --porcelain 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $changes = implode("\n", $output);
            if (empty($changes)) {
                $this->check('Working directory clean');
            } else {
                $changeCount = count($output);
                $this->warning("Working directory has {$changeCount} uncommitted changes", 'Consider committing before generation');
            }
        }

        echo "\n";
    }

    private function checkDiskSpace(): void
    {
        echo "💽 Checking Disk Space...\n";

        $projectDir = dirname(__DIR__);
        $diskFree = disk_free_space($projectDir);
        $diskTotal = disk_total_space($projectDir);

        $freeGB = round($diskFree / 1024 / 1024 / 1024, 2);
        $totalGB = round($diskTotal / 1024 / 1024 / 1024, 2);
        $usedPercent = round((1 - $diskFree / $diskTotal) * 100, 1);

        $this->check("Disk: {$freeGB}GB free / {$totalGB}GB total ({$usedPercent}% used)");

        // Warn if less than 1GB free
        if ($freeGB < 1) {
            $this->error('Low disk space', 'Less than 1GB free');
        } else if ($freeGB < 5) {
            $this->warning('Disk space getting low', "{$freeGB}GB remaining");
        }

        echo "\n";
    }

    private function checkDatabaseConnection(): void
    {
        echo "🗄️  Checking Database Connection...\n";

        try {
            $dsn = getenv('DATABASE_URL');
            if (!$dsn) {
                $this->warning('DATABASE_URL not set', 'Database checks skipped');
                echo "\n";
                return;
            }

            // Parse DSN to get connection details
            if (preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dsn, $matches)) {
                $host = $matches[3];
                $port = $matches[4];
                $dbname = $matches[5];

                // Try to connect
                $pdo = new \PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $matches[1], $matches[2]);
                $this->check('Database connection successful');

                // Check PostgreSQL version
                $version = $pdo->query('SELECT version()')->fetchColumn();
                if (preg_match('/PostgreSQL ([\d.]+)/', $version, $versionMatch)) {
                    $this->check("PostgreSQL version: {$versionMatch[1]}");
                }

            } else {
                $this->warning('Could not parse DATABASE_URL', 'Database checks skipped');
            }

        } catch (\Throwable $e) {
            $this->error('Database connection failed', $e->getMessage());
        }

        echo "\n";
    }

    private function checkPhpExtensions(): void
    {
        echo "🐘 Checking PHP Extensions...\n";

        $required = ['pdo', 'pdo_pgsql', 'mbstring', 'intl', 'opcache', 'zip'];
        $missing = [];

        foreach ($required as $ext) {
            if (extension_loaded($ext)) {
                $this->check("Extension '{$ext}' loaded");
            } else {
                $missing[] = $ext;
                $this->error("Extension '{$ext}' not loaded", 'Install php-' . $ext);
            }
        }

        echo "\n";
    }

    private function checkDirectories(): void
    {
        echo "📁 Checking Required Directories...\n";

        $projectDir = dirname(__DIR__);
        $directories = [
            'src/Entity/Generated',
            'src/Entity/Trait',
            'src/Repository/Generated',
            'src/Controller/Generated',
            'src/Security/Voter/Generated',
            'src/Form/Generated',
            'config/api_platform',
            'templates/generator',
            'var/generatorBackup',
        ];

        foreach ($directories as $dir) {
            $fullPath = $projectDir . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->check("{$dir}/ exists");
            } else {
                if ($this->autoFix) {
                    mkdir($fullPath, 0755, true);
                    $this->check("Created {$dir}/", true);
                } else {
                    $this->warning("{$dir}/ missing", 'Run with --fix to create');
                }
            }
        }

        echo "\n";
    }

    private function displaySummary(): void
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Verification Summary\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📊 Results:\n";
        echo "   • Checks passed:  {$this->checkCount}\n";
        echo "   • Warnings:       {$this->warningCount}\n";
        echo "   • Errors:         {$this->errorCount}\n";
        echo "\n";

        if ($this->errorCount === 0 && $this->warningCount === 0) {
            echo "✅ System is ready for bulk generation!\n\n";
            echo "Next steps:\n";
            echo "  php bin/console app:generate-from-csv --dry-run\n";
            echo "  php bin/console app:generate-from-csv\n";
        } else if ($this->errorCount === 0) {
            echo "⚠️  System is ready with warnings\n\n";
            echo "Review warnings above before proceeding.\n";
        } else {
            echo "❌ System is NOT ready - Fix errors before generation\n\n";
            if (!$this->autoFix) {
                echo "Try running with --fix to auto-fix some issues:\n";
                echo "  php scripts/pre-generation-check.php --fix\n";
            }
        }
        echo "\n";
    }

    private function check(string $message, bool $fixed = false): void
    {
        $this->checkCount++;
        $icon = $fixed ? '🔧' : '✓';
        echo "   {$icon} {$message}\n";
    }

    private function warning(string $message, string $detail = ''): void
    {
        $this->warningCount++;
        echo "   ⚠️  WARNING: {$message}\n";
        if ($detail) {
            echo "      → {$detail}\n";
        }
    }

    private function error(string $message, string $detail = ''): void
    {
        $this->errorCount++;
        echo "   ✗ ERROR: {$message}\n";
        if ($detail) {
            echo "      → {$detail}\n";
        }
        $this->issues[] = $message;
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg === '--fix') {
        $options['fix'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Pre-Generation Verification Script\n";
        echo "===================================\n\n";
        echo "Usage: php scripts/pre-generation-check.php [options]\n\n";
        echo "Options:\n";
        echo "  --fix      Attempt to automatically fix issues\n";
        echo "  --help, -h Show this help message\n\n";
        echo "Verifies:\n";
        echo "  • CSV files validity\n";
        echo "  • Backup system functionality\n";
        echo "  • Generator services availability\n";
        echo "  • Test environment readiness\n";
        echo "  • Git repository status\n";
        echo "  • Disk space\n";
        echo "  • Database connectivity\n";
        echo "  • PHP extensions\n";
        echo "  • Required directories\n";
        echo "\n";
        exit(0);
    }
}

// Execute verification
$check = new PreGenerationCheck($options);
exit($check->run());
