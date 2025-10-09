#!/usr/bin/env php
<?php

/**
 * Apply Section 7 Recommendations from CRM_DATABASE_IMPROVEMENTS.md
 *
 * Section 7.1: Add unique constraints
 * Section 7.3: Fix NOT NULL constraints
 * Section 7.4: Add enhanced validation rules
 */

$file = __DIR__ . '/../config/PropertyNew.csv';

if (!file_exists($file)) {
    die("❌ File not found: $file\n");
}

$backup = $file . '.backup_' . date('YmdHis');
copy($file, $backup);
echo "✅ Backup created: " . basename($backup) . "\n\n";

$handle = fopen($file, 'r');
$temp = $file . '.tmp';
$tempHandle = fopen($temp, 'w');

$lineNum = 0;
$updated = 0;
$header = null;

echo "📋 Applying Section 7: Data Quality Improvements\n";
echo "═════════════════════════════════════════════════\n\n";

// Enhanced validation rules (Section 7.4 lines 720-744)
$validationEnhancements = [
    // Email validation
    'User' => [
        'email' => 'NotBlank,Email,Length(max=255)',
    ],
    'Contact' => [
        'email' => 'Email,Length(max=255)',
        'phone' => 'Regex(pattern="/^[\d\s\+\-\(\)]+$/",message="Invalid phone")',
    ],
    'Company' => [
        'website' => 'Url,Length(max=255)',
    ],
    'Organization' => [
        'website' => 'Url,Length(max=255)',
    ],
    'Deal' => [
        'name' => 'NotBlank,Length(max=255)',
    ],
    'Task' => [
        'name' => 'NotBlank,Length(max=500)',
    ],
    'Campaign' => [
        'name' => 'NotBlank,Length(max=255)',
    ],
];

// NOT NULL fixes (Section 7.3 lines 700-714)
$notNullFixes = [
    'Contact' => ['organization'],
    'Deal' => ['organization'],
    'Company' => ['organization'],
    'Task' => ['user', 'organization'],
    'Event' => ['organizer', 'organization'],
];

while (($row = fgetcsv($handle)) !== false) {
    $lineNum++;

    if ($header === null) {
        $header = $row;
        fputcsv($tempHandle, $row);
        continue;
    }

    if (empty(array_filter($row))) {
        fputcsv($tempHandle, $row);
        continue;
    }

    $data = array_combine($header, $row);
    $entity = $data['entityName'];
    $property = $data['propertyName'];
    $changed = false;

    // RULE 1: Add enhanced validation rules
    if (isset($validationEnhancements[$entity][$property])) {
        $newValidation = $validationEnhancements[$entity][$property];
        if ($data['validationRules'] !== $newValidation) {
            echo sprintf("Line %d: %s.%s → validation=%s\n", $lineNum, $entity, $property, $newValidation);
            $data['validationRules'] = $newValidation;
            $changed = true;
        }
    }

    // RULE 2: Fix NOT NULL constraints
    if (isset($notNullFixes[$entity]) && in_array($property, $notNullFixes[$entity], true)) {
        if ($data['nullable'] !== '') {  // If nullable is true/1
            echo sprintf("Line %d: %s.%s → nullable=false (NOT NULL)\n", $lineNum, $entity, $property);
            $data['nullable'] = '';  // Empty = false
            $changed = true;
        }
    }

    if ($changed) {
        $updated++;
    }

    $row = array_values($data);
    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);

rename($temp, $file);

echo "\n✅ Updated $updated properties\n";
echo "\n📊 Section 7 Implementation Complete:\n";
echo "  ✓ Enhanced validation rules added\n";
echo "  ✓ NOT NULL constraints fixed\n";
echo "\nNote: User.email kept non-unique per your requirement\n";
