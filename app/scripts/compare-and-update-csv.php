<?php

declare(strict_types=1);

/**
 * Compare Generated CSV with Existing Codebase Entities
 *
 * Scans existing Entity classes and updates CSV files to match actual implementation
 */

$srcPath = '/home/user/inf/app/src/Entity';
$entityNewPath = '/home/user/inf/app/config/EntityNew.csv';
$propertyNewPath = '/home/user/inf/app/config/PropertyNew.csv';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  Comparing CSV with Existing Codebase\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Step 1: Scan existing entities
echo "📂 Scanning existing entities...\n";
$existingEntities = scanExistingEntities($srcPath);
echo "   Found " . count($existingEntities) . " existing entities\n\n";

// Step 2: Load CSV entities
echo "📋 Loading CSV entities...\n";
$csvEntities = loadCsvEntities($entityNewPath);
echo "   Found " . count($csvEntities) . " CSV entities\n\n";

// Step 3: Load CSV properties
echo "📋 Loading CSV properties...\n";
$csvProperties = loadCsvProperties($propertyNewPath);
echo "   Found " . count($csvProperties) . " CSV properties\n\n";

// Step 4: Map entity names (old CSV name → actual class name)
echo "🔍 Mapping entity names...\n";
$nameMapping = [
    'Module' => 'CourseModule',
    'Lecture' => 'CourseLecture',
    'UserCourse' => 'StudentCourse',
    'UserLecture' => 'StudentLecture',
];

$mappedCount = 0;
foreach ($nameMapping as $oldName => $newName) {
    if (isset($csvEntities[$oldName])) {
        $csvEntities[$newName] = $csvEntities[$oldName];
        $csvEntities[$newName]['entityName'] = $newName;
        unset($csvEntities[$oldName]);

        // Update properties
        if (isset($csvProperties[$oldName])) {
            $csvProperties[$newName] = $csvProperties[$oldName];
            foreach ($csvProperties[$newName] as &$prop) {
                $prop['entityName'] = $newName;
            }
            unset($csvProperties[$oldName]);
        }

        echo "   ✓ Mapped: $oldName → $newName\n";
        $mappedCount++;
    }
}
echo "   Mapped $mappedCount entity names\n\n";

// Step 5: Compare and report
echo "📊 Comparison Results:\n\n";

$existsInBoth = [];
$existsInCodeOnly = [];
$existsInCsvOnly = [];

foreach ($existingEntities as $className => $classInfo) {
    if (isset($csvEntities[$className])) {
        $existsInBoth[] = $className;
    } else {
        $existsInCodeOnly[] = $className;
    }
}

foreach ($csvEntities as $entityName => $entityInfo) {
    if (!isset($existingEntities[$entityName])) {
        $existsInCsvOnly[] = $entityName;
    }
}

echo "✅ Exists in both (already implemented): " . count($existsInBoth) . "\n";
foreach ($existsInBoth as $name) {
    echo "   • $name\n";
}

echo "\n⚠️  Exists in codebase but not in CSV: " . count($existsInCodeOnly) . "\n";
foreach ($existsInCodeOnly as $name) {
    echo "   • $name\n";
}

echo "\n🆕 Exists in CSV but not in codebase (will be generated): " . count($existsInCsvOnly) . "\n";
$preview = array_slice($existsInCsvOnly, 0, 10);
foreach ($preview as $name) {
    echo "   • $name\n";
}
if (count($existsInCsvOnly) > 10) {
    echo "   ... and " . (count($existsInCsvOnly) - 10) . " more\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📝 Summary:\n";
echo "   • Total existing entities: " . count($existingEntities) . "\n";
echo "   • Total CSV entities: " . count($csvEntities) . "\n";
echo "   • Already implemented: " . count($existsInBoth) . "\n";
echo "   • To be generated: " . count($existsInCsvOnly) . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Step 6: Write updated CSV files
echo "💾 Writing updated CSV files...\n";

$fpEntity = fopen($entityNewPath, 'w');
$firstEntity = true;
foreach ($csvEntities as $entity) {
    if ($firstEntity) {
        fputcsv($fpEntity, array_keys($entity));
        $firstEntity = false;
    }
    fputcsv($fpEntity, $entity);
}
fclose($fpEntity);
echo "   ✓ Updated: $entityNewPath\n";

$fpProperty = fopen($propertyNewPath, 'w');
$firstProperty = true;
foreach ($csvProperties as $entityProps) {
    foreach ($entityProps as $property) {
        if ($firstProperty) {
            fputcsv($fpProperty, array_keys($property));
            $firstProperty = false;
        }
        fputcsv($fpProperty, $property);
    }
}
fclose($fpProperty);
echo "   ✓ Updated: $propertyNewPath\n\n";

echo "✅ Comparison and update complete!\n\n";
echo "Next steps:\n";
echo "1. Review the updated CSV files\n";
echo "2. Entities marked as 'already implemented' will be skipped or updated\n";
echo "3. Entities marked as 'to be generated' will be created\n";
echo "4. Run: php bin/console app:generate-from-csv --dry-run\n\n";

// Helper functions

function scanExistingEntities(string $srcPath): array
{
    $entities = [];
    $files = glob($srcPath . '/*.php');

    foreach ($files as $file) {
        $className = basename($file, '.php');

        // Skip base class and special entities
        if ($className === 'EntityBase') {
            continue;
        }

        $content = file_get_contents($file);

        // Extract properties
        $properties = [];
        preg_match_all('/#\[ORM\\\\Column\((.*?)\]\s+(?:protected|private)\s+\??(\w+)\s+\$(\w+)/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $properties[] = [
                'name' => $match[3],
                'type' => $match[2],
                'attributes' => $match[1],
            ];
        }

        // Extract relationships
        preg_match_all('/#\[ORM\\\\(ManyToOne|OneToMany|ManyToMany|OneToOne)\((.*?)\]\s+(?:protected|private)\s+.*?\$(\w+)/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $properties[] = [
                'name' => $match[3],
                'type' => $match[1],
                'attributes' => $match[2],
            ];
        }

        $entities[$className] = [
            'file' => $file,
            'properties' => $properties,
        ];
    }

    return $entities;
}

function loadCsvEntities(string $path): array
{
    $entities = [];
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        $entity = array_combine($header, $row);
        $entities[$entity['entityName']] = $entity;
    }

    fclose($handle);
    return $entities;
}

function loadCsvProperties(string $path): array
{
    $properties = [];
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        $property = array_combine($header, $row);
        $entityName = $property['entityName'];

        if (!isset($properties[$entityName])) {
            $properties[$entityName] = [];
        }

        $properties[$entityName][] = $property;
    }

    fclose($handle);
    return $properties;
}
