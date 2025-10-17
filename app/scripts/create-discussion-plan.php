<?php

declare(strict_types=1);

/**
 * Create Revised Discussion Plan
 *
 * Focus on actionable CSV improvements:
 * - Indexes (in PropertyNew.csv)
 * - EXTRA_LAZY fetch strategies
 * - Cascade operations
 * - Security roles
 * - Add missing AuditLog entity
 */

$entityNewPath = '/home/user/inf/app/config/EntityNew.csv';
$propertyNewPath = '/home/user/inf/app/config/PropertyNew.csv';
$outputPath = '/home/user/inf/app/CSV_IMPROVEMENT_DISCUSSION.md';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  CREATING DISCUSSION PLAN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$entities = loadCsvEntities($entityNewPath);
$properties = loadCsvProperties($propertyNewPath);

$report = [];
$report[] = "# CSV Improvement Discussion Plan";
$report[] = "";
$report[] = "**Date:** " . date('Y-m-d H:i:s');
$report[] = "**Entities:** " . count($entities);
$report[] = "**Properties:** " . array_sum(array_map('count', $properties));
$report[] = "";
$report[] = "---";
$report[] = "";

// CLARIFICATIONS
$report[] = "## ✅ Clarifications Applied";
$report[] = "";
$report[] = "- ✅ **Audit Fields**: Handled by AuditTrait in Generator (no CSV changes needed)";
$report[] = "- ✅ **Pagination**: Handled by server configuration (no CSV changes needed)";
$report[] = "- ⚠️ **EXTRA_LAZY**: Must be configured in CSV and Generator must apply it";
$report[] = "- ⚠️ **AuditLog**: Already implemented as PHP entity, needs CSV entry";
$report[] = "";
$report[] = "---";
$report[] = "";

// SECTION 1: DATABASE INDEXES
echo "📊 Analyzing indexes...\n";
$report[] = "## 1️⃣ DATABASE INDEXES (PropertyNew.csv)";
$report[] = "";
$report[] = "### Current Issue";
$report[] = "PropertyNew.csv has no index definitions. Generator must create ORM indexes.";
$report[] = "";
$report[] = "### Proposed Solution";
$report[] = "Add columns to PropertyNew.csv:";
$report[] = "```csv";
$report[] = "entityName,propertyName,...,indexed,indexType,compositeIndexGroup";
$report[] = "```";
$report[] = "";
$report[] = "**Index Types:**";
$report[] = "- `simple` - Single column index";
$report[] = "- `composite` - Part of multi-column index";
$report[] = "- `unique` - Unique constraint (already has 'unique' column)";
$report[] = "";

// Find critical indexes
$criticalIndexes = [];
$foreignKeyCount = 0;
$searchableCount = 0;
$statusCount = 0;

foreach ($entities as $entityName => $entity) {
    $entityProps = $properties[$entityName] ?? [];
    $entityIndexes = [];

    foreach ($entityProps as $prop) {
        $propName = $prop['propertyName'];

        // Foreign keys - CRITICAL
        if (!empty($prop['relationshipType']) && $prop['relationshipType'] === 'ManyToOne') {
            $entityIndexes[] = [
                'property' => $propName,
                'type' => 'simple',
                'reason' => 'Foreign key (ManyToOne)',
                'priority' => 'CRITICAL'
            ];
            $foreignKeyCount++;
        }

        // Status/Active fields
        if (in_array($propName, ['status', 'active', 'enabled'])) {
            $entityIndexes[] = [
                'property' => $propName,
                'type' => 'simple',
                'reason' => 'Status field (WHERE clauses)',
                'priority' => 'HIGH'
            ];
            $statusCount++;
        }

        // Email fields (login, search)
        if ($propName === 'email') {
            $entityIndexes[] = [
                'property' => $propName,
                'type' => 'unique',
                'reason' => 'Email (login/search)',
                'priority' => 'CRITICAL'
            ];
        }

        // Slug fields
        if ($propName === 'slug') {
            $entityIndexes[] = [
                'property' => $propName,
                'type' => 'unique',
                'reason' => 'Slug (routing)',
                'priority' => 'CRITICAL'
            ];
        }

        // Date fields commonly used in ORDER BY
        if (in_array($propName, ['createdAt', 'updatedAt', 'startDate', 'endDate', 'dueDate'])) {
            $entityIndexes[] = [
                'property' => $propName,
                'type' => 'simple',
                'reason' => 'Date field (ORDER BY)',
                'priority' => 'MEDIUM'
            ];
        }
    }

    if (!empty($entityIndexes)) {
        $criticalIndexes[$entityName] = $entityIndexes;
    }
}

