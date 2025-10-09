#!/usr/bin/env php
<?php

/**
 * Generation Statistics Script
 * Analyzes generated code and provides comprehensive statistics
 *
 * Usage: php scripts/generation-stats.php [--format=text|json|markdown] [--output=PATH]
 *
 * Statistics:
 * - Total entities and files generated
 * - Breakdown by file type
 * - Lines of code metrics
 * - Test coverage
 * - API Platform configurations
 * - Multi-tenant entities
 * - Performance metrics
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Generator\Csv\CsvParserService;

class GenerationStatistics
{
    private string $format = 'text';
    private ?string $outputPath = null;
    private array $stats = [];
    private string $projectDir;

    public function __construct(array $options = [])
    {
        $this->format = $options['format'] ?? 'text';
        $this->outputPath = $options['output'] ?? null;
        $this->projectDir = dirname(__DIR__);
    }

    public function run(): int
    {
        try {
            // Collect all statistics
            $this->collectEntityStats();
            $this->collectFileStats();
            $this->collectCodeStats();
            $this->collectTestStats();
            $this->collectApiStats();
            $this->collectConfigurationStats();

            // Display or save results
            $output = $this->formatOutput();

            if ($this->outputPath) {
                file_put_contents($this->outputPath, $output);
                echo "📄 Statistics saved to: {$this->outputPath}\n";
            } else {
                echo $output;
            }

            return 0;

        } catch (\Throwable $e) {
            echo "❌ Error generating statistics: {$e->getMessage()}\n";
            return 1;
        }
    }

    private function collectEntityStats(): void
    {
        // Parse CSV to get entity information
        $parser = new CsvParserService($this->projectDir);
        $result = $parser->parseAll();

        $this->stats['entities'] = [
            'total' => count($result['entities']),
            'api_enabled' => 0,
            'voter_enabled' => 0,
            'test_enabled' => 0,
            'multi_tenant' => 0,
        ];

        $this->stats['properties'] = [
            'total' => 0,
            'relationships' => 0,
            'searchable' => 0,
            'sortable' => 0,
            'nullable' => 0,
            'unique' => 0,
        ];

        foreach ($result['entities'] as $entity) {
            if (($entity['apiEnabled'] ?? 'false') === 'true') {
                $this->stats['entities']['api_enabled']++;
            }
            if (($entity['voterEnabled'] ?? 'false') === 'true') {
                $this->stats['entities']['voter_enabled']++;
            }
            if (($entity['testEnabled'] ?? 'false') === 'true') {
                $this->stats['entities']['test_enabled']++;
            }
            if (($entity['hasOrganization'] ?? 'false') === 'true') {
                $this->stats['entities']['multi_tenant']++;
            }
        }

        // Count properties
        foreach ($result['properties'] as $entityName => $properties) {
            foreach ($properties as $property) {
                $this->stats['properties']['total']++;

                if (!empty($property['relationshipType'])) {
                    $this->stats['properties']['relationships']++;
                }
                if (($property['searchable'] ?? 'false') === 'true') {
                    $this->stats['properties']['searchable']++;
                }
                if (($property['sortable'] ?? 'false') === 'true') {
                    $this->stats['properties']['sortable']++;
                }
                if (($property['nullable'] ?? 'false') === 'true') {
                    $this->stats['properties']['nullable']++;
                }
                if (($property['unique'] ?? 'false') === 'true') {
                    $this->stats['properties']['unique']++;
                }
            }
        }

        $this->stats['properties']['avg_per_entity'] = $this->stats['entities']['total'] > 0
            ? round($this->stats['properties']['total'] / $this->stats['entities']['total'], 1)
            : 0;
    }

    private function collectFileStats(): void
    {
        $this->stats['files'] = [
            'entities_generated' => $this->countFiles('src/Entity/Generated', '.php'),
            'entities_extension' => $this->countFiles('src/Entity', '.php', ['Generated']),
            'repositories_generated' => $this->countFiles('src/Repository/Generated', '.php'),
            'repositories_extension' => $this->countFiles('src/Repository', '.php', ['Generated']),
            'controllers_generated' => $this->countFiles('src/Controller/Generated', '.php'),
            'controllers_extension' => $this->countFiles('src/Controller', '.php', ['Generated']),
            'voters_generated' => $this->countFiles('src/Security/Voter/Generated', '.php'),
            'voters_extension' => $this->countFiles('src/Security/Voter', '.php', ['Generated']),
            'forms_generated' => $this->countFiles('src/Form/Generated', '.php'),
            'forms_extension' => $this->countFiles('src/Form', '.php', ['Generated']),
            'templates' => $this->countFiles('templates', '.html.twig', ['base.html.twig', 'Generator']),
            'api_configs' => $this->countFiles('config/api_platform', '.yaml'),
            'tests' => $this->countFiles('tests', 'Test.php'),
        ];

        $this->stats['files']['total'] = array_sum($this->stats['files']);
        $this->stats['files']['generated'] = $this->stats['files']['entities_generated']
            + $this->stats['files']['repositories_generated']
            + $this->stats['files']['controllers_generated']
            + $this->stats['files']['voters_generated']
            + $this->stats['files']['forms_generated']
            + $this->stats['files']['templates']
            + $this->stats['files']['api_configs'];

        $this->stats['files']['extension'] = $this->stats['files']['entities_extension']
            + $this->stats['files']['repositories_extension']
            + $this->stats['files']['controllers_extension']
            + $this->stats['files']['voters_extension']
            + $this->stats['files']['forms_extension'];
    }

    private function collectCodeStats(): void
    {
        $this->stats['code'] = [
            'total_lines' => 0,
            'generated_lines' => 0,
            'extension_lines' => 0,
            'test_lines' => 0,
            'template_lines' => 0,
        ];

        // Count lines in generated files
        $this->stats['code']['generated_lines'] += $this->countLinesInDirectory('src/Entity/Generated');
        $this->stats['code']['generated_lines'] += $this->countLinesInDirectory('src/Repository/Generated');
        $this->stats['code']['generated_lines'] += $this->countLinesInDirectory('src/Controller/Generated');
        $this->stats['code']['generated_lines'] += $this->countLinesInDirectory('src/Security/Voter/Generated');
        $this->stats['code']['generated_lines'] += $this->countLinesInDirectory('src/Form/Generated');

        // Count lines in extension files
        $this->stats['code']['extension_lines'] += $this->countLinesInDirectory('src/Entity', ['Generated']);
        $this->stats['code']['extension_lines'] += $this->countLinesInDirectory('src/Repository', ['Generated']);
        $this->stats['code']['extension_lines'] += $this->countLinesInDirectory('src/Controller', ['Generated']);
        $this->stats['code']['extension_lines'] += $this->countLinesInDirectory('src/Security/Voter', ['Generated']);
        $this->stats['code']['extension_lines'] += $this->countLinesInDirectory('src/Form', ['Generated']);

        // Count test lines
        $this->stats['code']['test_lines'] = $this->countLinesInDirectory('tests');

        // Count template lines
        $this->stats['code']['template_lines'] = $this->countLinesInDirectory('templates', ['base.html.twig', 'Generator']);

        $this->stats['code']['total_lines'] = $this->stats['code']['generated_lines']
            + $this->stats['code']['extension_lines']
            + $this->stats['code']['test_lines']
            + $this->stats['code']['template_lines'];
    }

    private function collectTestStats(): void
    {
        $this->stats['tests'] = [
            'entity_tests' => $this->countFiles('tests/Entity', 'Test.php'),
            'repository_tests' => $this->countFiles('tests/Repository', 'Test.php'),
            'controller_tests' => $this->countFiles('tests/Controller', 'Test.php'),
            'voter_tests' => $this->countFiles('tests/Security/Voter', 'Test.php'),
        ];

        $this->stats['tests']['total'] = array_sum($this->stats['tests']);
    }

    private function collectApiStats(): void
    {
        $this->stats['api'] = [
            'configurations' => $this->countFiles('config/api_platform', '.yaml'),
            'operations' => 0,
        ];

        // Try to count operations from YAML files
        $yamlFiles = glob($this->projectDir . '/config/api_platform/*.yaml');
        foreach ($yamlFiles as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/operations:/i', $content, $matches)) {
                $this->stats['api']['operations'] += count($matches[0]);
            }
        }
    }

    private function collectConfigurationStats(): void
    {
        $this->stats['configuration'] = [
            'generator_templates' => $this->countFiles('templates/Generator', '.twig'),
            'generator_services' => $this->countFiles('src/Service/Generator', '.php'),
            'backups' => is_dir($this->projectDir . '/var/generatorBackup')
                ? count(glob($this->projectDir . '/var/generatorBackup/*'))
                : 0,
        ];
    }

    private function countFiles(string $path, string $extension = '.php', array $exclude = []): int
    {
        $fullPath = $this->projectDir . '/' . $path;
        if (!is_dir($fullPath)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath)
        );

        $count = 0;
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $extension)) {
                $skip = false;
                foreach ($exclude as $pattern) {
                    if (str_contains($file->getPathname(), $pattern)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function countLinesInDirectory(string $path, array $exclude = []): int
    {
        $fullPath = $this->projectDir . '/' . $path;
        if (!is_dir($fullPath)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath)
        );

        $totalLines = 0;
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                $skip = false;
                foreach ($exclude as $pattern) {
                    if (str_contains($file->getPathname(), $pattern)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $lines = count(file($file->getPathname()));
                    $totalLines += $lines;
                }
            }
        }

        return $totalLines;
    }

    private function formatOutput(): string
    {
        return match ($this->format) {
            'json' => $this->formatJson(),
            'markdown' => $this->formatMarkdown(),
            default => $this->formatText(),
        };
    }

    private function formatText(): string
    {
        $output = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "  TURBO Generator - Generation Statistics\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $output .= "📊 Entities\n";
        $output .= "   • Total:         {$this->stats['entities']['total']}\n";
        $output .= "   • API-enabled:   {$this->stats['entities']['api_enabled']}\n";
        $output .= "   • Voter-enabled: {$this->stats['entities']['voter_enabled']}\n";
        $output .= "   • Test-enabled:  {$this->stats['entities']['test_enabled']}\n";
        $output .= "   • Multi-tenant:  {$this->stats['entities']['multi_tenant']}\n\n";

        $output .= "🔧 Properties\n";
        $output .= "   • Total:          {$this->stats['properties']['total']}\n";
        $output .= "   • Avg per entity: {$this->stats['properties']['avg_per_entity']}\n";
        $output .= "   • Relationships:  {$this->stats['properties']['relationships']}\n";
        $output .= "   • Searchable:     {$this->stats['properties']['searchable']}\n";
        $output .= "   • Unique:         {$this->stats['properties']['unique']}\n\n";

        $output .= "📁 Files Generated\n";
        $output .= "   • Total files:    {$this->stats['files']['total']}\n";
        $output .= "   • Entities:       {$this->stats['files']['entities_generated']} + {$this->stats['files']['entities_extension']} ext\n";
        $output .= "   • Repositories:   {$this->stats['files']['repositories_generated']} + {$this->stats['files']['repositories_extension']} ext\n";
        $output .= "   • Controllers:    {$this->stats['files']['controllers_generated']} + {$this->stats['files']['controllers_extension']} ext\n";
        $output .= "   • Voters:         {$this->stats['files']['voters_generated']} + {$this->stats['files']['voters_extension']} ext\n";
        $output .= "   • Forms:          {$this->stats['files']['forms_generated']} + {$this->stats['files']['forms_extension']} ext\n";
        $output .= "   • Templates:      {$this->stats['files']['templates']}\n";
        $output .= "   • API Configs:    {$this->stats['files']['api_configs']}\n";
        $output .= "   • Tests:          {$this->stats['files']['tests']}\n\n";

        $output .= "📝 Lines of Code\n";
        $output .= "   • Total:          " . number_format($this->stats['code']['total_lines']) . "\n";
        $output .= "   • Generated:      " . number_format($this->stats['code']['generated_lines']) . "\n";
        $output .= "   • Extension:      " . number_format($this->stats['code']['extension_lines']) . "\n";
        $output .= "   • Tests:          " . number_format($this->stats['code']['test_lines']) . "\n";
        $output .= "   • Templates:      " . number_format($this->stats['code']['template_lines']) . "\n\n";

        $output .= "🧪 Tests\n";
        $output .= "   • Total:          {$this->stats['tests']['total']}\n";
        $output .= "   • Entity tests:   {$this->stats['tests']['entity_tests']}\n";
        $output .= "   • Repository:     {$this->stats['tests']['repository_tests']}\n";
        $output .= "   • Controller:     {$this->stats['tests']['controller_tests']}\n";
        $output .= "   • Voter:          {$this->stats['tests']['voter_tests']}\n\n";

        $output .= "🌐 API Platform\n";
        $output .= "   • Configurations: {$this->stats['api']['configurations']}\n";
        $output .= "   • Operations:     {$this->stats['api']['operations']}\n\n";

        $output .= "⚙️  Configuration\n";
        $output .= "   • Templates:      {$this->stats['configuration']['generator_templates']}\n";
        $output .= "   • Services:       {$this->stats['configuration']['generator_services']}\n";
        $output .= "   • Backups:        {$this->stats['configuration']['backups']}\n\n";

        return $output;
    }

    private function formatJson(): string
    {
        return json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'statistics' => $this->stats,
        ], JSON_PRETTY_PRINT);
    }

    private function formatMarkdown(): string
    {
        $output = "# TURBO Generator - Generation Statistics\n\n";
        $output .= "_Generated: " . date('Y-m-d H:i:s') . "_\n\n";

        $output .= "## Entities\n\n";
        $output .= "| Metric | Count |\n";
        $output .= "|--------|-------|\n";
        $output .= "| Total | {$this->stats['entities']['total']} |\n";
        $output .= "| API-enabled | {$this->stats['entities']['api_enabled']} |\n";
        $output .= "| Voter-enabled | {$this->stats['entities']['voter_enabled']} |\n";
        $output .= "| Test-enabled | {$this->stats['entities']['test_enabled']} |\n";
        $output .= "| Multi-tenant | {$this->stats['entities']['multi_tenant']} |\n\n";

        $output .= "## Properties\n\n";
        $output .= "| Metric | Count |\n";
        $output .= "|--------|-------|\n";
        $output .= "| Total | {$this->stats['properties']['total']} |\n";
        $output .= "| Avg per entity | {$this->stats['properties']['avg_per_entity']} |\n";
        $output .= "| Relationships | {$this->stats['properties']['relationships']} |\n";
        $output .= "| Searchable | {$this->stats['properties']['searchable']} |\n";
        $output .= "| Unique | {$this->stats['properties']['unique']} |\n\n";

        $output .= "## Files Generated\n\n";
        $output .= "| Type | Generated | Extension | Total |\n";
        $output .= "|------|-----------|-----------|-------|\n";
        $output .= "| Entities | {$this->stats['files']['entities_generated']} | {$this->stats['files']['entities_extension']} | " . ($this->stats['files']['entities_generated'] + $this->stats['files']['entities_extension']) . " |\n";
        $output .= "| Repositories | {$this->stats['files']['repositories_generated']} | {$this->stats['files']['repositories_extension']} | " . ($this->stats['files']['repositories_generated'] + $this->stats['files']['repositories_extension']) . " |\n";
        $output .= "| Controllers | {$this->stats['files']['controllers_generated']} | {$this->stats['files']['controllers_extension']} | " . ($this->stats['files']['controllers_generated'] + $this->stats['files']['controllers_extension']) . " |\n";
        $output .= "| Voters | {$this->stats['files']['voters_generated']} | {$this->stats['files']['voters_extension']} | " . ($this->stats['files']['voters_generated'] + $this->stats['files']['voters_extension']) . " |\n";
        $output .= "| Forms | {$this->stats['files']['forms_generated']} | {$this->stats['files']['forms_extension']} | " . ($this->stats['files']['forms_generated'] + $this->stats['files']['forms_extension']) . " |\n";
        $output .= "| Templates | {$this->stats['files']['templates']} | - | {$this->stats['files']['templates']} |\n";
        $output .= "| API Configs | {$this->stats['files']['api_configs']} | - | {$this->stats['files']['api_configs']} |\n";
        $output .= "| Tests | {$this->stats['files']['tests']} | - | {$this->stats['files']['tests']} |\n";
        $output .= "| **TOTAL** | **{$this->stats['files']['generated']}** | **{$this->stats['files']['extension']}** | **{$this->stats['files']['total']}** |\n\n";

        $output .= "## Lines of Code\n\n";
        $output .= "| Category | Lines |\n";
        $output .= "|----------|-------|\n";
        $output .= "| Generated | " . number_format($this->stats['code']['generated_lines']) . " |\n";
        $output .= "| Extension | " . number_format($this->stats['code']['extension_lines']) . " |\n";
        $output .= "| Tests | " . number_format($this->stats['code']['test_lines']) . " |\n";
        $output .= "| Templates | " . number_format($this->stats['code']['template_lines']) . " |\n";
        $output .= "| **TOTAL** | **" . number_format($this->stats['code']['total_lines']) . "** |\n\n";

        return $output;
    }
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (str_starts_with($arg, '--format=')) {
        $options['format'] = substr($arg, 9);
    } elseif (str_starts_with($arg, '--output=')) {
        $options['output'] = substr($arg, 9);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Generation Statistics Script\n";
        echo "============================\n\n";
        echo "Usage: php scripts/generation-stats.php [options]\n\n";
        echo "Options:\n";
        echo "  --format=FORMAT    Output format: text|json|markdown (default: text)\n";
        echo "  --output=PATH      Save output to file\n";
        echo "  --help, -h         Show this help message\n\n";
        echo "Examples:\n";
        echo "  php scripts/generation-stats.php\n";
        echo "  php scripts/generation-stats.php --format=json\n";
        echo "  php scripts/generation-stats.php --format=markdown --output=STATS.md\n";
        echo "\n";
        exit(0);
    }
}

// Execute statistics generation
$stats = new GenerationStatistics($options);
exit($stats->run());
