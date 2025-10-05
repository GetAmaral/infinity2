<?php
/**
 * Test Translation Loading - Both XLIFF and YAML formats
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;

echo "=== Translation Format Testing ===\n\n";

// Create translator
$translator = new Translator('en');

// Register loaders
$translator->addLoader('xliff', new XliffFileLoader());
$translator->addLoader('yaml', new YamlFileLoader());

// Test 1: Load XLIFF files
echo "Test 1: Loading XLIFF files...\n";
try {
    $translator->addResource('xliff', __DIR__ . '/../translations/messages.en.xliff', 'en', 'messages');
    $translator->addResource('xliff', __DIR__ . '/../translations/messages.pt_BR.xliff', 'pt_BR', 'messages');
    echo "✓ XLIFF files loaded successfully\n\n";
} catch (\Exception $e) {
    echo "✗ Error loading XLIFF: " . $e->getMessage() . "\n\n";
}

// Test 2: Load YAML files
echo "Test 2: Loading YAML files...\n";
try {
    $translator->addResource('yaml', __DIR__ . '/../translations/messages.en.yaml', 'en', 'messages');
    $translator->addResource('yaml', __DIR__ . '/../translations/messages.pt_BR.yaml', 'pt_BR', 'messages');
    echo "✓ YAML files loaded successfully\n\n";
} catch (\Exception $e) {
    echo "✗ Error loading YAML: " . $e->getMessage() . "\n\n";
}

// Test 3: Translate newly added keys
echo "Test 3: Testing newly added translations...\n";
echo "----------------------------------------\n\n";

$testKeys = [
    'status.active',
    'status.processing',
    'time.today',
    'metric.month_growth',
    'unit.minutes',
    'pagination.show',
    'nav.ai_chat',
    'branding.luminai_crm',
    'organization.switcher_title',
    'course.back_to_course',
    'theme.switch_to_dark',
    'misc.na',
];

// Test English
echo "ENGLISH TRANSLATIONS (en):\n";
$translator->setLocale('en');
foreach ($testKeys as $key) {
    $translation = $translator->trans($key, [], 'messages');
    $status = $translation === $key ? '✗ MISSING' : '✓';
    echo sprintf("  %s %-35s → %s\n", $status, $key, $translation);
}

echo "\n";

// Test Portuguese
echo "PORTUGUESE TRANSLATIONS (pt_BR):\n";
$translator->setLocale('pt_BR');
foreach ($testKeys as $key) {
    $translation = $translator->trans($key, [], 'messages');
    $status = $translation === $key ? '✗ MISSING' : '✓';
    echo sprintf("  %s %-35s → %s\n", $status, $key, $translation);
}

echo "\n";

// Test 4: Test with parameters
echo "Test 4: Testing translations with parameters...\n";
echo "------------------------------------------------\n\n";

$translator->setLocale('en');
$paramTests = [
    ['key' => 'metric.month_growth', 'params' => ['%percent%' => '12']],
    ['key' => 'metric.new_count', 'params' => ['%count%' => '3']],
    ['key' => 'course.lecture_number', 'params' => ['%number%' => '5']],
    ['key' => 'insight.last_interaction', 'params' => ['%days%' => '7']],
];

foreach ($paramTests as $test) {
    $translation = $translator->trans($test['key'], $test['params'], 'messages');
    echo "  Key: {$test['key']}\n";
    echo "  Params: " . json_encode($test['params']) . "\n";
    echo "  Result: $translation\n\n";
}

// Test 5: Count available translations
echo "Test 5: Translation Statistics...\n";
echo "-----------------------------------\n\n";

$catalogue_en = $translator->getCatalogue('en');
$catalogue_pt = $translator->getCatalogue('pt_BR');

echo "  English (en):    " . count($catalogue_en->all('messages')) . " translations\n";
echo "  Portuguese (pt_BR): " . count($catalogue_pt->all('messages')) . " translations\n\n";

// Calculate coverage
$coverage = round((count($catalogue_pt->all('messages')) / count($catalogue_en->all('messages'))) * 100, 1);
echo "  Portuguese Coverage: $coverage%\n\n";

echo "=== All Tests Complete! ===\n";