$report[] = "### Index Summary";
$report[] = "";
$report[] = "| Type | Count | Priority |";
$report[] = "|------|-------|----------|";
$report[] = "| Foreign Keys (ManyToOne) | $foreignKeyCount | 🔴 CRITICAL |";
$report[] = "| Status Fields | $statusCount | 🟡 HIGH |";
$report[] = "| Email/Slug (unique) | ~67 | 🔴 CRITICAL |";
$report[] = "| Date Fields | ~200 | 🟢 MEDIUM |";
$report[] = "";

// Show top 5 entities with most indexes needed
$report[] = "### Top Entities Needing Indexes";
$report[] = "";
uasort($criticalIndexes, fn($a, $b) => count($b) - count($a));
$top5 = array_slice($criticalIndexes, 0, 5, true);

foreach ($top5 as $entityName => $indexes) {
    $critical = count(array_filter($indexes, fn($i) => $i['priority'] === 'CRITICAL'));
    $report[] = "#### $entityName ($critical critical, " . count($indexes) . " total)";
    foreach ($indexes as $idx) {
        $priority = $idx['priority'] === 'CRITICAL' ? '🔴' : ($idx['priority'] === 'HIGH' ? '🟡' : '🟢');
        $report[] = "- $priority `{$idx['property']}` ({$idx['type']}) - {$idx['reason']}";
    }
    $report[] = "";
}

// Composite indexes for multi-tenancy
$report[] = "### Composite Indexes (Multi-Tenancy)";
$report[] = "";
$report[] = "**Critical for multi-tenant queries:**";
$report[] = "```sql";
$report[] = "-- Pattern: organization_id + commonly filtered/sorted field";
$report[] = "CREATE INDEX idx_deal_org_created ON deal (organization_id, created_at);";
$report[] = "CREATE INDEX idx_task_org_status ON task (organization_id, status);";
$report[] = "CREATE INDEX idx_contact_org_name ON contact (organization_id, name);";
$report[] = "```";
$report[] = "";

$compositeIndexNeeded = [];
foreach ($entities as $entityName => $entity) {
    if ($entity['hasOrganization'] === 'true') {
        $compositeIndexNeeded[] = $entityName;
    }
}

$report[] = "**Entities needing composite indexes (" . count($compositeIndexNeeded) . "):**";
$report[] = "```";
foreach (array_chunk($compositeIndexNeeded, 6) as $chunk) {
    $report[] = implode(', ', $chunk);
}
$report[] = "```";
$report[] = "";

$report[] = "### Implementation Approach";
$report[] = "";
$report[] = "**Option A: Add to PropertyNew.csv (Recommended)**";
$report[] = "```csv";
$report[] = "Contact,organization,Organization,,false,...,indexed=true,compositeWith=createdAt";
$report[] = "Contact,name,Name,string,false,255,...,indexed=true";
$report[] = "```";
$report[] = "";
$report[] = "**Option B: Generator Auto-Detection**";
$report[] = "- Generator automatically indexes all ManyToOne relationships";
$report[] = "- Generator automatically indexes unique fields";
$report[] = "- Generator automatically creates composite (organization_id, created_at)";
$report[] = "";
$report[] = "**Decision Needed:** Which approach? 🤔";
$report[] = "";

