<?php

declare(strict_types=1);

/**
 * FINAL PROPER CSV MIGRATION
 *
 * - Compiles indexes from original Entity.csv
 * - Adds performance-optimized indexes (foreign keys, composite)
 * - Maps comprehensive 19-role hierarchy
 * - Includes EXTRA_LAZY for large collections
 * - Proper cascade and orphanRemoval
 */

$oldCsvPath = '/home/user/inf/config/Entity.csv';
$entityNewPath = '/home/user/inf/app/config/EntityNew.csv';
$propertyNewPath = '/home/user/inf/app/config/PropertyNew.csv';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FINAL PROPER CSV MIGRATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Parse original CSV
$handle = fopen($oldCsvPath, 'r');
fgetcsv($handle, 0, ';'); // Skip header

$entities = [];
$properties = [];
$currentEntity = null;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $entityName = $row[1] ?? '';
    if (empty($entityName)) continue;

    // New entity
    if (!empty($row[0])) {
        $currentEntity = $entityName;
        $navGroup = $row[21] ?? '';
        $navOrder = $row[22] ?? '99';

        $entities[$entityName] = [
            'entityName' => $entityName,
            'nav_group' => $navGroup,
            'nav_order' => (int)$navOrder,
        ];
    }

    // Property
    if ($currentEntity && !empty($row[2])) {
        $propName = $row[2];
        $type = $row[3] ?? '';
        $len = $row[4] ?? '';
        $nullable = ($row[5] ?? '') === '1';
        $targetEntity = $row[6] ?? '';
        $fkProperty = $row[7] ?? '';
        $orderBy = $row[8] ?? '';
        $indexStr = $row[9] ?? '';  // INDEXES!
        $roleStr = $row[15] ?? '';  // ROLES!

        $properties[$currentEntity][] = [
            'propertyName' => $propName,
            'type' => $type,
            'length' => $len,
            'nullable' => $nullable,
            'targetEntity' => $targetEntity,
            'fkProperty' => $fkProperty,
            'orderBy' => $orderBy,
            'index' => $indexStr,
            'role' => $roleStr,
        ];
    }
}

fclose($handle);

echo "✓ Parsed " . count($entities) . " entities with " . array_sum(array_map('count', $properties)) . " properties\n\n";

// ===== COMPREHENSIVE ROLE HIERARCHY =====
$roleHierarchy = [
    'ROLE_SUPER_ADMIN' => [
        'level' => 100,
        'description' => 'System-wide super administrator - full access',
        'entityPattern' => ['*'], // All entities
    ],
    'ROLE_ORGANIZATION_ADMIN' => [
        'level' => 90,
        'description' => 'Organization administrator',
        'entityPattern' => ['Organization', 'User', 'Role', 'Module', 'Profile'],
    ],
    'ROLE_SYSTEM_CONFIG' => [
        'level' => 85,
        'description' => 'System configuration (templates, types, global settings)',
        'entityPattern' => ['*Template', '*Type', 'City', 'Country', 'TimeZone', 'CommunicationMethod'],
    ],
    'ROLE_CRM_ADMIN' => [
        'level' => 80,
        'description' => 'CRM administrator - configure CRM system',
        'entityPattern' => ['Pipeline*', 'DealStage', 'DealType', 'DealCategory', 'TaskType', 'LeadSource'],
    ],
    'ROLE_SALES_MANAGER' => [
        'level' => 70,
        'description' => 'Sales manager - manage team and deals',
        'entityPattern' => ['Deal', 'Pipeline', 'Contact', 'Company', 'Task'],
    ],
    'ROLE_ACCOUNT_MANAGER' => [
        'level' => 65,
        'description' => 'Account manager - manage customer relationships',
        'entityPattern' => ['Contact', 'Company', 'Deal', 'Task', 'Talk'],
    ],
    'ROLE_SALES_REP' => [
        'level' => 60,
        'description' => 'Sales representative - own deals and contacts',
        'entityPattern' => ['Deal', 'Contact', 'Task', 'Talk'],
    ],
    'ROLE_MARKETING_ADMIN' => [
        'level' => 75,
        'description' => 'Marketing administrator',
        'entityPattern' => ['Campaign', 'LeadSource'],
    ],
    'ROLE_MARKETING_MANAGER' => [
        'level' => 65,
        'description' => 'Marketing campaign manager',
        'entityPattern' => ['Campaign', 'LeadSource', 'Contact'],
    ],
    'ROLE_EVENT_ADMIN' => [
        'level' => 75,
        'description' => 'Event administrator',
        'entityPattern' => ['EventCategory', 'EventResource*', 'CalendarType'],
    ],
    'ROLE_EVENT_MANAGER' => [
        'level' => 65,
        'description' => 'Event manager',
        'entityPattern' => ['Event', 'EventAttendee', 'EventResourceBooking', 'Calendar'],
    ],
    'ROLE_EDUCATION_ADMIN' => [
        'level' => 75,
        'description' => 'Education administrator',
        'entityPattern' => ['Course', 'CourseModule', 'CourseLecture'],
    ],
    'ROLE_INSTRUCTOR' => [
        'level' => 65,
        'description' => 'Course instructor/teacher',
        'entityPattern' => ['Course', 'CourseModule', 'CourseLecture', 'StudentCourse', 'StudentLecture'],
    ],
    'ROLE_STUDENT' => [
        'level' => 50,
        'description' => 'Student/learner',
        'entityPattern' => ['StudentCourse', 'StudentLecture', 'Course', 'CourseModule', 'CourseLecture'],
    ],
    'ROLE_SUPPORT_ADMIN' => [
        'level' => 75,
        'description' => 'Support administrator',
        'entityPattern' => ['Agent*', 'Talk*'],
    ],
    'ROLE_SUPPORT_AGENT' => [
        'level' => 60,
        'description' => 'Support agent - handle conversations',
        'entityPattern' => ['Talk', 'TalkMessage', 'Contact'],
    ],
    'ROLE_DATA_ADMIN' => [
        'level' => 70,
        'description' => 'Data administrator - manage master data',
        'entityPattern' => ['Product*', 'Brand', 'TaxCategory', 'BillingFrequency', 'Competitor', 'Tag'],
    ],
    'ROLE_MANAGER' => [
        'level' => 65,
        'description' => 'General manager role',
        'entityPattern' => [],
    ],
    'ROLE_USER' => [
        'level' => 50,
        'description' => 'Basic authenticated user',
        'entityPattern' => [],
    ],
];

