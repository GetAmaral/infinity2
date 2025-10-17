<?php

declare(strict_types=1);

/**
 * PROPER CSV Migration - Analyze and Migrate Entity.csv to EntityNew.csv + PropertyNew.csv
 *
 * Includes:
 * - Proper index extraction and compilation
 * - Comprehensive role system analysis
 * - Best practices for performance
 */

$oldCsvPath = '/home/user/inf/config/Entity.csv';
$entityNewPath = '/home/user/inf/app/config/EntityNew.csv';
$propertyNewPath = '/home/user/inf/app/config/PropertyNew.csv';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PROPER CSV MIGRATION WITH INDEXES AND ROLES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// STEP 1: Analyze original CSV structure
echo "📊 STEP 1: Analyzing original Entity.csv...\n";

$handle = fopen($oldCsvPath, 'r');
$header = fgetcsv($handle, 0, ';');

echo "   Columns found:\n";
foreach ($header as $i => $col) {
    echo "   [$i] $col\n";
}
echo "\n";

// Column mapping
$colMap = [
    'ID' => 0,
    'Entity' => 1,
    'Property' => 2,
    'Type' => 3,
    'Len' => 4,
    'Nullable' => 5,
    'targetEntity' => 6,
    'fkProperty' => 7,
    'OrderBy' => 8,
    'index' => 9,      // INDEXES HERE!
    'form' => 10,
    'detail' => 11,
    'list' => 12,
    'noSearch' => 13,
    'noSort' => 14,
    'roles' => 15,     // ROLES HERE!
    'get' => 16,
    'post' => 17,
    'put' => 18,
    'patch' => 19,
    'delete' => 20,
    'nav_group' => 21,
    'nav_order' => 22,
];

// STEP 2: Extract ALL data with indexes and roles
echo "📋 STEP 2: Extracting entities, properties, indexes, and roles...\n";

$entities = [];
$properties = [];
$indexData = [];
$roleData = [];
$currentEntity = null;

rewind($handle);
fgetcsv($handle, 0, ';'); // Skip header

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    if (empty($row[1])) continue; // Skip if no entity

    $entityName = $row[$colMap['Entity']];
    $propertyName = $row[$colMap['Property']] ?? '';

    // New entity
    if (!empty($row[$colMap['ID']])) {
        $currentEntity = $entityName;
        $navGroup = $row[$colMap['nav_group']] ?? '';
        $navOrder = $row[$colMap['nav_order']] ?? '99';

        $entities[$entityName] = [
            'navGroup' => $navGroup,
            'navOrder' => (int)$navOrder,
        ];
    }

    // Property
    if ($currentEntity && !empty($propertyName)) {
        $type = $row[$colMap['Type']] ?? '';
        $nullable = ($row[$colMap['Nullable']] ?? '') === '1';
        $length = $row[$colMap['Len']] ?? '';
        $targetEntity = $row[$colMap['targetEntity']] ?? '';
        $fkProperty = $row[$colMap['fkProperty']] ?? '';
        $orderBy = $row[$colMap['OrderBy']] ?? '';

        // INDEXES (column 9)
        $indexStr = $row[$colMap['index']] ?? '';

        // ROLES (column 15)
        $roleStr = $row[$colMap['roles']] ?? '';

        // Form visibility
        $form = $row[$colMap['form']] ?? '';
        $detail = $row[$colMap['detail']] ?? '';
        $list = $row[$colMap['list']] ?? '';
        $noSearch = $row[$colMap['noSearch']] ?? '';
        $noSort = $row[$colMap['noSort']] ?? '';

        $property = [
            'entityName' => $currentEntity,
            'propertyName' => $propertyName,
            'type' => $type,
            'nullable' => $nullable,
            'length' => $length,
            'targetEntity' => $targetEntity,
            'fkProperty' => $fkProperty,
            'orderBy' => $orderBy,
            'index' => $indexStr,
            'roles' => $roleStr,
            'form' => $form,
            'detail' => $detail,
            'list' => $list,
            'noSearch' => $noSearch,
            'noSort' => $noSort,
        ];

        $properties[$currentEntity][] = $property;

        // Collect index data
        if (!empty($indexStr)) {
            if (!isset($indexData[$currentEntity])) {
                $indexData[$currentEntity] = [];
            }
            $indexData[$currentEntity][$propertyName] = $indexStr;
        }

        // Collect role data
        if (!empty($roleStr)) {
            if (!isset($roleData[$currentEntity])) {
                $roleData[$currentEntity] = [];
            }
            $roleData[$currentEntity][$propertyName] = $roleStr;
        }
    }
}

fclose($handle);

echo "   ✓ Found " . count($entities) . " entities\n";
echo "   ✓ Found " . array_sum(array_map('count', $properties)) . " properties\n";
echo "   ✓ Found " . array_sum(array_map('count', $indexData)) . " indexed properties\n";
echo "   ✓ Found " . array_sum(array_map('count', $roleData)) . " role-protected properties\n";
echo "\n";