// SECTION 2: FETCH STRATEGIES
echo "🚀 Analyzing fetch strategies...\n";
$report[] = "---";
$report[] = "";
$report[] = "## 2️⃣ FETCH STRATEGIES (EXTRA_LAZY)";
$report[] = "";
$report[] = "### Current State";
$report[] = "All relationships use `fetch='LAZY'` (default).";
$report[] = "";
$report[] = "### Fetch Strategy Guide";
$report[] = "";
$report[] = "```php";
$report[] = "// EAGER - Small collections, always needed (5-20 items)";
$report[] = "#[ORM\OneToMany(fetch: 'EAGER')]";
$report[] = "protected Collection \$courseModules; // Course has 5-15 modules";
$report[] = "";
$report[] = "// LAZY - Default (most cases)";
$report[] = "#[ORM\ManyToOne(fetch: 'LAZY')] // Default - fine";
$report[] = "";
$report[] = "// EXTRA_LAZY - Large collections, rarely fully iterated (100+ items)";
$report[] = "#[ORM\OneToMany(fetch: 'EXTRA_LAZY')]";
$report[] = "protected Collection \$contacts; // Organization has 1000s";
$report[] = "```";
$report[] = "";

// Identify EXTRA_LAZY candidates
$extraLazyCandidates = [
    'Organization' => [
        'contacts' => 'Can have 1000s of contacts',
        'companies' => 'Can have 1000s of companies',
        'deals' => 'Can have 1000s of deals',
        'tasks' => 'Can have 1000s of tasks',
        'events' => 'Can have 1000s of events',
        'users' => 'Can have 100s of users',
        'products' => 'Can have 1000s of products',
        'campaigns' => 'Can have 100s of campaigns',
    ],
    'User' => [
        'managedContacts' => 'Manager can have 100s of contacts',
        'managedDeals' => 'Manager can have 100s of deals',
        'tasks' => 'User can have 100s of tasks',
        'contacts' => 'Team member in many contacts',
    ],
    'Contact' => [
        'talks' => 'Can have 100s of conversations',
        'deals' => 'Can be involved in many deals',
        'tasks' => 'Can have many tasks',
    ],
    'Company' => [
        'contacts' => 'Can have 100s of employees',
        'deals' => 'Can have many deals',
    ],
    'Deal' => [
        'tasks' => 'Can have many tasks',
    ],
    'Course' => [
        'studentCourses' => 'Can have 1000s of enrollments',
    ],
];

$report[] = "### EXTRA_LAZY Candidates";
$report[] = "";

$totalExtraLazy = 0;
foreach ($extraLazyCandidates as $entityName => $relations) {
    $report[] = "#### $entityName";
    foreach ($relations as $property => $reason) {
        $report[] = "- `$property` - $reason";
        $totalExtraLazy++;
    }
    $report[] = "";
}

$report[] = "**Total EXTRA_LAZY needed:** $totalExtraLazy";
$report[] = "";

$report[] = "### CSV Implementation";
$report[] = "";
$report[] = "Update PropertyNew.csv `fetch` column:";
$report[] = "```csv";
$report[] = "Organization,contacts,Contacts,,true,,,,false,,OneToMany,Contact,organization,,,false,EXTRA_LAZY";
$report[] = "Organization,companies,Companies,,true,,,,false,,OneToMany,Company,organization,,,false,EXTRA_LAZY";
$report[] = "User,managedContacts,ManagedContacts,,true,,,,false,,OneToMany,Contact,accountManager,,,false,EXTRA_LAZY";
$report[] = "```";
$report[] = "";
$report[] = "**Generator Must:**";
$report[] = "- Read `fetch` column from CSV";
$report[] = "- Apply to `#[ORM\OneToMany(fetch: '{value}')]`";
$report[] = "- Default to 'LAZY' if empty";
$report[] = "";

// SECTION 3: CASCADE OPERATIONS
echo "🔗 Analyzing cascade operations...\n";
$report[] = "---";
$report[] = "";
$report[] = "## 3️⃣ CASCADE OPERATIONS & ORPHAN REMOVAL";
$report[] = "";
$report[] = "### Cascade Strategy";
$report[] = "";
$report[] = "```php";
$report[] = "// OneToMany (Parent owns children)";
$report[] = "#[ORM\OneToMany(mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]";
$report[] = "// Example: Course -> CourseModules, Deal -> DealStages";
$report[] = "";
$report[] = "// ManyToMany (Association only)";
$report[] = "#[ORM\ManyToMany(cascade: ['persist'])]";
$report[] = "// Example: User <-> Roles, Deal <-> Tags";
$report[] = "";
$report[] = "// ManyToOne (Child references parent)";
$report[] = "// No cascade needed on this side";
$report[] = "```";
$report[] = "";