// ===== MAP ENTITIES TO ROLES =====
echo "🔒 Mapping entities to roles...\n";

$entitySecurityMap = [];

foreach ($entities as $entityName => $entityData) {
    $navGroup = $entityData['nav_group'];
    $matchedRoles = [];

    // System entities
    if ($navGroup === 'system.99') {
        $matchedRoles[] = 'ROLE_SUPER_ADMIN';
        $entitySecurityMap[$entityName] = [
            'primary' => 'ROLE_SUPER_ADMIN',
            'allowed' => ['ROLE_SUPER_ADMIN'],
        ];
        continue;
    }

    // Match entity to roles by pattern
    foreach ($roleHierarchy as $role => $info) {
        foreach ($info['entityPattern'] as $pattern) {
            if ($pattern === '*') {
                continue; // Skip wildcard for now
            }

            // Pattern matching
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $entityName)) {
                    $matchedRoles[] = $role;
                }
            } elseif ($pattern === $entityName) {
                $matchedRoles[] = $role;
            }
        }
    }

    // Apply logic based on nav_group if no match
    if (empty($matchedRoles)) {
        if (str_contains($navGroup, 'organization')) {
            $matchedRoles = ['ROLE_ORGANIZATION_ADMIN', 'ROLE_MANAGER', 'ROLE_USER'];
        } elseif (str_contains($navGroup, 'crm')) {
            $matchedRoles = ['ROLE_CRM_ADMIN', 'ROLE_SALES_MANAGER', 'ROLE_SALES_REP'];
        } elseif (str_contains($navGroup, 'marketing')) {
            $matchedRoles = ['ROLE_MARKETING_ADMIN', 'ROLE_MARKETING_MANAGER'];
        } elseif (str_contains($navGroup, 'calendar')) {
            $matchedRoles = ['ROLE_EVENT_ADMIN', 'ROLE_EVENT_MANAGER', 'ROLE_USER'];
        } elseif (str_contains($navGroup, 'education')) {
            $matchedRoles = ['ROLE_EDUCATION_ADMIN', 'ROLE_INSTRUCTOR', 'ROLE_STUDENT'];
        } else {
            $matchedRoles = ['ROLE_USER'];
        }
    }

    // Sort by level (highest first)
    usort($matchedRoles, function($a, $b) use ($roleHierarchy) {
        return ($roleHierarchy[$b]['level'] ?? 0) - ($roleHierarchy[$a]['level'] ?? 0);
    });

    $entitySecurityMap[$entityName] = [
        'primary' => $matchedRoles[0],
        'allowed' => $matchedRoles,
    ];
}

echo "   ✓ Mapped " . count($entitySecurityMap) . " entities to roles\n\n";

// ===== CREATE ENTITYNEW.CSV =====
echo "📝 Creating EntityNew.csv...\n";

