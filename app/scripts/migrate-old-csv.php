<?php

declare(strict_types=1);

/**
 * Migrate Old Entity.csv to EntityNew.csv + PropertyNew.csv
 * 
 * Parses the semicolon-separated mixed format and creates the new two-file structure
 */

$oldCsvPath = '/home/user/inf/config/Entity.csv';
$entityNewPath = '/home/user/inf/app/config/EntityNew.csv';
$propertyNewPath = '/home/user/inf/app/config/PropertyNew.csv';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  Migrating Old CSV Format to New Format\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Parse old CSV
if (!file_exists($oldCsvPath)) {
    die("❌ Error: {$oldCsvPath} not found!\n");
}

$handle = fopen($oldCsvPath, 'r');
$header = fgetcsv($handle, 0, ';');

$entities = [];
$properties = [];
$currentEntity = null;
$entityId = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    if (empty($row[0]) && empty($row[1])) {
        continue; // Skip empty rows
    }
    
    $id = $row[0] ?? '';
    $entityName = $row[1] ?? '';
    $propertyName = $row[2] ?? '';
    $propertyType = $row[3] ?? '';
    
    // New entity detected (has ID)
    if (!empty($id) && !empty($entityName)) {
        $entityId++;
        $currentEntity = $entityName;
        
        $navGroup = $row[21] ?? '';
        $navOrder = $row[22] ?? '99';
        
        $entities[$entityName] = [
            'entityName' => $entityName,
            'entityLabel' => $entityName,
            'pluralLabel' => $entityName . 's', // Simple pluralization
            'icon' => 'bi-circle',
            'description' => '',
            'hasOrganization' => hasOrganizationProperty($entityName) ? 'true' : 'false',
            'apiEnabled' => 'true',
            'operations' => 'GetCollection,Get,Post,Put,Delete',
            'security' => "is_granted('ROLE_USER')",
            'normalizationContext' => strtolower($entityName) . ':read',
            'denormalizationContext' => strtolower($entityName) . ':write',
            'paginationEnabled' => 'true',
            'itemsPerPage' => '30',
            'order' => '{"name": "asc"}',
            'searchableFields' => '',
            'filterableFields' => '',
            'voterEnabled' => 'true',
            'voterAttributes' => 'VIEW,EDIT,DELETE',
            'formTheme' => 'bootstrap_5_layout.html.twig',
            'indexTemplate' => '',
            'formTemplate' => '',
            'showTemplate' => '',
            'menuGroup' => getMenuGroup($navGroup),
            'menuOrder' => (int)$navOrder,
            'testEnabled' => 'true',
        ];
        
        $properties[$entityName] = [];
    }
    
    // Property for current entity
    if ($currentEntity && !empty($propertyName)) {
        $nullable = ($row[5] ?? '') === '1' ? 'true' : 'false';
        $length = $row[4] ?? '';
        $targetEntity = $row[6] ?? '';
        $inversedBy = $row[7] ?? '';
        
        $property = [
            'entityName' => $currentEntity,
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
            'apiGroups' => strtolower($currentEntity) . ':read,' . strtolower($currentEntity) . ':write',
            'translationKey' => '',
            'formatPattern' => '',
            'fixtureType' => getFixtureType($propertyType),
            'fixtureOptions' => '{}',
        ];
        
        $properties[$currentEntity][] = $property;
    }
}

fclose($handle);

echo "✅ Parsed: " . count($entities) . " entities, " . array_sum(array_map('count', $properties)) . " properties\n\n";

// Write EntityNew.csv
$fp = fopen($entityNewPath, 'w');
fputcsv($fp, array_keys($entities[array_key_first($entities)]));
foreach ($entities as $entity) {
    fputcsv($fp, $entity);
}
fclose($fp);

echo "✅ Created: {$entityNewPath}\n";

// Write PropertyNew.csv
$fp = fopen($propertyNewPath, 'w');
$firstProperty = $properties[array_key_first($properties)][0];
fputcsv($fp, array_keys($firstProperty));
foreach ($properties as $entityProps) {
    foreach ($entityProps as $property) {
        fputcsv($fp, $property);
    }
}
fclose($fp);

echo "✅ Created: {$propertyNewPath}\n\n";

echo "📊 Summary:\n";
echo "   • Entities: " . count($entities) . "\n";
echo "   • Properties: " . array_sum(array_map('count', $properties)) . "\n";
echo "   • EntityNew.csv: {$entityNewPath}\n";
echo "   • PropertyNew.csv: {$propertyNewPath}\n\n";

echo "✅ Migration complete!\n\n";
echo "Next steps:\n";
echo "1. Review the generated CSV files\n";
echo "2. Adjust entity labels, icons, menu groups as needed\n";
echo "3. Run: php bin/console app:generate-from-csv --dry-run\n";
echo "4. Run: php bin/console app:generate-from-csv\n\n";

// Helper functions

function hasOrganizationProperty(string $entityName): bool
{
    // System entities don't have organization
    $systemEntities = ['Module', 'Role', 'City', 'Country', 'ProfileTemplate', 
                       'SocialMediaType', 'AgentType', 'TalkTypeTemplate',
                       'NotificationTypeTemplate', 'CalendarType', 'CalendarExternalLink',
                       'EventResourceType', 'HolidayTemplate', 'TimeZone', 'CommunicationMethod'];
    return !in_array($entityName, $systemEntities);
}

function getMenuGroup(string $navGroup): string
{
    $mapping = [
        'system.99' => 'System',
        'organization.90' => 'Configuration',
        'crm.01' => 'CRM',
        'marketing.20' => 'Marketing',
        'calendar.10' => 'Calendar',
        'education.30' => 'Education',
    ];
    
    return $mapping[$navGroup] ?? 'General';
}

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
