<?php

declare(strict_types=1);

/**
 * Fix Module entities: Keep Module AND add CourseModule separately
 */

$entityNewPath = '/home/user/inf/app/config/EntityNew.csv';
$propertyNewPath = '/home/user/inf/app/config/PropertyNew.csv';
$oldCsvPath = '/home/user/inf/config/Entity.csv';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  Fixing Module vs CourseModule\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Step 1: Re-parse old CSV to restore original Module
echo "📋 Re-parsing original Entity.csv...\n";
$handle = fopen($oldCsvPath, 'r');
$header = fgetcsv($handle, 0, ';');

$moduleProperties = [];
$currentEntity = null;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    if (empty($row[0]) && empty($row[1])) {
        continue;
    }

    $id = $row[0] ?? '';
    $entityName = $row[1] ?? '';
    $propertyName = $row[2] ?? '';
    $propertyType = $row[3] ?? '';

    // Module entity detected
    if (!empty($id) && $entityName === 'Module') {
        $currentEntity = 'Module';
        echo "   ✓ Found Module entity in original CSV\n";
    }

    // Property for Module
    if ($currentEntity === 'Module' && !empty($propertyName)) {
        $nullable = ($row[5] ?? '') === '1' ? 'true' : 'false';
        $length = $row[4] ?? '';
        $targetEntity = $row[6] ?? '';
        $inversedBy = $row[7] ?? '';

        $moduleProperties[] = [
            'entityName' => 'Module',
            'propertyName' => $propertyName,
            'propertyLabel' => ucfirst($propertyName),
            'propertyType' => mapType($propertyType),
            'nullable' => $nullable,
            'length' => $length,
            'precision' => '',
            'scale' => '',
            'unique' => 'false',
            'defaultValue' => '',
            'relationshipType' => isRelationType($propertyType) ? $propertyType : '',
            'targetEntity' => $targetEntity,
            'inversedBy' => $inversedBy,
            'mappedBy' => '',
            'cascade' => '',
            'orphanRemoval' => 'false',
            'fetch' => 'LAZY',
            'orderBy' => '',
            'validationRules' => getValidationRules($propertyType, $nullable === 'false'),
            'validationMessage' => '',
            'formType' => getFormType($propertyType),
            'formOptions' => '{}',
            'formRequired' => $nullable === 'false' ? 'true' : 'false',
            'formReadOnly' => 'false',
            'formHelp' => '',
            'showInList' => 'true',
            'showInDetail' => 'true',
            'showInForm' => $propertyName === 'organization' ? 'false' : 'true',
            'sortable' => 'true',
            'searchable' => in_array($propertyType, ['string', 'text']) ? 'true' : 'false',
            'filterable' => 'false',
            'apiReadable' => 'true',
            'apiWritable' => 'true',
            'apiGroups' => 'module:read,module:write',
            'translationKey' => '',
            'formatPattern' => '',
            'fixtureType' => getFixtureType($propertyType),
            'fixtureOptions' => '{}',
        ];
    }

    // Stop after Module
    if ($currentEntity === 'Module' && !empty($id) && $id !== '1' && $entityName !== 'Module') {
        break;
    }
}
fclose($handle);

echo "   ✓ Found " . count($moduleProperties) . " properties for Module\n\n";

// Step 2: Read existing CourseModule from codebase
echo "📂 Reading CourseModule from codebase...\n";
$courseModuleFile = '/home/user/inf/app/src/Entity/CourseModule.php';
$courseModuleContent = file_get_contents($courseModuleFile);

// Extract CourseModule properties
$courseModuleProperties = extractPropertiesFromClass($courseModuleContent, 'CourseModule');
echo "   ✓ Extracted " . count($courseModuleProperties) . " properties from CourseModule\n\n";

// Step 3: Load current CSV files
echo "📋 Loading current CSV files...\n";
$entities = loadCsvEntities($entityNewPath);
$properties = loadCsvProperties($propertyNewPath);

// Step 4: Restore Module (not CourseModule)
echo "🔧 Restoring Module entity...\n";
if (isset($entities['CourseModule']) && !isset($entities['Module'])) {
    // Rename back to Module
    $entities['Module'] = $entities['CourseModule'];
    $entities['Module']['entityName'] = 'Module';
    $entities['Module']['entityLabel'] = 'Module';
    $entities['Module']['pluralLabel'] = 'Modules';
    $entities['Module']['description'] = 'System modules for organizations';
    $entities['Module']['hasOrganization'] = 'false'; // System entity
    $entities['Module']['menuGroup'] = 'System';
    $entities['Module']['icon'] = 'bi-puzzle';

    // Restore Module properties from original CSV
    $properties['Module'] = $moduleProperties;

    echo "   ✓ Restored Module entity\n";
}

