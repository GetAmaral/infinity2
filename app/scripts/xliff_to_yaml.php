<?php
/**
 * Convert XLIFF translation files to YAML format
 */

require __DIR__ . '/../vendor/autoload.php';

function convertXliffToYaml(string $xliffFile, string $yamlFile): void
{
    if (!file_exists($xliffFile)) {
        echo "Error: XLIFF file not found: $xliffFile\n";
        return;
    }

    $xml = simplexml_load_file($xliffFile);
    if ($xml === false) {
        echo "Error: Could not parse XLIFF file: $xliffFile\n";
        return;
    }

    $translations = [];

    // Register XLIFF namespace
    $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

    // Extract all trans-unit elements
    $transUnits = $xml->xpath('//xliff:trans-unit');

    foreach ($transUnits as $unit) {
        $id = (string)$unit['id'];
        $target = (string)$unit->target;

        if (!empty($id) && !empty($target)) {
            $translations[$id] = $target;
        }
    }

    // Sort by key for better readability
    ksort($translations);

    // Generate YAML content
    $yamlContent = "# Symfony Translation File\n";
    $yamlContent .= "# Generated from XLIFF on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($translations as $key => $value) {
        // Escape special YAML characters in the value
        $escapedValue = $value;

        // If value contains special characters, quote it
        if (preg_match('/[:\{\}\[\],&\*#\?|\-<>=!%@`\']/', $value) ||
            preg_match('/^\s/', $value) ||
            preg_match('/\s$/', $value)) {
            $escapedValue = "'" . str_replace("'", "''", $value) . "'";
        }

        $yamlContent .= "$key: $escapedValue\n";
    }

    // Write YAML file
    file_put_contents($yamlFile, $yamlContent);
    echo "✓ Converted $xliffFile -> $yamlFile (" . count($translations) . " translations)\n";
}

// Convert English translations
convertXliffToYaml(
    __DIR__ . '/../translations/messages.en.xliff',
    __DIR__ . '/../translations/messages.en.yaml'
);

// Convert Portuguese translations
convertXliffToYaml(
    __DIR__ . '/../translations/messages.pt_BR.xliff',
    __DIR__ . '/../translations/messages.pt_BR.yaml'
);

echo "\n✅ YAML translation files created successfully!\n";
