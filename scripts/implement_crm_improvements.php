#!/usr/bin/env php
<?php

/**
 * Implement ALL recommendations from CRM_DATABASE_IMPROVEMENTS.md
 *
 * Section 3.1 - Critical Indexes:
 * 1. ALL organization FK → composite with createdAt
 * 2. ALL email fields → simple (user wants non-unique)
 * 3. ALL name fields → simple
 * 4. ALL owner/manager FK → composite with organization
 * 5. Status & stage filtering → composites
 * 6. Date-based queries → composites
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

echo "📋 Implementing CRM_DATABASE_IMPROVEMENTS.md Recommendations\n";
echo "═══════════════════════════════════════════════════════════\n\n";

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
    $propertyType = $data['propertyType'];
    $relationshipType = $data['relationshipType'];
    $changed = false;

    // RULE 1: ALL organization FK → composite with createdAt (Section 3.1 line 266-275)
    if ($property === 'organization' && $relationshipType === 'ManyToOne') {
        if ($data['indexType'] !== 'composite' || $data['compositeIndexWith'] !== 'createdAt') {
            echo sprintf("Line %d: %s.organization → composite,createdAt (MULTI-TENANT)\n", $lineNum, $entity);
            $data['indexType'] = 'composite';
            $data['compositeIndexWith'] = 'createdAt';
            $changed = true;
        }
    }

    // RULE 2: ALL email fields → simple (user wants non-unique) (Section 3.1 line 284-289)
    if ($property === 'email' && in_array($propertyType, ['string'], true)) {
        if ($data['indexType'] !== 'simple') {
            echo sprintf("Line %d: %s.email → simple (EMAIL LOOKUP)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 3: ALL name fields → simple (Section 3.1 line 294-301)
    if ($property === 'name' && in_array($propertyType, ['string', 'text'], true)) {
        if ($data['indexType'] !== 'simple' && $data['indexType'] !== 'composite') {
            echo sprintf("Line %d: %s.name → simple (NAME SEARCH)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 4: ALL owner/manager FK → composite with organization (Section 3.1 line 307-312)
    if (in_array($property, ['accountManager', 'manager', 'owner'], true) && $relationshipType === 'ManyToOne') {
        if ($data['indexType'] !== 'composite' || !str_contains($data['compositeIndexWith'], 'organization')) {
            echo sprintf("Line %d: %s.%s → composite,organization (MY ITEMS)\n", $lineNum, $entity, $property);
            $data['indexType'] = 'composite';
            $data['compositeIndexWith'] = 'organization';
            $changed = true;
        }
    }

    // RULE 5: User FK → composite with organization (for "My Tasks", etc)
    if ($property === 'user' && $relationshipType === 'ManyToOne' && !in_array($entity, ['Organization'], true)) {
        // Only if not already indexed differently
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.user → composite,organization (USER ITEMS)\n", $lineNum, $entity);
            $data['indexType'] = 'composite';
            $data['compositeIndexWith'] = 'organization';
            $changed = true;
        }
    }

    // RULE 6: currentStage → simple (Section 3.1 line 316-323)
    if ($property === 'currentStage' && $relationshipType === 'ManyToOne') {
        if ($data['indexType'] !== 'simple') {
            echo sprintf("Line %d: %s.currentStage → simple (STAGE FILTERING)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 7: startDateTime → simple for events (Section 3.1 line 331)
    if ($property === 'startDateTime' && in_array($propertyType, ['datetime', 'datetime_immutable'], true)) {
        if ($data['indexType'] !== 'simple') {
            echo sprintf("Line %d: %s.startDateTime → simple (DATE FILTERING)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 8: company FK → simple (for filtering by company)
    if ($property === 'company' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.company → simple (COMPANY FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 9: contact FK → simple (for filtering by contact)
    if ($property === 'contact' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.contact → simple (CONTACT FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 10: deal FK → simple (for filtering by deal)
    if ($property === 'deal' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.deal → simple (DEAL FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 11: calendar FK → simple
    if ($property === 'calendar' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.calendar → simple (CALENDAR FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 12: event FK → simple
    if ($property === 'event' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.event → simple (EVENT FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 13: type FK → simple (for filtering by type)
    if ($property === 'type' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.type → simple (TYPE FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    // RULE 14: category FK → simple (for filtering by category)
    if ($property === 'category' && $relationshipType === 'ManyToOne') {
        if (empty($data['indexType'])) {
            echo sprintf("Line %d: %s.category → simple (CATEGORY FILTER)\n", $lineNum, $entity);
            $data['indexType'] = 'simple';
            $data['compositeIndexWith'] = '';
            $changed = true;
        }
    }

    if ($changed) {
        $updated++;
    }

    // Write row back
    $row = array_values($data);
    fputcsv($tempHandle, $row);
}

fclose($handle);
fclose($tempHandle);

rename($temp, $file);

echo "\n✅ Updated $updated properties with indexes\n";
echo "\n📊 Implementation Summary:\n";
echo "  ✓ Multi-tenant isolation: ALL organization FK → composite,createdAt\n";
echo "  ✓ Email lookups: ALL email fields → simple\n";
echo "  ✓ Name searches: ALL name fields → simple\n";
echo "  ✓ Owner lookups: ALL manager/owner FK → composite,organization\n";
echo "  ✓ Relationship filters: ALL common FK → simple\n";
echo "  ✓ Date filtering: Date fields → simple\n";
echo "  ✓ Status filtering: Already done in previous script\n";
echo "\n🎯 Following CRM_DATABASE_IMPROVEMENTS.md Section 3.1\n";
echo "\n🔧 Next Steps:\n";
echo "  1. Review changes: git diff config/PropertyNew.csv\n";
echo "  2. Test generator: php bin/console app:generate-from-csv --dry-run\n";
echo "  3. Generate entities: php bin/console app:generate-from-csv\n";
echo "  4. Create migration: php bin/console make:migration\n";
