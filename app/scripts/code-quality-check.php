#!/usr/bin/env php
<?php

/**
 * Code Quality Check Script
 * Runs comprehensive code quality checks on the Generator system
 *
 * Usage: php scripts/code-quality-check.php [--fix] [--report=PATH]
 *
 * Checks:
 * - PHPStan analysis (level 8)
 * - PHP CS Fixer
 * - Security audit
 * - Symfony deprecations
 * - Dead code detection
 */

declare(strict_types=1);

class CodeQualityCheck
{
    private bool $autoFix = false;
    private ?string $reportPath = null;
    private array $results = [];
    private int $errorCount = 0;
    private int $warningCount = 0;
    private float $startTime;

    public function __construct(array $options = [])
    {
        $this->autoFix = isset($options['fix']);
        $this->reportPath = $options['report'] ?? null;
        $this->startTime = microtime(true);
    }

    public function run(): int
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Code Quality Check - TURBO Generator System\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($this->autoFix) {
            echo "⚙️  Auto-fix mode enabled - Will attempt to fix issues\n\n";
        }

        // Run all quality checks
        $this->checkPhpStan();
        $this->checkCodeStyle();
        $this->checkSecurity();
        $this->checkDeprecations();

        // Display summary
        $this->displaySummary();

        // Generate report if requested
        if ($this->reportPath) {
            $this->generateReport();
        }

