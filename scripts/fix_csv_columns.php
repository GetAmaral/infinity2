#!/usr/bin/env php
<?php

/**
 * Fix CSV Column Count Issues
 *
 * Ensures all rows have exactly 41 columns to match header
 */

$file = __DIR__ . '/../config/PropertyNew.csv';

if (!file_exists($file)) {
    die("❌ File not found: $file\n");
}

// Backup
$backup = $file . '.backup_' . date('YmdHis');
copy($file, $backup);
echo "✅ Backup created: " . basename($backup) . "\n\n";

$handle = fopen($file, 'r');
$temp = $file . '.tmp';
$tempHandle = fopen($temp, 'w');

$lineNum = 0;
$fixed = 0;
$header = null;
$expectedCols = 41;

while (($row = fgetcsv($handle)) !== false) {
    $lineNum++;

    if ($lineNum === 1) {
        $header = $row;
        $expectedCols = count($header);
        fputcsv($tempHandle, $row);
        echo "Header: $expectedCols columns\n\n";
        continue;
    }

    $actualCols = count($row);

    if ($actualCols !== $expectedCols) {
        echo "Line $lineNum: {$row[0]},{$row[1]} - $actualCols columns (expected $expectedCols)\n";

        // Pad with empty strings if too few
        while (count($row) < $expectedCols) {
            $row[] = '';
            $fixed++;
        }

        // Trim if too many (shouldn't happen)
        if (count($row) > $expectedCols) {
            $row = array_slice($row, 0, $expectedCols);
        }

        echo "  → Fixed to $expectedCols columns\n";
    }

    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);

rename($temp, $file);

echo "\n✅ Fixed $fixed column issues\n";
echo "✅ File normalized: all rows now have $expectedCols columns\n";