$entityCsvData = [];
$entityCsvData[] = [
    'entityName', 'entityLabel', 'pluralLabel', 'icon', 'description',
    'hasOrganization', 'apiEnabled', 'operations', 'security',
    'normalizationContext', 'denormalizationContext', 'paginationEnabled', 'itemsPerPage',
    'order', 'searchableFields', 'filterableFields', 'voterEnabled', 'voterAttributes',
    'formTheme', 'indexTemplate', 'formTemplate', 'showTemplate',
    'menuGroup', 'menuOrder', 'testEnabled'
];

foreach ($entities as $entityName => $entityData) {
    $navGroup = $entityData['nav_group'];
    $navOrder = $entityData['nav_order'];
    $security = $entitySecurityMap[$entityName] ?? ['primary' => 'ROLE_USER'];

    $hasOrganization = !in_array($entityName, [
        'Module', 'Role', 'City', 'Country', 'ProfileTemplate', 'SocialMediaType',
        'AgentType', 'TalkTypeTemplate', 'NotificationTypeTemplate', 'CalendarType',
        'CalendarExternalLink', 'EventResourceType', 'HolidayTemplate', 'TimeZone', 'CommunicationMethod'
    ]);

    $menuGroup = match(true) {
        str_contains($navGroup, 'system') => 'System',
        str_contains($navGroup, 'organization') => 'Configuration',
        str_contains($navGroup, 'crm') => 'CRM',
        str_contains($navGroup, 'marketing') => 'Marketing',
        str_contains($navGroup, 'calendar') => 'Calendar',
        str_contains($navGroup, 'education') => 'Education',
        default => 'General',
    };

    $voterEnabled = !in_array($security['primary'], ['ROLE_SUPER_ADMIN']);

    $entityCsvData[] = [
        $entityName,
        $entityName,
        $entityName . 's',
        'bi-circle',
        '',
        $hasOrganization ? 'true' : 'false',
        'true',
        'GetCollection,Get,Post,Put,Delete',
        "is_granted('{$security['primary']}')",
        strtolower($entityName) . ':read',
        strtolower($entityName) . ':write',
        'true',
        '30',
        '{"createdAt": "desc"}',
        '',
        '',
        $voterEnabled ? 'true' : 'false',
        $voterEnabled ? 'VIEW,EDIT,DELETE' : '',
        'bootstrap_5_layout.html.twig',
        '',
        '',
        '',
        $menuGroup,
        $navOrder,
        'true',
    ];
}