// Step 5: Add CourseModule as separate entity
echo "🆕 Adding CourseModule entity...\n";
$entities['CourseModule'] = [
    'entityName' => 'CourseModule',
    'entityLabel' => 'Course Module',
    'pluralLabel' => 'Course Modules',
    'icon' => 'bi-folder',
    'description' => 'Modules inside courses',
    'hasOrganization' => 'false', // Related to Course which has organization
    'apiEnabled' => 'true',
    'operations' => 'GetCollection,Get,Post,Put,Delete',
    'security' => "is_granted('ROLE_USER')",
    'normalizationContext' => 'course_module:read',
    'denormalizationContext' => 'course_module:write',
    'paginationEnabled' => 'true',
    'itemsPerPage' => '30',
    'order' => '{"viewOrder": "asc"}',
    'searchableFields' => 'name',
    'filterableFields' => '',
    'voterEnabled' => 'true',
    'voterAttributes' => 'VIEW,EDIT,DELETE',
    'formTheme' => 'bootstrap_5_layout.html.twig',
    'indexTemplate' => '',
    'formTemplate' => '',
    'showTemplate' => '',
    'menuGroup' => 'Education',
    'menuOrder' => 32,
    'testEnabled' => 'true',
];

$properties['CourseModule'] = $courseModuleProperties;
echo "   ✓ Added CourseModule entity\n\n";

// Step 6: Fix other mappings (keep Lecture → CourseLecture, UserCourse → StudentCourse, etc.)
echo "🔧 Applying other entity name fixes...\n";
$otherMappings = [
    'Lecture' => 'CourseLecture',
    'UserCourse' => 'StudentCourse',
    'UserLecture' => 'StudentLecture',
];

foreach ($otherMappings as $oldName => $newName) {
    if (isset($entities[$oldName]) && !isset($entities[$newName])) {
        $entities[$newName] = $entities[$oldName];
        $entities[$newName]['entityName'] = $newName;
        unset($entities[$oldName]);

        if (isset($properties[$oldName])) {
            $properties[$newName] = $properties[$oldName];
            foreach ($properties[$newName] as &$prop) {
                $prop['entityName'] = $newName;
            }
            unset($properties[$oldName]);
        }

        echo "   ✓ Mapped: $oldName → $newName\n";
    }
}

// Step 7: Write updated CSV files
echo "\n💾 Writing updated CSV files...\n";