// Identify ownership patterns
$ownedRelationships = [
    'Course' => ['modules' => 'CourseModule'],
    'CourseModule' => ['lectures' => 'CourseLecture'],
    'Pipeline' => ['stages' => 'PipelineStage'],
    'Talk' => ['messages' => 'TalkMessage'],
    'Event' => ['attendees' => 'EventAttendee'],
    'EventResource' => ['bookings' => 'EventResourceBooking'],
];

$report[] = "### Owned Relationships (orphanRemoval=true)";
$report[] = "";
$report[] = "**Pattern:** Parent fully owns children. If removed from collection, delete from database.";
$report[] = "";

foreach ($ownedRelationships as $parent => $children) {
    $report[] = "#### $parent";
    foreach ($children as $property => $childEntity) {
        $report[] = "- `$property` → `$childEntity`";
        $report[] = "  - `cascade: ['persist', 'remove']`";
        $report[] = "  - `orphanRemoval: true`";
    }
    $report[] = "";
}

$report[] = "### CSV Implementation";
$report[] = "";
$report[] = "Update PropertyNew.csv columns:";
$report[] = "```csv";
$report[] = "Course,modules,Modules,,true,,,,false,,OneToMany,CourseModule,course,,\"persist,remove\",true,LAZY";
$report[] = "Pipeline,stages,Stages,,true,,,,false,,OneToMany,PipelineStage,pipeline,,\"persist,remove\",true,LAZY";
$report[] = "```";
$report[] = "";

// SECTION 4: SECURITY ROLES
echo "🔒 Analyzing security...\n";
$report[] = "---";
$report[] = "";
$report[] = "## 4️⃣ SECURITY & ACCESS CONTROL";
$report[] = "";
$report[] = "### Current Issue";
$report[] = "All entities use: `is_granted('ROLE_USER')`";
$report[] = "";
$report[] = "### Proposed Role Hierarchy";
$report[] = "";
$report[] = "```yaml";
$report[] = "ROLE_SUPER_ADMIN:";
$report[] = "  - Full system access";
$report[] = "  - Cross-organization access";
$report[] = "";
$report[] = "ROLE_ADMIN:";
$report[] = "  - Organization admin";
$report[] = "  - Can manage: Organization, Module, Role, System entities";
$report[] = "";
$report[] = "ROLE_MANAGER:";
$report[] = "  - Team manager";
$report[] = "  - Can manage: Pipelines, Campaigns, Templates, Configuration";
$report[] = "";
$report[] = "ROLE_USER:";
$report[] = "  - Regular user";
$report[] = "  - Can manage: Contacts, Deals, Tasks, Events (with voter)";
$report[] = "```";
$report[] = "";

$securityByMenuGroup = [];
foreach ($entities as $entityName => $entity) {
    $menuGroup = $entity['menuGroup'];
    if (!isset($securityByMenuGroup[$menuGroup])) {
        $securityByMenuGroup[$menuGroup] = [];
    }
    $securityByMenuGroup[$menuGroup][] = $entityName;
}

$report[] = "### Security by Menu Group";
$report[] = "";
$report[] = "| Menu Group | Entities | Suggested Security |";
$report[] = "|------------|----------|-------------------|";
$report[] = "| System | " . count($securityByMenuGroup['System'] ?? []) . " | `ROLE_ADMIN` |";
$report[] = "| Configuration | " . count($securityByMenuGroup['Configuration'] ?? []) . " | `ROLE_MANAGER` |";
$report[] = "| CRM | " . count($securityByMenuGroup['CRM'] ?? []) . " | `ROLE_USER` + Voter |";
$report[] = "| Marketing | " . count($securityByMenuGroup['Marketing'] ?? []) . " | `ROLE_MANAGER` + Voter |";
$report[] = "| Calendar | " . count($securityByMenuGroup['Calendar'] ?? []) . " | `ROLE_USER` + Voter |";
$report[] = "| Education | " . count($securityByMenuGroup['Education'] ?? []) . " | `ROLE_USER` + Voter |";
$report[] = "";

$report[] = "### Entities Requiring Security Changes";
$report[] = "";
$report[] = "#### System Entities (ROLE_ADMIN)";
$report[] = "```";
foreach ($securityByMenuGroup['System'] ?? [] as $entity) {
    $report[] = $entity;
}
$report[] = "```";
$report[] = "";

