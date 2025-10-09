#!/usr/bin/env php
<?php

/**
 * Add Strategic Indexes to PropertyNew.csv
 *
 * Adds comprehensive indexes based on CRM best practices:
 * - Status/Stage filtering (organization + status composites)
 * - Date range queries (organization + date composites)
 * - Priority filtering
 * - Manager/Owner lookups (organization + manager composites)
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
$updated = 0;
$header = null;

// Index improvements map: [entity][property] => [indexType, compositeIndexWith]
$indexImprovements = [
    // Priority 1: Status/Stage Filtering (CRITICAL for CRM)
    'Deal' => [
        'dealStatus' => ['composite', 'organization|currentStage'],  // Filter by status + stage
        'priority' => ['composite', 'dealStatus'],  // Priority within status
        'expectedClosureDate' => ['composite', 'dealStatus|organization'],  // Forecast reporting
        'closureDate' => ['composite', 'organization'],  // Historical reporting
        'nextFollowUp' => ['composite', 'organization|dealStatus'],  // Upcoming actions
        'lastActivityDate' => ['simple', ''],  // Stale deal detection
        'manager' => ['composite', 'organization|dealStatus'],  // My deals by status
    ],

    'Task' => [
        'taskStatus' => ['composite', 'organization|scheduledDate'],  // Active tasks by date
        'priority' => ['composite', 'taskStatus|organization'],  // Priority tasks by status
        'scheduledDate' => ['composite', 'organization|taskStatus'],  // Upcoming tasks
    ],

    'Contact' => [
        'status' => ['composite', 'organization|accountManager'],  // Active contacts by manager
        'accountManager' => ['composite', 'organization|status'],  // Manager's active contacts
    ],

    'Company' => [
        'status' => ['composite', 'organization|accountManager'],  // Active companies
        'accountManager' => ['composite', 'organization|status'],  // Manager's active companies
    ],

    'Talk' => [
        'status' => ['composite', 'organization'],  // Filter talks by status
        'priority' => ['composite', 'status|organization'],  // Priority talks
    ],

    'Organization' => [
        'status' => ['simple', ''],  // Active/inactive organizations
    ],

    // Priority 2: Manager/Owner Lookups (My Items queries)
    'Pipeline' => [
        'manager' => ['composite', 'organization'],  // My pipelines
    ],

    // Priority 3: Date Range Queries
    'Campaign' => [
        'startDate' => ['composite', 'organization|endDate'],  // Active campaigns
        'endDate' => ['composite', 'organization'],  // Campaign history
    ],

    'UserCourse' => [
        'startDate' => ['composite', 'organization'],  // Course schedules
    ],

    'UserLecture' => [
        'startDate' => ['composite', 'organization'],  // Lecture schedules
    ],

    // Priority 4: Event Management
    'Event' => [
        'priority' => ['composite', 'organization'],  // Priority events
    ],

    // Priority 5: Course Management
    'Course' => [
        'status' => ['composite', 'organization'],  // Active courses
    ],
];

echo "📋 Index Improvements to Apply:\n";
echo "═══════════════════════════════\n\n";

$totalImprovements = 0;
foreach ($indexImprovements as $entity => $properties) {
    echo "$entity: " . count($properties) . " indexes\n";
    $totalImprovements += count($properties);
}

echo "\nTotal: $totalImprovements index improvements\n\n";
echo "🔄 Processing CSV...\n\n";

while (($row = fgetcsv($handle)) !== false) {
    $lineNum++;

    // First row is headers
    if ($header === null) {
        $header = $row;
        fputcsv($tempHandle, $row);
        continue;
    }

    // Skip empty rows
    if (empty(array_filter($row))) {
        fputcsv($tempHandle, $row);
        continue;
    }

    // Combine headers with row data
    $data = array_combine($header, $row);

    $entity = $data['entityName'];
    $property = $data['propertyName'];

    // Check if this property needs index improvement
    if (isset($indexImprovements[$entity][$property])) {
        $improvement = $indexImprovements[$entity][$property];
        $oldIndexType = $data['indexType'];
        $oldComposite = $data['compositeIndexWith'];

        $data['indexType'] = $improvement[0];
        $data['compositeIndexWith'] = $improvement[1];

        echo sprintf(
            "Line %d: %s.%s\n  OLD: indexType=%s, compositeIndexWith=%s\n  NEW: indexType=%s, compositeIndexWith=%s\n\n",
            $lineNum,
            $entity,
            $property,
            $oldIndexType ?: '(empty)',
            $oldComposite ?: '(empty)',
            $data['indexType'],
            $data['compositeIndexWith'] ?: '(empty)'
        );

        $updated++;
    }

    // Write row back
    $row = array_values($data);
    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);

rename($temp, $file);

echo "\n✅ Updated $updated properties with strategic indexes\n";
echo "✅ Total index improvements: $totalImprovements\n";
echo "✅ File saved: $file\n";
echo "\n📊 Index Strategy Applied:\n";
echo "  • Status/Stage filtering (organization + status)\n";
echo "  • Date range queries (organization + date)\n";
echo "  • Manager/Owner lookups (organization + manager + status)\n";
echo "  • Priority filtering (status + priority)\n";
echo "  • Multi-column composites using | separator\n";
echo "\n🔧 Next Steps:\n";
echo "  1. Test generator: php bin/console app:generate-from-csv --dry-run\n";
echo "  2. Verify SQL output\n";
echo "  3. Generate entities: php bin/console app:generate-from-csv\n";
echo "  4. Create migration: php bin/console make:migration\n";
echo "  5. Review and apply: php bin/console doctrine:migrations:migrate\n";