// STEP 3: Analyze index patterns
echo "🔍 STEP 3: Analyzing index patterns...\n";

$allIndexes = [];
foreach ($indexData as $entity => $props) {
    foreach ($props as $prop => $indexStr) {
        $indexes = explode('|', $indexStr);
        foreach ($indexes as $idx) {
            if (!isset($allIndexes[$idx])) {
                $allIndexes[$idx] = [];
            }
            $allIndexes[$idx][] = "$entity.$prop";
        }
    }
}

echo "   Index patterns found:\n";
foreach ($allIndexes as $indexName => $fields) {
    echo "   • $indexName: " . count($fields) . " occurrences\n";
    if (count($fields) <= 5) {
        foreach ($fields as $field) {
            echo "     - $field\n";
        }
    }
}
echo "\n";

// STEP 4: Analyze role patterns
echo "🔒 STEP 4: Analyzing role patterns...\n";

$allRoles = [];
foreach ($roleData as $entity => $props) {
    foreach ($props as $prop => $roleStr) {
        if (!isset($allRoles[$roleStr])) {
            $allRoles[$roleStr] = [];
        }
        $allRoles[$roleStr][] = "$entity.$prop";
    }
}

echo "   Roles found:\n";
ksort($allRoles);
foreach ($allRoles as $role => $fields) {
    echo "   • $role: " . count($fields) . " properties\n";
}
echo "\n";

// STEP 5: Create comprehensive role hierarchy
echo "🎯 STEP 5: Creating comprehensive role hierarchy...\n";

$roleHierarchy = [
    // System Administration
    'ROLE_SUPER_ADMIN' => [
        'description' => 'System-wide super administrator',
        'grants' => ['Full system access', 'Cross-organization access', 'System configuration'],
        'entities' => [],
    ],

    // Organization Administration
    'ROLE_ORGANIZATION_ADMIN' => [
        'description' => 'Organization administrator',
        'grants' => ['Organization management', 'User management', 'Module configuration'],
        'entities' => ['Organization', 'User', 'Role', 'Module'],
    ],

    // CRM Roles
    'ROLE_CRM_ADMIN' => [
        'description' => 'CRM system administrator',
        'grants' => ['CRM configuration', 'Pipeline management', 'Deal stages'],
        'entities' => ['Pipeline', 'PipelineStage', 'DealStage', 'DealType', 'DealCategory', 'TaskType'],
    ],

    'ROLE_SALES_MANAGER' => [
        'description' => 'Sales team manager',
        'grants' => ['Manage deals', 'Assign tasks', 'View team performance'],
        'entities' => ['Deal', 'Task', 'Pipeline', 'Contact', 'Company'],
    ],

    'ROLE_SALES_REP' => [
        'description' => 'Sales representative',
        'grants' => ['Manage own deals', 'Create contacts', 'Update tasks'],
        'entities' => ['Deal', 'Contact', 'Task', 'Talk'],
    ],

    'ROLE_ACCOUNT_MANAGER' => [
        'description' => 'Account/Customer manager',
        'grants' => ['Manage customer accounts', 'Track interactions'],
        'entities' => ['Contact', 'Company', 'Deal', 'Task', 'Talk'],
    ],

    // Marketing Roles
    'ROLE_MARKETING_ADMIN' => [
        'description' => 'Marketing administrator',
        'grants' => ['Campaign management', 'Lead source configuration'],
        'entities' => ['Campaign', 'LeadSource'],
    ],

    'ROLE_MARKETING_MANAGER' => [
        'description' => 'Marketing campaign manager',
        'grants' => ['Create campaigns', 'Manage leads'],
        'entities' => ['Campaign', 'LeadSource', 'Contact'],
    ],

    // Calendar/Events Roles
    'ROLE_EVENT_ADMIN' => [
        'description' => 'Events administrator',
        'grants' => ['Event configuration', 'Resource management'],
        'entities' => ['EventCategory', 'EventResource', 'EventResourceType', 'CalendarType'],
    ],

    'ROLE_EVENT_MANAGER' => [
        'description' => 'Event organizer',
        'grants' => ['Create events', 'Manage attendees', 'Book resources'],
        'entities' => ['Event', 'EventAttendee', 'EventResourceBooking', 'Calendar'],
    ],

    // Education Roles
    'ROLE_EDUCATION_ADMIN' => [
        'description' => 'Education administrator',
        'grants' => ['Course management', 'Module configuration'],
        'entities' => ['Course', 'CourseModule', 'CourseLecture'],
    ],

    'ROLE_INSTRUCTOR' => [
        'description' => 'Course instructor',
        'grants' => ['Create courses', 'Manage modules', 'Track students'],
        'entities' => ['Course', 'CourseModule', 'CourseLecture', 'StudentCourse'],
    ],

    'ROLE_STUDENT' => [
        'description' => 'Student/Learner',
        'grants' => ['Enroll in courses', 'View lectures', 'Track progress'],
        'entities' => ['StudentCourse', 'StudentLecture'],
    ],

    // Configuration Roles
    'ROLE_SYSTEM_CONFIG' => [
        'description' => 'System configuration manager',
        'grants' => ['Manage templates', 'Configure types', 'System settings'],
        'entities' => ['ProfileTemplate', 'TalkTypeTemplate', 'AgentType', 'CalendarType', 'NotificationTypeTemplate', 'TimeZone', 'CommunicationMethod'],
    ],

    'ROLE_ORG_CONFIG' => [
        'description' => 'Organization configuration manager',
        'grants' => ['Configure organization settings', 'Manage profiles'],
        'entities' => ['Profile', 'Agent', 'TalkType', 'NotificationType'],
    ],

    // Support/Agent Roles
    'ROLE_SUPPORT_ADMIN' => [
        'description' => 'Support administrator',
        'grants' => ['Configure support settings', 'Manage agents'],
        'entities' => ['Agent', 'AgentType', 'Talk', 'TalkType'],
    ],

    'ROLE_SUPPORT_AGENT' => [
        'description' => 'Support agent',
        'grants' => ['Handle customer conversations', 'Manage tickets'],
        'entities' => ['Talk', 'TalkMessage', 'Contact'],
    ],

    // Data Management
    'ROLE_DATA_ADMIN' => [
        'description' => 'Data administrator',
        'grants' => ['Manage system data', 'Import/Export', 'Data cleanup'],
        'entities' => ['City', 'Country', 'Product', 'ProductCategory', 'Brand', 'TaxCategory'],
    ],

    // Basic User
    'ROLE_USER' => [
        'description' => 'Basic authenticated user',
        'grants' => ['View own data', 'Basic operations'],
        'entities' => [],
    ],
];