$fp = fopen($entityNewPath, 'w');
foreach ($entityCsvData as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

echo "   ✓ Created EntityNew.csv with " . (count($entityCsvData) - 1) . " entities\n\n";

// ===== CREATE PROPERTYNEW.CSV WITH INDEXES =====
echo "📝 Creating PropertyNew.csv with indexes...\n";

$propertyCsvData = [];
$propertyCsvData[] = [
    'entityName', 'propertyName', 'propertyLabel', 'propertyType', 'nullable', 'length',
    'precision', 'scale', 'unique', 'defaultValue', 'relationshipType', 'targetEntity',
    'inversedBy', 'mappedBy', 'cascade', 'orphanRemoval', 'fetch', 'orderBy',
    'indexed', 'indexType', 'compositeIndexWith', // INDEX COLUMNS
    'validationRules', 'validationMessage', 'formType', 'formOptions', 'formRequired',
    'formReadOnly', 'formHelp', 'showInList', 'showInDetail', 'showInForm',
    'sortable', 'searchable', 'filterable', 'apiReadable', 'apiWritable', 'apiGroups',
    'allowedRoles', // ROLE COLUMN
    'translationKey', 'formatPattern', 'fixtureType', 'fixtureOptions'
];

$indexCount = 0;

foreach ($properties as $entityName => $props) {
    foreach ($props as $prop) {
        $propName = $prop['propertyName'];
        $type = mapType($prop['type']);
        $relType = isRelationType($prop['type']) ? $prop['type'] : '';
        $nullable = $prop['nullable'] ? 'true' : 'false';
        $indexed = 'false';
        $indexType = '';
        $compositeWith = '';

        // Compile original indexes
        if (!empty($prop['index'])) {
            $indexed = 'true';
            $indexNames = explode('|', $prop['index']);

            // Check if composite
            if (count($indexNames) > 1) {
                $indexType = 'composite';
                // Extract composite columns from index name pattern
                if (str_contains($prop['index'], 'ix_name_slug')) {
                    $compositeWith = 'slug';
                } elseif (str_contains($prop['index'], 'ix_name_organization')) {
                    $compositeWith = 'organization';
                } elseif (str_contains($prop['index'], 'ix_email_organization')) {
                    $compositeWith = 'organization';
                }
            } else {
                $indexType = 'simple';
            }
            $indexCount++;
        }

        // Add indexes for foreign keys (BEST PRACTICE)
        if ($relType === 'ManyToOne' && $indexed === 'false') {
            $indexed = 'true';
            $indexType = 'simple';
            $indexCount++;
        }

        // Add composite index for organization + createdAt (BEST PRACTICE for multi-tenant)
        if ($propName === 'organization' && $relType === 'ManyToOne') {
            $indexed = 'true';
            $indexType = 'composite';
            $compositeWith = 'createdAt';
        }

        // Unique fields get unique index
        if (in_array($propName, ['email', 'slug']) && $indexed === 'false') {
            $indexed = 'true';
            $indexType = 'unique';
            $indexCount++;
        }

        // EXTRA_LAZY for large collections
        $fetch = 'LAZY';
        if ($relType === 'OneToMany') {
            $isLargeCollection = in_array($propName, [
                'contacts', 'companies', 'deals', 'tasks', 'events', 'users', 'products',
                'campaigns', 'managedContacts', 'managedDeals', 'talks', 'studentCourses'
            ]);

            if ($isLargeCollection) {
                $fetch = 'EXTRA_LAZY';
            }
        }

        // Cascade and orphanRemoval
        $cascade = '';
        $orphanRemoval = 'false';

        if ($relType === 'OneToMany') {
            $isOwned = in_array($propName, ['modules', 'lectures', 'messages', 'stages', 'attendees', 'bookings']);
            if ($isOwned) {
                $cascade = 'persist,remove';
                $orphanRemoval = 'true';
            }
        }

        // Extract roles from original
        $allowedRoles = '';
        if (!empty($prop['role'])) {
            $allowedRoles = $prop['role'];
        }

        $propertyCsvData[] = [
            $entityName,
            $propName,
            ucfirst($propName),
            $type,
            $nullable,
            $prop['length'] ?? '',
            '',
            '',
            in_array($propName, ['email', 'slug']) ? 'true' : 'false',
            '',
            $relType,
            $prop['targetEntity'] ?? '',
            $prop['fkProperty'] ?? '',
            '',
            $cascade,
            $orphanRemoval,
            $fetch,
            $prop['orderBy'] ?? '',
            $indexed,
            $indexType,
            $compositeWith,
            getValidationRules($type, $nullable === 'false'),
            '',
            getFormType($type, $relType),
            '{}',
            $nullable === 'false' ? 'true' : 'false',
            'false',
            '',
            'true',
            'true',
            $propName === 'organization' ? 'false' : 'true',
            'true',
            in_array($type, ['string', 'text']) ? 'true' : 'false',
            'false',
            'true',
            'true',
            strtolower($entityName) . ':read,' . strtolower($entityName) . ':write',
            $allowedRoles,
            '',
            '',
            getFixtureType($type, $relType),
            '{}',
        ];
    }
}

$fp = fopen($propertyNewPath, 'w');
foreach ($propertyCsvData as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

echo "   ✓ Created PropertyNew.csv with " . (count($propertyCsvData) - 1) . " properties\n";
echo "   ✓ Added $indexCount indexes (compiled + best practices)\n\n";

echo "✅ MIGRATION COMPLETE!\n\n";

echo "Summary:\n";
echo "   • EntityNew.csv: " . (count($entityCsvData) - 1) . " entities with role-based security\n";
echo "   • PropertyNew.csv: " . (count($propertyCsvData) - 1) . " properties with indexes\n";
echo "   • Indexes: $indexCount (original + foreign keys + composite)\n";
echo "   • Roles: " . count($roleHierarchy) . " comprehensive roles\n";
echo "   • EXTRA_LAZY: Applied to large collections\n";
echo "   • Cascade/orphanRemoval: Applied to owned relationships\n\n";

// Helper functions
function mapType(string $type): string {
    return match($type) {
        'bool' => 'boolean',
        'int', 'smallint', 'bigint' => 'integer',
        'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne' => '',
        default => $type,
    };
}

function isRelationType(string $type): bool {
    return in_array($type, ['ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne']);
}

function getValidationRules(string $type, bool $required): string {
    $rules = [];
    if ($required && $type !== '') {
        $rules[] = 'NotBlank';
    }
    if ($type === 'string') {
        $rules[] = 'Length(max=255)';
    }
    return implode(',', $rules);
}

function getFormType(string $type, string $relType): string {
    if (!empty($relType)) {
        return 'EntityType';
    }
    return match($type) {
        'string' => 'TextType',
        'text' => 'TextareaType',
        'integer' => 'IntegerType',
        'boolean' => 'CheckboxType',
        'datetime' => 'DateTimeType',
        'date' => 'DateType',
        'json' => 'TextareaType',
        default => 'TextType',
    };
}

function getFixtureType(string $type, string $relType): string {
    if (!empty($relType)) return '';
    return match($type) {
        'string' => 'word',
        'text' => 'paragraph',
        'integer' => 'randomNumber',
        'boolean' => 'boolean',
        'datetime' => 'dateTime',
        'date' => 'date',
        default => 'word',
    };
}
