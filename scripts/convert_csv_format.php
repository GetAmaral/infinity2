#!/usr/bin/env php
<?php

/**
 * CSV Format Converter
 *
 * Converts CSVs to new optimized format:
 * 1. Remove 'indexed' column from PropertyNew.csv
 * 2. Convert booleans: true/false → 1/empty
 * 3. Keep legacy support in parser
 */

$propertyFile = __DIR__ . '/../app/config/PropertyNew.csv';
$entityFile = __DIR__ . '/../app/config/EntityNew.csv';

echo "🔧 Converting CSV files to new format...\n\n";

// ============================================================================
// Part 1: Remove 'indexed' column from PropertyNew.csv
// ============================================================================

echo "📄 Processing PropertyNew.csv...\n";

if (!file_exists($propertyFile)) {
    die("❌ PropertyNew.csv not found at: $propertyFile\n");
}

// Backup original
$backupFile = $propertyFile . '.backup_' . date('Y-m-d_His');
copy($propertyFile, $backupFile);
echo "  ✅ Backup created: " . basename($backupFile) . "\n";

$handle = fopen($propertyFile, 'r');
$tempFile = $propertyFile . '.tmp';
$tempHandle = fopen($tempFile, 'w');

$lineNum = 0;
$indexedColumnIndex = null;

while (($row = fgetcsv($handle)) !== false) {
    $lineNum++;

    // First line: header
    if ($lineNum === 1) {
        // Find 'indexed' column
        $indexedColumnIndex = array_search('indexed', $row);
        if ($indexedColumnIndex === false) {
            fclose($handle);
            fclose($tempHandle);
            unlink($tempFile);
            die("❌ 'indexed' column not found in header\n");
        }

        // Remove 'indexed' column
        array_splice($row, $indexedColumnIndex, 1);
        echo "  ✅ Removed 'indexed' column (position $indexedColumnIndex)\n";

        // Write new header
        fputcsv($tempHandle, $row);
        continue;
    }

    // Data rows: remove column at same index
    if ($indexedColumnIndex !== null) {
        array_splice($row, $indexedColumnIndex, 1);
    }

    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);

// Replace original with temp
rename($tempFile, $propertyFile);
echo "  ✅ PropertyNew.csv updated (column removed)\n";

// ============================================================================
// Part 2: Convert boolean values in PropertyNew.csv
// ============================================================================

echo "\n📊 Converting PropertyNew.csv booleans...\n";

$handle = fopen($propertyFile, 'r');
$tempFile = $propertyFile . '.tmp';
$tempHandle = fopen($tempFile, 'w');

$lineNum = 0;
$headers = null;
$booleanColumns = [
    'nullable', 'unique', 'orphanRemoval', 'formRequired', 'formReadOnly',
    'showInList', 'showInDetail', 'showInForm', 'sortable', 'searchable',
    'filterable', 'apiReadable', 'apiWritable'
];

while (($row = fgetcsv($handle)) !== false) {
    $lineNum++;

    if ($lineNum === 1) {
        $headers = $row;
        fputcsv($tempHandle, $row);
        continue;
    }

    // Convert boolean columns
    foreach ($booleanColumns as $colName) {
        $colIndex = array_search($colName, $headers);
        if ($colIndex !== false && isset($row[$colIndex])) {
            $value = trim(strtolower($row[$colIndex]));
            // Convert: true/yes/1 → 1, false/no/empty → empty
            if (in_array($value, ['true', 'yes', '1'])) {
                $row[$colIndex] = '1';
            } else {
                $row[$colIndex] = '';
            }
        }
    }

    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);
rename($tempFile, $propertyFile);

echo "  ✅ Converted " . count($booleanColumns) . " boolean columns\n";
echo "  ✅ Pattern: true → 1, false → empty\n";

// ============================================================================
// Part 3: Convert boolean values in EntityNew.csv
// ============================================================================

echo "\n📊 Converting EntityNew.csv booleans...\n";

// Backup entity file
$entityBackup = $entityFile . '.backup_' . date('Y-m-d_His');
copy($entityFile, $entityBackup);
echo "  ✅ Backup created: " . basename($entityBackup) . "\n";

$handle = fopen($entityFile, 'r');
$tempFile = $entityFile . '.tmp';
$tempHandle = fopen($tempFile, 'w');

$lineNum = 0;
$headers = null;
$entityBooleanColumns = [
    'hasOrganization', 'apiEnabled', 'paginationEnabled', 'voterEnabled', 'testEnabled'
];

while (($row = fgetcsv($handle)) !== false) {
    $lineNum++;

    if ($lineNum === 1) {
        $headers = $row;
        fputcsv($tempHandle, $row);
        continue;
    }

    // Convert boolean columns
    foreach ($entityBooleanColumns as $colName) {
        $colIndex = array_search($colName, $headers);
        if ($colIndex !== false && isset($row[$colIndex])) {
            $value = trim(strtolower($row[$colIndex]));
            if (in_array($value, ['true', 'yes', '1'])) {
                $row[$colIndex] = '1';
            } else {
                $row[$colIndex] = '';
            }
        }
    }

    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);
rename($tempFile, $entityFile);

echo "  ✅ Converted " . count($entityBooleanColumns) . " boolean columns\n";

// ============================================================================
// Summary
// ============================================================================

echo "\n✨ Conversion complete!\n\n";
echo "📝 Summary:\n";
echo "  • PropertyNew.csv: 'indexed' column removed\n";
echo "  • PropertyNew.csv: 13 boolean columns converted (true → 1, false → empty)\n";
echo "  • EntityNew.csv: 5 boolean columns converted (true → 1, false → empty)\n";
echo "  • Backups created with timestamp suffix\n\n";
echo "💾 Token savings estimate: ~15-20% reduction in CSV size\n\n";
echo "✅ Ready to run generator!\n";
echo "   Run: php bin/console app:generate-from-csv\n\n";