$fpEntity = fopen($entityNewPath, 'w');
$firstEntity = true;
foreach ($entities as $entity) {
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
foreach ($properties as $entityProps) {
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

echo "✅ Fixed! Summary:\n";
echo "   • Module (system module) - restored from original CSV\n";
echo "   • CourseModule (course module) - added from codebase\n";
echo "   • Total entities: " . count($entities) . "\n";
echo "   • Total properties: " . array_sum(array_map('count', $properties)) . "\n\n";

// Helper functions

function mapType(string $type): string
{
    $mapping = [
        'bool' => 'boolean',
        'int' => 'integer',
        'bigint' => 'bigint',
        'smallint' => 'smallint',
        'float' => 'float',
        'ManyToOne' => '',
        'OneToMany' => '',
        'ManyToMany' => '',
        'OneToOne' => '',
    ];
    return $mapping[$type] ?? $type;
}

function isRelationType(string $type): bool
{
    return in_array($type, ['ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne']);
}

function getValidationRules(string $type, bool $required): string
{
    if (isRelationType($type)) {
        return $required ? 'NotBlank' : '';
    }

    $rules = [];
    if ($required) {
        $rules[] = 'NotBlank';
    }

    if ($type === 'string') {
        $rules[] = 'Length(max=255)';
    }

    return implode(',', $rules);
}

function getFormType(string $type): string
{
    if (isRelationType($type)) {
        return 'EntityType';
    }

    $mapping = [
        'string' => 'TextType',
        'text' => 'TextareaType',
        'integer' => 'IntegerType',
        'smallint' => 'IntegerType',
        'bigint' => 'IntegerType',
        'float' => 'NumberType',
        'boolean' => 'CheckboxType',
        'date' => 'DateType',
        'datetime' => 'DateTimeType',
        'time' => 'TimeType',
        'json' => 'TextareaType',
    ];

    return $mapping[$type] ?? 'TextType';
}

function getFixtureType(string $type): string
{
    if (isRelationType($type)) {
        return '';
    }

    $mapping = [
        'string' => 'word',
        'text' => 'paragraph',
        'integer' => 'randomNumber',
        'smallint' => 'randomDigit',
        'float' => 'randomFloat',
        'boolean' => 'boolean',
        'date' => 'date',
        'datetime' => 'dateTime',
    ];

    return $mapping[$type] ?? 'word';
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

function extractPropertiesFromClass(string $content, string $entityName): array
{
    $properties = [];

    // Extract regular properties
    preg_match_all('/#\[ORM\\\\Column\((.*?)\]\s+protected\s+\??(\w+)\s+\$(\w+)/s', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $attributes = $match[1];
        $type = $match[2];
        $name = $match[3];

        // Parse length from attributes
        $length = '';
        if (preg_match('/length:\s*(\d+)/', $attributes, $lengthMatch)) {
            $length = $lengthMatch[1];
        }

        // Parse nullable
        $nullable = strpos($type, '?') !== false || strpos($attributes, 'nullable: true') !== false ? 'true' : 'false';

        // Map PHP types to Doctrine types
        $typeMapping = [
            'string' => 'string',
            'int' => 'integer',
            'float' => 'float',
            'bool' => 'boolean',
            'DateTimeImmutable' => 'datetime_immutable',
            'DateTime' => 'datetime',
        ];

        $doctrineType = $typeMapping[str_replace('?', '', $type)] ?? 'string';

        $properties[] = [
            'entityName' => $entityName,
            'propertyName' => $name,
            'propertyLabel' => ucfirst($name),
            'propertyType' => $doctrineType,
            'nullable' => $nullable,
            'length' => $length,
            'precision' => '',
            'scale' => '',
            'unique' => 'false',
            'defaultValue' => '',
            'relationshipType' => '',
            'targetEntity' => '',
            'inversedBy' => '',
            'mappedBy' => '',
            'cascade' => '',
            'orphanRemoval' => 'false',
            'fetch' => 'LAZY',
            'orderBy' => '',
            'validationRules' => $nullable === 'false' ? 'NotBlank' : '',
            'validationMessage' => '',
            'formType' => getFormType($doctrineType),
            'formOptions' => '{}',
            'formRequired' => $nullable === 'false' ? 'true' : 'false',
            'formReadOnly' => 'false',
            'formHelp' => '',
            'showInList' => 'true',
            'showInDetail' => 'true',
            'showInForm' => 'true',
            'sortable' => 'true',
            'searchable' => $doctrineType === 'string' ? 'true' : 'false',
            'filterable' => 'false',
            'apiReadable' => 'true',
            'apiWritable' => 'true',
            'apiGroups' => strtolower($entityName) . ':read,' . strtolower($entityName) . ':write',
            'translationKey' => '',
            'formatPattern' => '',
            'fixtureType' => getFixtureType($doctrineType),
            'fixtureOptions' => '{}',
        ];
    }

    // Extract relationships
    preg_match_all('/#\[ORM\\\\(ManyToOne|OneToMany|ManyToMany|OneToOne)\((.*?)\]\s+protected\s+.*?\$(\w+)/s', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $relationType = $match[1];
        $attributes = $match[2];
        $name = $match[3];

        // Parse targetEntity
        $targetEntity = '';
        if (preg_match('/targetEntity:\s*(\w+)::class/', $attributes, $targetMatch)) {
            $targetEntity = $targetMatch[1];
        }

        // Parse inversedBy/mappedBy
        $inversedBy = '';
        $mappedBy = '';
        if (preg_match('/inversedBy:\s*[\'"](\w+)[\'"]/', $attributes, $inverseMatch)) {
            $inversedBy = $inverseMatch[1];
        }
        if (preg_match('/mappedBy:\s*[\'"](\w+)[\'"]/', $attributes, $mappedMatch)) {
            $mappedBy = $mappedMatch[1];
        }

        $properties[] = [
            'entityName' => $entityName,
            'propertyName' => $name,
            'propertyLabel' => ucfirst($name),
            'propertyType' => '',
            'nullable' => 'true',
            'length' => '',
            'precision' => '',
            'scale' => '',
            'unique' => 'false',
            'defaultValue' => '',
            'relationshipType' => $relationType,
            'targetEntity' => $targetEntity,
            'inversedBy' => $inversedBy,
            'mappedBy' => $mappedBy,
            'cascade' => '',
            'orphanRemoval' => 'false',
            'fetch' => 'LAZY',
            'orderBy' => '',
            'validationRules' => '',
            'validationMessage' => '',
            'formType' => 'EntityType',
            'formOptions' => '{}',
            'formRequired' => 'false',
            'formReadOnly' => 'false',
            'formHelp' => '',
            'showInList' => 'false',
            'showInDetail' => 'true',
            'showInForm' => 'true',
            'sortable' => 'false',
            'searchable' => 'false',
            'filterable' => 'true',
            'apiReadable' => 'true',
            'apiWritable' => 'true',
            'apiGroups' => strtolower($entityName) . ':read,' . strtolower($entityName) . ':write',
            'translationKey' => '',
            'formatPattern' => '',
            'fixtureType' => '',
            'fixtureOptions' => '{}',
        ];
    }

    return $properties;
}
