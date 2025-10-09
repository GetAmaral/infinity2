#!/usr/bin/env php
<?php

/**
 * Apply Section 4 Recommendations from CRM_DATABASE_IMPROVEMENTS.md
 *
 * Section 4.2: Update fetch strategies to EXTRA_LAZY for large collections
 * Section 4.3: Add cascade operations
 * Section 4.4: Add orderBy to collections
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

echo "📋 Applying Section 4: Relationship Improvements\n";
echo "═════════════════════════════════════════════════\n\n";

// Collections that should be EXTRA_LAZY (Section 4.2 lines 429-437)
$extraLazyCollections = [
    'Organization' => ['users', 'contacts', 'deals', 'campaigns', 'tasks', 'products', 'events'],
    'Company' => ['contacts', 'deals'],
    'User' => ['tasks'],
    'Contact' => ['deals', 'tasks'],
    'Deal' => ['talks', 'tasks'],
    'Talk' => ['talkMessages'],
    'Campaign' => ['contacts', 'companies', 'deals'],
];

// Cascade operations (Section 4.3 lines 455-477)
$cascadeRules = [
    // Child entities - cascade persist+remove, orphanRemoval=true
    'Contact' => [
        'socialMedias' => 'persist,remove',
        'flags' => 'persist,remove',
        'talks' => 'persist',
    ],
    'Company' => [
        'socialMedias' => 'persist,remove',
        'flags' => 'persist,remove',
    ],
    'Deal' => [
        'dealStages' => 'persist,remove',
        'tasks' => 'persist',
        'talks' => 'persist',
    ],
    'Talk' => [
        'talkMessages' => 'persist,remove',
    ],
    'Campaign' => [
        'contacts' => 'persist',
        'companies' => 'persist',
        'deals' => 'persist',
    ],
];

// OrderBy for collections (Section 4.4 lines 486-496)
$orderByRules = [
    'Organization' => [
        'users' => '{"createdAt": "asc"}',
        'contacts' => '{"createdAt": "desc"}',
        'deals' => '{"createdAt": "desc"}',
    ],
    'Contact' => [
        'talks' => '{"createdAt": "desc"}',
        'tasks' => '{"dueDate": "asc"}',
    ],
    'Deal' => [
        'dealStages' => '{"lastUpdatedAt": "desc"}',
        'tasks' => '{"scheduledDate": "asc"}',
    ],
    'Talk' => [
        'talkMessages' => '{"createdAt": "asc"}',
    ],
    'Task' => [
        'tasks' => '{"dueDate": "asc"}',
    ],
    'Campaign' => [
        'contacts' => '{"createdAt": "desc"}',
        'companies' => '{"createdAt": "desc"}',
    ],
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
    $relationshipType = $data['relationshipType'];
    $changed = false;

    // RULE 1: Update fetch strategy to EXTRA_LAZY
    if ($relationshipType === 'OneToMany' || $relationshipType === 'ManyToMany') {
        if (isset($extraLazyCollections[$entity]) && in_array($property, $extraLazyCollections[$entity], true)) {
            if ($data['fetch'] !== 'EXTRA_LAZY') {
                echo sprintf("Line %d: %s.%s → fetch=EXTRA_LAZY\n", $lineNum, $entity, $property);
                $data['fetch'] = 'EXTRA_LAZY';
                $changed = true;
            }
        }
    }

    // RULE 2: Add cascade operations
    if (isset($cascadeRules[$entity][$property])) {
        $cascade = $cascadeRules[$entity][$property];
        if ($data['cascade'] !== $cascade) {
            echo sprintf("Line %d: %s.%s → cascade=%s\n", $lineNum, $entity, $property, $cascade);
            $data['cascade'] = $cascade;

            // Set orphanRemoval=true if cascade includes remove
            if (str_contains($cascade, 'remove')) {
                $data['orphanRemoval'] = '1';
            }
            $changed = true;
        }
    }

    // RULE 3: Add orderBy to collections
    if (isset($orderByRules[$entity][$property])) {
        $orderBy = $orderByRules[$entity][$property];
        if (empty($data['orderBy']) || $data['orderBy'] !== $orderBy) {
            echo sprintf("Line %d: %s.%s → orderBy=%s\n", $lineNum, $entity, $property, $orderBy);
            $data['orderBy'] = $orderBy;
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
echo "\n📊 Section 4 Implementation Complete:\n";
echo "  ✓ Fetch strategies updated to EXTRA_LAZY\n";
echo "  ✓ Cascade operations added\n";
echo "  ✓ OrderBy added to collections\n";