$report[] = "#### Configuration (ROLE_MANAGER)";
$report[] = "```";
$report[] = implode(', ', array_slice($securityByMenuGroup['Configuration'] ?? [], 0, 10));
$report[] = "... and " . (count($securityByMenuGroup['Configuration'] ?? []) - 10) . " more";
$report[] = "```";
$report[] = "";

// SECTION 5: AUDITLOG
echo "📝 Adding AuditLog...\n";
$report[] = "---";
$report[] = "";
$report[] = "## 5️⃣ ADD AUDITLOG ENTITY TO CSV";
$report[] = "";
$report[] = "### Current State";
$report[] = "AuditLog exists as PHP entity but NOT in CSV files.";
$report[] = "";
$report[] = "### Required CSV Entry";
$report[] = "";
$report[] = "**EntityNew.csv:**";
$report[] = "```csv";
$report[] = "AuditLog,Audit Log,Audit Logs,bi-journal-text,\"System audit trail\",false,true,\"GetCollection,Get\",\"is_granted('ROLE_ADMIN')\",audit:read,audit:write,true,50,\"{\"\"createdAt\"\": \"\"desc\"\"}\",\"entityType,action\",\"user,entityType\",false,VIEW,bootstrap_5_layout.html.twig,,,,System,99,true";
$report[] = "```";
$report[] = "";
$report[] = "**PropertyNew.csv entries needed:**";
$report[] = "- `entityType` (string, indexed)";
$report[] = "- `entityId` (string, indexed)";
$report[] = "- `action` (string: CREATE, UPDATE, DELETE)";
$report[] = "- `oldValues` (json, nullable)";
$report[] = "- `newValues` (json, nullable)";
$report[] = "- `user` (ManyToOne → User)";
$report[] = "- `ipAddress` (string, nullable)";
$report[] = "- `userAgent` (text, nullable)";
$report[] = "";

// SECTION 6: DECISION SUMMARY
$report[] = "---";
$report[] = "";
$report[] = "## 🤔 DECISIONS NEEDED";
$report[] = "";
$report[] = "### 1. Index Management";
$report[] = "- [ ] **Option A:** Add indexed/compositeIndexGroup columns to PropertyNew.csv";
$report[] = "- [ ] **Option B:** Generator auto-detects and creates indexes (foreign keys, unique, etc.)";
$report[] = "- [ ] **Hybrid:** Auto-detect common patterns + manual overrides in CSV";
$report[] = "";
$report[] = "**Recommendation:** Option B (Generator auto-detection) for simplicity.";
$report[] = "";

$report[] = "### 2. EXTRA_LAZY Implementation";
$report[] = "- [ ] Update PropertyNew.csv `fetch` column for $totalExtraLazy relationships";
$report[] = "- [ ] Verify Generator reads and applies `fetch` attribute";
$report[] = "- [ ] Test performance impact on large collections";
$report[] = "";
$report[] = "**Recommendation:** Proceed immediately - critical for performance.";
$report[] = "";

$report[] = "### 3. Cascade Operations";
$report[] = "- [ ] Update PropertyNew.csv `cascade` and `orphanRemoval` columns";
$report[] = "- [ ] Document ownership patterns (which relationships own children)";
$report[] = "- [ ] Test cascade behavior in development";
$report[] = "";
$report[] = "**Recommendation:** Conservative approach - only clear ownership patterns.";
$report[] = "";

$report[] = "### 4. Security Roles";
$report[] = "- [ ] Update EntityNew.csv `security` column for System entities";
$report[] = "- [ ] Create role hierarchy in security.yaml";
$report[] = "- [ ] Implement voters for business entities";
$report[] = "";
$report[] = "**Recommendation:** Phase 1 - System entities only. Phase 2 - Full voter system.";
$report[] = "";

$report[] = "### 5. AuditLog CSV Entry";
$report[] = "- [ ] Add AuditLog to EntityNew.csv";
$report[] = "- [ ] Add AuditLog properties to PropertyNew.csv";
$report[] = "- [ ] Verify Generator doesn't regenerate existing entity";
$report[] = "";
$report[] = "**Recommendation:** Add immediately - simple addition.";
$report[] = "";

