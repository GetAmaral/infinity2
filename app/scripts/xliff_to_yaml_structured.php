<?php
/**
 * Convert XLIFF translation files to YAML format
 * Preserves structure, sections, and adds notes as comments
 */

require __DIR__ . '/../vendor/autoload.php';

function convertXliffToYamlStructured(string $xliffFile, string $yamlFile): void
{
    if (!file_exists($xliffFile)) {
        echo "Error: XLIFF file not found: $xliffFile\n";
        return;
    }

    $xmlContent = file_get_contents($xliffFile);
    if ($xmlContent === false) {
        echo "Error: Could not read XLIFF file: $xliffFile\n";
        return;
    }

    $xml = simplexml_load_string($xmlContent);
    if ($xml === false) {
        echo "Error: Could not parse XLIFF file: $xliffFile\n";
        return;
    }

    // Register XLIFF namespace
    $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

    // Generate YAML content
    $yamlContent = "# Symfony Translation File\n";
    $yamlContent .= "# Converted from XLIFF on " . date('Y-m-d H:i:s') . "\n";
    $yamlContent .= "# Format: YAML with preserved structure and sections\n\n";

    // Parse XML line by line to preserve structure and comments
    $lines = explode("\n", $xmlContent);
    $currentSection = null;
    $inBody = false;
    $translationCount = 0;

    foreach ($lines as $line) {
        $trimmedLine = trim($line);

        // Check if we're in the body section
        if (strpos($trimmedLine, '<body>') !== false) {
            $inBody = true;
            continue;
        }
        if (strpos($trimmedLine, '</body>') !== false) {
            break;
        }

        if (!$inBody) {
            continue;
        }

        // Extract XML comments (section headers)
        if (preg_match('/<!--\s*(.+?)\s*-->/', $trimmedLine, $matches)) {
            $sectionName = $matches[1];
            if ($translationCount > 0) {
                $yamlContent .= "\n";
            }
            $yamlContent .= "# ========================================\n";
            $yamlContent .= "# $sectionName\n";
            $yamlContent .= "# ========================================\n";
            $currentSection = $sectionName;
            continue;
        }

        // Extract trans-unit elements
        if (preg_match('/<trans-unit id="([^"]+)"/', $trimmedLine, $matches)) {
            $id = $matches[1];

            // Get the full trans-unit block
            $transUnitXml = '';
            $depth = 0;
            $started = false;

            foreach ($lines as $idx => $l) {
                if (strpos($l, '<trans-unit id="' . $id . '"') !== false) {
                    $started = true;
                }
                if ($started) {
                    $transUnitXml .= $l . "\n";
                    if (strpos($l, '<trans-unit') !== false) $depth++;
                    if (strpos($l, '</trans-unit>') !== false) {
                        $depth--;
                        if ($depth === 0) break;
                    }
                }
            }

            // Parse the trans-unit
            $unitXml = @simplexml_load_string($transUnitXml);
            if ($unitXml === false) {
                continue;
            }

            $target = (string)$unitXml->target;
            $note = (string)$unitXml->note;

            if (empty($target)) {
                continue;
            }

            // Add note as comment if exists
            if (!empty($note)) {
                $yamlContent .= "# $note\n";
            }

            // Format the value for YAML
            $escapedValue = $target;

            // Check if value needs quoting
            $needsQuotes = false;

            // Quote if starts with special characters
            if (preg_match('/^[:\{\}\[\],&\*#\?|\-<>=!%@`\']/', $escapedValue)) {
                $needsQuotes = true;
            }

            // Quote if contains special characters that need escaping
            if (preg_match('/[:\{\}\[\]&\*#\?|<>=!@`]/', $escapedValue)) {
                $needsQuotes = true;
            }

            // Quote if starts or ends with whitespace
            if (preg_match('/^\s/', $escapedValue) || preg_match('/\s$/', $escapedValue)) {
                $needsQuotes = true;
            }

            // Quote if contains newlines or is multiline
            if (strpos($escapedValue, "\n") !== false) {
                $needsQuotes = true;
            }

            if ($needsQuotes) {
                // Use single quotes and escape single quotes by doubling them
                $escapedValue = "'" . str_replace("'", "''", $escapedValue) . "'";
            }

            $yamlContent .= "$id: $escapedValue\n";
            $translationCount++;
        }
    }

    $yamlContent .= "\n# Total translations: $translationCount\n";

    // Write YAML file
    file_put_contents($yamlFile, $yamlContent);
    echo "✓ Converted $xliffFile -> $yamlFile ($translationCount translations)\n";
}

// Convert English translations
echo "Converting XLIFF to structured YAML format...\n\n";

convertXliffToYamlStructured(
    __DIR__ . '/../translations/messages.en.xliff',
    __DIR__ . '/../translations/messages.en.yaml'
);

convertXliffToYamlStructured(
    __DIR__ . '/../translations/messages.pt_BR.xliff',
    __DIR__ . '/../translations/messages.pt_BR.yaml'
);

echo "\n✅ YAML translation files created with preserved structure!\n";