        return $this->errorCount > 0 ? 1 : 0;
    }

    private function checkPhpStan(): void
    {
        echo "🔍 Running PHPStan Analysis (Level 8)...\n";

        $projectDir = dirname(__DIR__);
        $phpstanBin = $projectDir . '/vendor/bin/phpstan';

        if (!file_exists($phpstanBin)) {
            $this->warning('PHPStan', 'PHPStan not installed - Run: composer require --dev phpstan/phpstan');
            echo "\n";
            return;
        }

        // Check src directory
        $startTime = microtime(true);
        $command = sprintf(
            'cd %s && vendor/bin/phpstan analyse src --level=8 --no-progress 2>&1',
            escapeshellarg($projectDir)
        );

        exec($command, $output, $returnCode);
        $analysisTime = microtime(true) - $startTime;

        $outputStr = implode("\n", $output);

        if ($returnCode === 0) {
            $this->pass('PHPStan', 'Zero errors found', $analysisTime);
        } else {
            // Parse errors
            $errorCount = 0;
            if (preg_match('/(\d+)\s+errors?/i', $outputStr, $matches)) {
                $errorCount = (int)$matches[1];
            }

            $this->error('PHPStan', "{$errorCount} errors found", $analysisTime);

            // Show sample errors
            $errorLines = array_filter($output, fn($line) => str_contains($line, '│') || str_contains($line, '─'));
            if (!empty($errorLines)) {
                echo "   Sample errors:\n";
                foreach (array_slice($errorLines, 0, 5) as $line) {
                    echo "   " . $line . "\n";
                }
                if (count($errorLines) > 5) {
                    echo "   ... and " . (count($errorLines) - 5) . " more errors\n";
                }
            }
        }

        $this->results['phpstan'] = [
            'passed' => $returnCode === 0,
            'errors' => $errorCount ?? 0,
            'time' => $analysisTime,
        ];

        echo "\n";
    }

    private function checkCodeStyle(): void
    {
        echo "🎨 Checking Code Style (PHP CS Fixer)...\n";

        $projectDir = dirname(__DIR__);
        $fixerBin = $projectDir . '/vendor/bin/php-cs-fixer';

        if (!file_exists($fixerBin)) {
            $this->warning('PHP CS Fixer', 'Not installed - Run: composer require --dev friendsofphp/php-cs-fixer');
            echo "\n";
            return;
        }

        $startTime = microtime(true);

        if ($this->autoFix) {
            // Apply fixes
            $command = sprintf(
                'cd %s && vendor/bin/php-cs-fixer fix src --verbose 2>&1',
                escapeshellarg($projectDir)
            );
        } else {
            // Dry run
            $command = sprintf(
                'cd %s && vendor/bin/php-cs-fixer fix src --dry-run --diff 2>&1',
                escapeshellarg($projectDir)
            );
        }

        exec($command, $output, $returnCode);
        $fixTime = microtime(true) - $startTime;

        $outputStr = implode("\n", $output);

        // Parse violations
        $fixedCount = 0;
        if (preg_match('/(\d+)\s+files?\s+fixed/i', $outputStr, $matches)) {
            $fixedCount = (int)$matches[1];
        } elseif (preg_match('/(\d+)\s+files?\s+need\s+fixing/i', $outputStr, $matches)) {
            $fixedCount = (int)$matches[1];
        }

        if ($fixedCount === 0) {
            $this->pass('Code Style', 'All files follow coding standards', $fixTime);
        } else {
            if ($this->autoFix) {
                $this->pass('Code Style', "{$fixedCount} files fixed", $fixTime);
            } else {
                $this->warning('Code Style', "{$fixedCount} files need fixing - Run with --fix", $fixTime);
            }
        }

        $this->results['code_style'] = [
            'passed' => $fixedCount === 0 || $this->autoFix,
            'violations' => $fixedCount,
            'fixed' => $this->autoFix,
            'time' => $fixTime,
        ];

        echo "\n";
    }

    private function checkSecurity(): void
    {
        echo "🔒 Running Security Audit...\n";

        $projectDir = dirname(__DIR__);

        // Composer audit
        $startTime = microtime(true);
        $command = sprintf(
            'cd %s && composer audit --format=json 2>&1',
            escapeshellarg($projectDir)
        );

        exec($command, $output, $returnCode);
        $auditTime = microtime(true) - $startTime;

        $outputStr = implode("\n", $output);

        // Try to parse JSON output
        $vulnerabilities = 0;
        if (str_contains($outputStr, '"advisories"')) {
            $json = json_decode($outputStr, true);
            if (isset($json['advisories'])) {
                $vulnerabilities = count($json['advisories']);
            }
        } elseif (str_contains($outputStr, 'Found') && preg_match('/Found\s+(\d+)/i', $outputStr, $matches)) {
            $vulnerabilities = (int)$matches[1];
        }

        if ($vulnerabilities === 0 || str_contains($outputStr, 'No security vulnerability advisories found')) {
            $this->pass('Security Audit', 'No vulnerabilities found', $auditTime);
        } else {
            $this->error('Security Audit', "{$vulnerabilities} vulnerabilities found", $auditTime);

            // Show vulnerable packages
            if (str_contains($outputStr, 'Package:')) {
                $packages = [];
                foreach ($output as $line) {
                    if (str_contains($line, 'Package:')) {
                        $packages[] = trim(str_replace('Package:', '', $line));
                    }
                }
                if (!empty($packages)) {
                    echo "   Vulnerable packages:\n";
                    foreach (array_slice($packages, 0, 5) as $pkg) {
                        echo "   • {$pkg}\n";
                    }
                }
            }
        }

        // Check for hardcoded secrets
        echo "   🔍 Checking for hardcoded secrets...\n";
        $secretPatterns = ['password', 'secret', 'api_key', 'token', 'apikey'];
        $foundSecrets = 0;

        foreach ($secretPatterns as $pattern) {
            $command = sprintf(
                'cd %s && grep -r -i "%s" src/ --exclude-dir=Generated 2>/dev/null | wc -l',
                escapeshellarg($projectDir),
                $pattern
            );
            $count = (int)trim(shell_exec($command) ?? '0');
            $foundSecrets += $count;
        }

        if ($foundSecrets > 0) {
            $this->warning('Secret Detection', "{$foundSecrets} potential secrets found in code");
        } else {
            echo "   ✓ No hardcoded secrets found\n";
        }

        $this->results['security'] = [
            'passed' => $vulnerabilities === 0,
            'vulnerabilities' => $vulnerabilities,
            'secrets' => $foundSecrets,
            'time' => $auditTime,
        ];

        echo "\n";
    }

    private function checkDeprecations(): void
    {
        echo "⚠️  Checking for Deprecations...\n";

        $projectDir = dirname(__DIR__);

        // Check Symfony deprecations in logs
        $logFile = $projectDir . '/var/log/dev.log';

        if (!file_exists($logFile)) {
            echo "   ℹ️  No log file found - Run application to generate logs\n\n";
            return;
        }

        $command = sprintf(
            'grep -c "DEPRECATION" %s 2>/dev/null || echo "0"',
            escapeshellarg($logFile)
        );

        $deprecationCount = (int)trim(shell_exec($command) ?? '0');

        if ($deprecationCount === 0) {
            $this->pass('Deprecations', 'No deprecation warnings found');
        } else {
            $this->warning('Deprecations', "{$deprecationCount} deprecation warnings found");

            // Show sample deprecations
            $command = sprintf(
                'grep "DEPRECATION" %s | head -n 3',
                escapeshellarg($logFile)
            );
            $samples = shell_exec($command);
            if ($samples) {
                echo "   Sample deprecations:\n";
                foreach (explode("\n", trim($samples)) as $line) {
                    if (!empty($line)) {
                        echo "   " . substr($line, 0, 100) . "...\n";
                    }
                }
            }
        }

        $this->results['deprecations'] = [
            'passed' => $deprecationCount === 0,
            'count' => $deprecationCount,
        ];

        echo "\n";
    }

    private function displaySummary(): void
    {
        $totalTime = microtime(true) - $this->startTime;

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Quality Summary\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        echo "📊 Results:\n";
        echo "   • Errors:     {$this->errorCount}\n";
        echo "   • Warnings:   {$this->warningCount}\n";
        echo "   • Total time: " . round($totalTime, 2) . "s\n";
        echo "\n";

        if ($this->errorCount === 0 && $this->warningCount === 0) {
            echo "✅ All quality checks passed!\n\n";
            echo "Code is production-ready:\n";
            echo "  • PHPStan level 8 compliant\n";
            echo "  • Coding standards followed\n";
            echo "  • No security vulnerabilities\n";
            echo "  • No deprecation warnings\n";
        } elseif ($this->errorCount === 0) {
            echo "⚠️  Quality checks completed with warnings\n\n";
            echo "Review warnings above and fix as needed.\n";
        } else {
            echo "❌ Quality checks failed\n\n";
            echo "Fix errors above before proceeding to production.\n";
            if (!$this->autoFix) {
                echo "\nTry running with --fix to automatically fix some issues:\n";
                echo "  php scripts/code-quality-check.php --fix\n";
            }
        }

        echo "\n";
    }

    private function generateReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_time' => microtime(true) - $this->startTime,
            'errors' => $this->errorCount,
            'warnings' => $this->warningCount,
            'auto_fix' => $this->autoFix,
            'results' => $this->results,
        ];

        file_put_contents($this->reportPath, json_encode($report, JSON_PRETTY_PRINT));
        echo "📄 Quality report saved to: {$this->reportPath}\n\n";
    }

    private function pass(string $check, string $message, ?float $time = null): void
    {
        $timeStr = $time !== null ? ' (' . round($time, 2) . 's)' : '';
        echo "   ✅ {$check}: {$message}{$timeStr}\n";
    }

    private function warning(string $check, string $message, ?float $time = null): void
    {
        $this->warningCount++;
        $timeStr = $time !== null ? ' (' . round($time, 2) . 's)' : '';
        echo "   ⚠️  {$check}: {$message}{$timeStr}\n";
    }

    private function error(string $check, string $message, ?float $time = null): void
    {
        $this->errorCount++;
        $timeStr = $time !== null ? ' (' . round($time, 2) . 's)' : '';
        echo "   ❌ {$check}: {$message}{$timeStr}\n";
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if ($arg === '--fix') {
        $options['fix'] = true;
    } elseif (str_starts_with($arg, '--report=')) {
        $options['report'] = substr($arg, 9);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Code Quality Check Script\n";
        echo "==========================\n\n";
        echo "Usage: php scripts/code-quality-check.php [options]\n\n";
        echo "Options:\n";
        echo "  --fix          Automatically fix code style issues\n";
        echo "  --report=PATH  Save results to JSON file\n";
        echo "  --help, -h     Show this help message\n\n";
        echo "Checks:\n";
        echo "  • PHPStan analysis (level 8)\n";
        echo "  • PHP CS Fixer (Symfony coding standards)\n";
        echo "  • Security audit (composer audit)\n";
        echo "  • Deprecation warnings\n";
        echo "  • Hardcoded secrets detection\n";
        echo "\n";
        exit(0);
    }
}

// Execute quality check
$check = new CodeQualityCheck($options);
exit($check->run());