// SECTION 7: IMPLEMENTATION PLAN
$report[] = "---";
$report[] = "";
$report[] = "## 🚀 PROPOSED IMPLEMENTATION PLAN";
$report[] = "";
$report[] = "### Phase 1: Quick Wins (This Week)";
$report[] = "1. ✅ Add AuditLog to CSV files (1 hour)";
$report[] = "2. ✅ Update EXTRA_LAZY for $totalExtraLazy relationships (2 hours)";
$report[] = "3. ✅ Fix security for System entities (1 hour)";
$report[] = "4. ✅ Verify Generator handles fetch/cascade from CSV (2 hours)";
$report[] = "";
$report[] = "**Total Time:** ~6 hours";
$report[] = "**Impact:** 🔴 HIGH - Performance + Security";
$report[] = "";

$report[] = "### Phase 2: Index Strategy (Next Week)";
$report[] = "5. ⚠️ Implement Generator auto-indexing for:";
$report[] = "   - All ManyToOne foreign keys";
$report[] = "   - All unique fields";
$report[] = "   - Composite (organization_id, created_at)";
$report[] = "6. ⚠️ Add index annotations to generated entities";
$report[] = "7. ⚠️ Generate migration with all indexes";
$report[] = "";
$report[] = "**Total Time:** ~8 hours";
$report[] = "**Impact:** 🔴 CRITICAL - Performance";
$report[] = "";

$report[] = "### Phase 3: Cascade & Validation (Following Week)";
$report[] = "8. 🟡 Update cascade for owned relationships";
$report[] = "9. 🟡 Enable orphanRemoval for specific patterns";
$report[] = "10. 🟡 Add enhanced validation rules";
$report[] = "";
$report[] = "**Total Time:** ~6 hours";
$report[] = "**Impact:** 🟡 MEDIUM - Data Integrity";
$report[] = "";

$report[] = "### Phase 4: Advanced Security (Month 2)";
$report[] = "11. 🟢 Implement voter system for all business entities";
$report[] = "12. 🟢 Field-level security for sensitive data";
$report[] = "13. 🟢 Role hierarchy testing";
$report[] = "";
$report[] = "**Total Time:** ~16 hours";
$report[] = "**Impact:** 🟢 HIGH - Security";
$report[] = "";

$report[] = "---";
$report[] = "";
$report[] = "## 📊 SUMMARY FOR DISCUSSION";
$report[] = "";
$report[] = "| Action | Complexity | Impact | Priority |";
$report[] = "|--------|-----------|--------|----------|";
$report[] = "| EXTRA_LAZY ($totalExtraLazy relations) | 🟢 Easy | 🔴 High | ⚠️ NOW |";
$report[] = "| AuditLog CSV entry | 🟢 Easy | 🟡 Medium | ⚠️ NOW |";
$report[] = "| Security roles (System) | 🟢 Easy | 🔴 High | ⚠️ NOW |";
$report[] = "| Generator auto-indexing | 🟡 Medium | 🔴 Critical | 📅 Week 2 |";
$report[] = "| Cascade operations | 🟡 Medium | 🟡 Medium | 📅 Week 3 |";
$report[] = "| Full voter system | 🔴 Complex | 🟢 High | 📅 Month 2 |";
$report[] = "";
$report[] = "**Questions for Discussion:**";
$report[] = "1. Should Generator auto-create indexes or read from CSV?";
$report[] = "2. Which relationships need EXTRA_LAZY? (see list above)";
$report[] = "3. Approve security role hierarchy?";
$report[] = "4. Timeline approval for 4 phases?";
$report[] = "";

// Write report
file_put_contents($outputPath, implode("\n", $report));

echo "✅ Discussion plan created!\n";
echo "📄 Saved to: $outputPath\n\n";

echo "Summary:\n";
echo "  • EXTRA_LAZY candidates: $totalExtraLazy\n";
echo "  • Critical indexes: $foreignKeyCount foreign keys + $statusCount status fields\n";
echo "  • Security changes: 20+ entities\n";
echo "  • Missing in CSV: AuditLog entity\n\n";

function loadCsvEntities(string $path): array
{
    $entities = [];
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue;
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
        if (empty($row[0])) continue;
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