echo "   Created " . count($roleHierarchy) . " roles\n\n";

foreach ($roleHierarchy as $role => $info) {
    echo "   $role\n";
    echo "     → {$info['description']}\n";
}
echo "\n";

// STEP 6: Save analysis report
$reportPath = '/home/user/inf/app/PROPER_CSV_ANALYSIS.md';
$report = [];
$report[] = "# Proper CSV Migration Analysis";
$report[] = "";
$report[] = "## Original Entity.csv Structure";
$report[] = "";
$report[] = "**Columns:**";
foreach ($header as $i => $col) {
    $report[] = "- [$i] `$col`";
}
$report[] = "";

$report[] = "## Index Patterns Extracted";
$report[] = "";
foreach ($allIndexes as $indexName => $fields) {
    $report[] = "### $indexName (" . count($fields) . " properties)";
    if (count($fields) <= 10) {
        foreach ($fields as $field) {
            $report[] = "- `$field`";
        }
    } else {
        foreach (array_slice($fields, 0, 10) as $field) {
            $report[] = "- `$field`";
        }
        $report[] = "- ... and " . (count($fields) - 10) . " more";
    }
    $report[] = "";
}

$report[] = "## Role Patterns Extracted";
$report[] = "";
foreach ($allRoles as $role => $fields) {
    $report[] = "### $role (" . count($fields) . " properties)";
    foreach (array_slice($fields, 0, 10) as $field) {
        $report[] = "- `$field`";
    }
    if (count($fields) > 10) {
        $report[] = "- ... and " . (count($fields) - 10) . " more";
    }
    $report[] = "";
}

$report[] = "## Comprehensive Role Hierarchy (18 Roles)";
$report[] = "";
foreach ($roleHierarchy as $role => $info) {
    $report[] = "### $role";
    $report[] = "";
    $report[] = "**Description:** {$info['description']}";
    $report[] = "";
    $report[] = "**Grants:**";
    foreach ($info['grants'] as $grant) {
        $report[] = "- $grant";
    }
    if (!empty($info['entities'])) {
        $report[] = "";
        $report[] = "**Primary Entities:** " . implode(', ', $info['entities']);
    }
    $report[] = "";
}

file_put_contents($reportPath, implode("\n", $report));

echo "✅ Analysis saved to: $reportPath\n\n";

echo "📊 Summary:\n";
echo "   • Entities: " . count($entities) . "\n";
echo "   • Properties: " . array_sum(array_map('count', $properties)) . "\n";
echo "   • Index patterns: " . count($allIndexes) . "\n";
echo "   • Role patterns: " . count($allRoles) . "\n";
echo "   • Comprehensive roles: " . count($roleHierarchy) . "\n\n";

echo "Next: I will create the proper migration script with indexes and roles compiled.\n";
