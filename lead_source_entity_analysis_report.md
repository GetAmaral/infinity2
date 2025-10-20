# LeadSource Entity Analysis Report

**Date:** 2025-10-19
**Database:** PostgreSQL 18
**Entity Status:** Defined in Genmax but NOT Generated
**Generator ID:** `0199cadd-639e-72a6-9079-52f3b283277e`

---

## Executive Summary

The LeadSource entity is currently defined in the Genmax code generator database but has NOT been generated into PHP code yet. The current implementation has significant gaps when compared to 2025 CRM lead source attribution best practices. This report identifies 12 critical issues and provides specific recommendations for implementing a production-ready lead source tracking system.

**Status:** `is_generated = false` - Entity exists only as metadata in `generator_entity` table.

---

## Current Implementation

### Entity Configuration

| Field | Current Value | Status |
|-------|---------------|--------|
| Entity Name | LeadSource | OK |
| Entity Label | LeadSource | NEEDS IMPROVEMENT (use "Lead Source") |
| Plural Label | LeadSources | NEEDS IMPROVEMENT (use "Lead Sources") |
| Icon | bi-funnel | OK |
| Description | Lead sources for tracking where prospects come from | OK |
| Has Organization | true | OK |
| API Enabled | true | OK |
| API Operations | GetCollection, Get, Post, Put, Delete | OK |
| API Security | is_granted('ROLE_CRM_ADMIN') | TOO RESTRICTIVE |
| API Default Order | {"createdAt":"desc"} | NEEDS IMPROVEMENT |
| Menu Group | Marketing | OK |
| Menu Order | 2 | OK |
| Color | #fd7e14 (Orange) | OK |
| Tags | ["marketing", "lead", "tracking"] | OK |
| Voter Enabled | true | OK |

### Current Properties (6 Properties)

| Property | Type | Nullable | Unique | Validation | Issues |
|----------|------|----------|--------|------------|--------|
| name | string | No | No | NotBlank | Missing length, no uniqueness constraint |
| description | text | Yes | No | None | OK but not searchable |
| group | string | Yes | No | None | Vague name, missing length |
| active | boolean | Yes | No | None | WRONG: Should be NOT NULL with default |
| organization | ManyToOne | Yes | No | None | Relationship OK |
| deals | OneToMany | Yes | No | None | Relationship OK (inverse side) |

---

## Critical Issues Identified

### 1. NAMING CONVENTION VIOLATION - Boolean Field

**Issue:** Property is named `active` instead of following boolean conventions.

**Current:**
```sql
property_name: active
property_type: boolean
nullable: true
```

**Expected Conventions:**
- Boolean fields should use descriptive names: `active`, `default`, `enabled`
- NOT prefixed with "is": `isActive`, `isDefault`, `isEnabled`

**Assessment:** The naming is CORRECT (`active` not `isActive`), but the field is nullable which is WRONG.

**Fix Required:**
```sql
property_name: active
property_type: boolean
nullable: false  -- MUST be NOT NULL
default_value: true
```

### 2. MISSING DEFAULT LEAD SOURCE FLAG

**Issue:** No `default` boolean field to mark the default lead source.

**Impact:** Users cannot set a fallback lead source for unknown/untagged leads.

**Fix Required:**
```sql
property_name: default
property_label: Default Source
property_type: boolean
nullable: false
default_value: false
unique: false
validation_rules: []
```

**Database Constraint:** Add CHECK constraint to ensure only ONE row has `default = true` per organization.

### 3. INSUFFICIENT FIELD STRUCTURE

**Issue:** Missing critical fields for modern lead attribution in 2025.

**Current Fields:** name, description, group, active
**Missing Fields:** 13+ critical fields

#### Missing Fields Based on 2025 Best Practices:

| Missing Field | Type | Purpose | Priority |
|---------------|------|---------|----------|
| `sourceName` | string(100) | Primary identifier (e.g., "Google Ads", "LinkedIn") | CRITICAL |
| `category` | string(50) | High-level grouping (e.g., "Paid", "Organic", "Referral") | CRITICAL |
| `subcategory` | string(100) | Detailed classification (e.g., "Social Media", "Search Engine") | HIGH |
| `medium` | string(50) | UTM medium (e.g., "cpc", "email", "social") | CRITICAL |
| `campaign` | string(255) | Campaign identifier for tracking | HIGH |
| `costPerLead` | decimal(10,2) | Cost per lead for ROI calculations | HIGH |
| `totalCost` | decimal(10,2) | Total investment in this source | MEDIUM |
| `totalLeads` | integer | Total leads generated (denormalized) | MEDIUM |
| `conversionRate` | decimal(5,2) | Lead-to-customer conversion percentage | MEDIUM |
| `utmSource` | string(100) | UTM source parameter | HIGH |
| `utmMedium` | string(50) | UTM medium parameter | HIGH |
| `utmCampaign` | string(255) | UTM campaign parameter | HIGH |
| `utmContent` | string(255) | UTM content parameter (A/B test tracking) | LOW |
| `utmTerm` | string(100) | UTM term parameter (paid keyword) | LOW |
| `trackingUrl` | string(500) | Full tracking URL with parameters | MEDIUM |
| `isFirstTouch` | boolean | Track first touchpoint attribution | HIGH |
| `isLastTouch` | boolean | Track last touchpoint attribution | HIGH |
| `color` | string(7) | Hex color for UI visualization | LOW |
| `icon` | string(50) | Icon identifier for UI | LOW |
| `externalId` | string(100) | External system ID (Google Ads, Facebook) | MEDIUM |
| `apiKey` | string(255) | API key for integration (encrypted) | LOW |
| `lastSyncedAt` | datetime_immutable | Last sync with external platform | LOW |

### 4. NO UNIQUENESS CONSTRAINTS

**Issue:** The `name` field should be unique per organization to prevent duplicates.

**Current:**
```sql
name: string, nullable=false, unique=false
```

**Fix Required:**
```sql
name: string(100), nullable=false, unique=true
-- Or composite unique constraint: (organization_id, name)
```

### 5. MISSING STRING LENGTH CONSTRAINTS

**Issue:** String fields `name` and `group` have no length constraints.

**Current:**
```sql
name: string, length=NULL
group: string, length=NULL
```

**Fix Required:**
```sql
name: string(100)
group: string(50)
```

### 6. OVERLY RESTRICTIVE API SECURITY

**Issue:** API requires `ROLE_CRM_ADMIN` for ALL operations, preventing CRM users from viewing.

**Current:**
```yaml
api_security: "is_granted('ROLE_CRM_ADMIN')"
```

**Fix Required:**
```yaml
api_security: "is_granted('ROLE_USER')"
operation_security:
  GetCollection: "is_granted('ROLE_USER')"
  Get: "is_granted('ROLE_USER')"
  Post: "is_granted('ROLE_CRM_ADMIN')"
  Put: "is_granted('ROLE_CRM_ADMIN')"
  Delete: "is_granted('ROLE_CRM_ADMIN')"
```

### 7. MISSING API FILTERS

**Issue:** No API Platform filters configured for searchability.

**Current State:**
```sql
filter_searchable: false (all properties)
filter_orderable: false (all properties)
filter_strategy: NULL
```

**Fix Required:**

| Property | Filter Strategy | Searchable | Orderable | Boolean | Date |
|----------|----------------|------------|-----------|---------|------|
| name | partial | true | true | false | false |
| sourceName | partial | true | true | false | false |
| category | exact | true | true | false | false |
| subcategory | exact | true | true | false | false |
| medium | exact | true | true | false | false |
| active | - | false | true | true | false |
| default | - | false | true | true | false |
| createdAt | - | false | true | false | true |
| updatedAt | - | false | true | false | true |
| costPerLead | - | false | true | false | false |
| conversionRate | - | false | true | false | false |

### 8. IMPROPER API DEFAULT ORDER

**Issue:** Ordering by `createdAt DESC` is not user-friendly for lead sources.

**Current:**
```json
{"createdAt":"desc"}
```

**Fix Required:**
```json
{"category":"asc", "name":"asc"}
```

Rationale: Users want to see lead sources grouped by category and alphabetically sorted.

### 9. MISSING VALIDATION RULES

**Issue:** Most fields have no validation rules.

**Current:**
```sql
name: ["NotBlank"]
description: []
group: []
active: []
```

**Fix Required:**

```json
// name
["NotBlank", {"Length": {"max": 100}}]

// sourceName
["NotBlank", {"Length": {"max": 100}}]

// category
["NotBlank", {"Length": {"max": 50}}, {"Choice": {"choices": ["Paid", "Organic", "Referral", "Direct", "Partner", "Other"]}}]

// subcategory
[{"Length": {"max": 100}}]

// medium
[{"Length": {"max": 50}}]

// costPerLead
[{"GreaterThanOrEqual": {"value": 0}}]

// conversionRate
[{"Range": {"min": 0, "max": 100}}]

// utmSource
[{"Length": {"max": 100}}]

// trackingUrl
[{"Url": {}}, {"Length": {"max": 500}}]
```

### 10. NO ENUM FOR CATEGORY FIELD

**Issue:** Category should be an enum to prevent inconsistent data entry.

**Fix Required:**
```sql
is_enum: true
enum_class: 'App\\Enum\\LeadSourceCategory'
enum_values: ["Paid", "Organic", "Referral", "Direct", "Partner", "Event", "Other"]
```

**PHP Enum:**
```php
namespace App\Enum;

enum LeadSourceCategory: string
{
    case PAID = 'Paid';
    case ORGANIC = 'Organic';
    case REFERRAL = 'Referral';
    case DIRECT = 'Direct';
    case PARTNER = 'Partner';
    case EVENT = 'Event';
    case OTHER = 'Other';
}
```

### 11. MISSING DATABASE INDEXES

**Issue:** No indexes defined for frequently queried fields.

**Fix Required:**

| Property | Index Type | Composite With | Justification |
|----------|-----------|----------------|---------------|
| name | btree | organization_id | Unique constraint + filtering |
| category | btree | - | Filtering and grouping |
| active | btree | - | Boolean filtering |
| default | btree | organization_id | Finding default per org |
| medium | btree | - | UTM tracking queries |
| utmSource | btree | - | UTM tracking queries |
| createdAt | btree | - | Sorting and range queries |

```sql
indexed: true
index_type: 'btree'
composite_index_with: ["organization_id"]  -- for name, default
```

### 12. MISSING SOFT DELETE SUPPORT

**Issue:** No soft delete tracking for lead sources.

**Fix Required:**
```sql
property_name: deletedAt
property_label: Deleted At
property_type: datetime_immutable
nullable: true
default_value: null
api_readable: false
api_writable: false
show_in_list: false
show_in_form: false
```

Add to Entity configuration:
```sql
-- Doctrine filter will handle soft deletes automatically
```

---

## Industry Best Practices (2025)

### Multi-Field Attribution Model

Modern CRM systems use a **multi-field approach** for lead attribution:

1. **Lead Source** (7-15 values): High-level category aligned with `utm_medium`
   - Examples: Paid Search, Organic Search, Social Media, Email, Referral, Event, Direct

2. **Lead Source Detail**: Specific source aligned with `utm_source`
   - Examples: Google, LinkedIn, Facebook, Partner XYZ, Conference 2025

3. **Lead Source Type**: Attribution model
   - First Touch, Last Touch, Multi-Touch

4. **Campaign Tracking**: Full UTM parameter capture
   - utm_source, utm_medium, utm_campaign, utm_content, utm_term

5. **Cost Tracking**: ROI calculation
   - Cost per lead, total investment, conversion rate

### Attribution Rules (2025 Standards)

1. **Preserve Original Attribution**: NEVER change a contact's first lead source
2. **Automate Data Collection**: Use hidden form fields for UTM capture
3. **Standardize Naming**: Enforce taxonomy to prevent fragmentation
4. **Integrate Systems**: Sync CRM with marketing automation platforms
5. **Track Multi-Touch**: Capture all touchpoints, not just first/last
6. **Calculate ROI**: Link marketing spend to pipeline stages
7. **First-Party Data**: Rely on direct data capture due to privacy regulations

### Cost Per Lead Benchmarks (2025)

| Industry | Average CPL | Range |
|----------|-------------|-------|
| B2B SaaS | $237 | $150-$400 |
| E-Commerce | $91 | $40-$150 |
| Financial Services | $160 | $80-$300 |
| Healthcare | $210 | $120-$350 |
| Overall B2B | $100-$300 | $40-$300 |

---

## Database Schema Recommendations

### Optimized LeadSource Table Schema

```sql
CREATE TABLE lead_source (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organization(id) ON DELETE CASCADE,

    -- Core Fields
    name VARCHAR(100) NOT NULL,
    source_name VARCHAR(100) NOT NULL,  -- Display name
    category VARCHAR(50) NOT NULL,       -- Enum: Paid, Organic, Referral, etc.
    subcategory VARCHAR(100),            -- More specific classification
    description TEXT,

    -- Status Fields
    active BOOLEAN NOT NULL DEFAULT true,
    "default" BOOLEAN NOT NULL DEFAULT false,
    color VARCHAR(7),                    -- Hex color code
    icon VARCHAR(50),                    -- Icon identifier

    -- UTM Tracking
    medium VARCHAR(50),                  -- utm_medium
    utm_source VARCHAR(100),             -- utm_source
    utm_medium VARCHAR(50),              -- utm_medium
    utm_campaign VARCHAR(255),           -- utm_campaign
    utm_content VARCHAR(255),            -- utm_content (A/B testing)
    utm_term VARCHAR(100),               -- utm_term (keywords)
    tracking_url VARCHAR(500),           -- Full URL with parameters

    -- Cost & Performance Tracking
    cost_per_lead NUMERIC(10,2) CHECK (cost_per_lead >= 0),
    total_cost NUMERIC(10,2) CHECK (total_cost >= 0),
    total_leads INTEGER DEFAULT 0 CHECK (total_leads >= 0),
    conversion_rate NUMERIC(5,2) CHECK (conversion_rate >= 0 AND conversion_rate <= 100),

    -- Attribution Model
    is_first_touch BOOLEAN NOT NULL DEFAULT true,
    is_last_touch BOOLEAN NOT NULL DEFAULT false,

    -- Integration
    external_id VARCHAR(100),            -- External system ID
    api_key VARCHAR(255),                -- Encrypted API key
    last_synced_at TIMESTAMP,            -- Last sync timestamp

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP,                -- Soft delete

    -- Constraints
    CONSTRAINT uq_leadsource_org_name UNIQUE (organization_id, name),
    CONSTRAINT chk_one_default_per_org CHECK (
        -- Only one default per organization (enforced via trigger or application logic)
        -- PostgreSQL doesn't support filtered unique constraints easily, use trigger
        true
    )
);

-- Indexes for Performance
CREATE INDEX idx_leadsource_organization ON lead_source(organization_id);
CREATE INDEX idx_leadsource_category ON lead_source(category);
CREATE INDEX idx_leadsource_active ON lead_source(active);
CREATE INDEX idx_leadsource_default ON lead_source(organization_id, "default") WHERE "default" = true;
CREATE INDEX idx_leadsource_medium ON lead_source(medium);
CREATE INDEX idx_leadsource_utm_source ON lead_source(utm_source);
CREATE INDEX idx_leadsource_created ON lead_source(created_at DESC);
CREATE INDEX idx_leadsource_deleted ON lead_source(deleted_at) WHERE deleted_at IS NULL;

-- Trigger to enforce one default per organization
CREATE OR REPLACE FUNCTION enforce_single_default_leadsource()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.default = true THEN
        UPDATE lead_source
        SET "default" = false
        WHERE organization_id = NEW.organization_id
          AND id != NEW.id
          AND "default" = true;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_enforce_single_default_leadsource
BEFORE INSERT OR UPDATE ON lead_source
FOR EACH ROW
WHEN (NEW.default = true)
EXECUTE FUNCTION enforce_single_default_leadsource();

-- Full-text search index (optional, for advanced search)
CREATE INDEX idx_leadsource_fulltext ON lead_source
    USING gin(to_tsvector('english', coalesce(name, '') || ' ' || coalesce(source_name, '') || ' ' || coalesce(description, '')));
```

### Query Performance Optimization

**Common Query Patterns:**

```sql
-- 1. Get all active lead sources for organization
SELECT * FROM lead_source
WHERE organization_id = ? AND active = true AND deleted_at IS NULL
ORDER BY category, name;
-- Uses: idx_leadsource_organization, idx_leadsource_active

-- 2. Get default lead source for organization
SELECT * FROM lead_source
WHERE organization_id = ? AND "default" = true AND deleted_at IS NULL
LIMIT 1;
-- Uses: idx_leadsource_default

-- 3. Find lead source by UTM parameters
SELECT * FROM lead_source
WHERE organization_id = ?
  AND utm_source = ?
  AND utm_medium = ?
  AND deleted_at IS NULL;
-- Uses: idx_leadsource_utm_source, idx_leadsource_medium

-- 4. Lead source performance report
SELECT
    category,
    COUNT(*) as source_count,
    SUM(total_leads) as total_leads,
    AVG(cost_per_lead) as avg_cpl,
    AVG(conversion_rate) as avg_conversion
FROM lead_source
WHERE organization_id = ? AND deleted_at IS NULL
GROUP BY category
ORDER BY total_leads DESC;
-- Uses: idx_leadsource_organization

-- 5. Full-text search
SELECT * FROM lead_source
WHERE organization_id = ?
  AND deleted_at IS NULL
  AND to_tsvector('english', coalesce(name, '') || ' ' || coalesce(source_name, '') || ' ' || coalesce(description, ''))
      @@ plainto_tsquery('english', ?);
-- Uses: idx_leadsource_fulltext
```

---

## Genmax Property Definitions

### Complete Property List (27 Properties)

Here's the complete SQL to update `generator_property` table:

```sql
-- 1. Update existing 'name' property
UPDATE generator_property SET
    length = 100,
    "unique" = true,
    validation_rules = '["NotBlank", {"Length": {"max": 100}}]'::json,
    filter_strategy = 'partial',
    filter_searchable = true,
    filter_orderable = true,
    indexed = true,
    index_type = 'btree',
    composite_index_with = '["organization_id"]'::json
WHERE entity_id = '0199cadd-639e-72a6-9079-52f3b283277e' AND property_name = 'name';

-- 2. Update existing 'description' property
UPDATE generator_property SET
    filter_strategy = 'partial',
    filter_searchable = true,
    filter_orderable = false
WHERE entity_id = '0199cadd-639e-72a6-9079-52f3b283277e' AND property_name = 'description';

-- 3. Update existing 'group' property - RENAME to 'category'
UPDATE generator_property SET
    property_name = 'category',
    property_label = 'Category',
    length = 50,
    nullable = false,
    validation_rules = '["NotBlank", {"Length": {"max": 50}}]'::json,
    filter_strategy = 'exact',
    filter_searchable = true,
    filter_orderable = true,
    indexed = true,
    index_type = 'btree',
    is_enum = true,
    enum_class = 'App\\Enum\\LeadSourceCategory',
    enum_values = '["Paid", "Organic", "Referral", "Direct", "Partner", "Event", "Other"]'::json
WHERE entity_id = '0199cadd-639e-72a6-9079-52f3b283277e' AND property_name = 'group';

-- 4. Fix existing 'active' property
UPDATE generator_property SET
    nullable = false,
    default_value = 'true'::json,
    validation_rules = '[]'::json,
    filter_boolean = true,
    filter_orderable = true,
    indexed = true,
    index_type = 'btree'
WHERE entity_id = '0199cadd-639e-72a6-9079-52f3b283277e' AND property_name = 'active';

-- 5. Add 'default' property (NEW)
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value, validation_rules,
    filter_boolean, filter_orderable, indexed, index_type,
    composite_index_with, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES (
    uuid_generate_v7(),
    '0199cadd-639e-72a6-9079-52f3b283277e',
    'default', 'Default Source', 'boolean',
    false, false, 'false'::json, '[]'::json,
    true, true, true, 'btree',
    '["organization_id"]'::json, true, true,
    true, true, NOW(), NOW(), 5
);

-- 6. Add 'sourceName' property (NEW)
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, validation_rules,
    filter_strategy, filter_searchable, filter_orderable,
    api_readable, api_writable, show_in_list, show_in_form,
    created_at, updated_at, property_order
) VALUES (
    uuid_generate_v7(),
    '0199cadd-639e-72a6-9079-52f3b283277e',
    'sourceName', 'Source Name', 'string',
    100, false, '["NotBlank", {"Length": {"max": 100}}]'::json,
    'partial', true, true,
    true, true, true, true,
    NOW(), NOW(), 6
);

-- 7. Add 'subcategory' property (NEW)
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, validation_rules,
    filter_strategy, filter_searchable, filter_orderable,
    api_readable, api_writable, show_in_list, show_in_form,
    created_at, updated_at, property_order
) VALUES (
    uuid_generate_v7(),
    '0199cadd-639e-72a6-9079-52f3b283277e',
    'subcategory', 'Subcategory', 'string',
    100, true, '[{"Length": {"max": 100}}]'::json,
    'exact', true, true,
    true, true, true, true,
    NOW(), NOW(), 7
);

-- 8. Add 'medium' property (NEW)
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, validation_rules,
    filter_strategy, filter_searchable, filter_orderable, indexed,
    api_readable, api_writable, show_in_list, show_in_form,
    created_at, updated_at, property_order
) VALUES (
    uuid_generate_v7(),
    '0199cadd-639e-72a6-9079-52f3b283277e',
    'medium', 'Medium', 'string',
    50, true, '[{"Length": {"max": 50}}]'::json,
    'exact', true, true, true,
    true, true, true, true,
    NOW(), NOW(), 8
);

-- 9-13. Add UTM tracking properties
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, validation_rules, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'utmSource', 'UTM Source', 'string', 100, true, '[{"Length": {"max": 100}}]'::json, true, true, false, true, NOW(), NOW(), 9),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'utmMedium', 'UTM Medium', 'string', 50, true, '[{"Length": {"max": 50}}]'::json, true, true, false, true, NOW(), NOW(), 10),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'utmCampaign', 'UTM Campaign', 'string', 255, true, '[{"Length": {"max": 255}}]'::json, true, true, false, true, NOW(), NOW(), 11),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'utmContent', 'UTM Content', 'string', 255, true, '[{"Length": {"max": 255}}]'::json, true, true, false, true, NOW(), NOW(), 12),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'utmTerm', 'UTM Term', 'string', 100, true, '[{"Length": {"max": 100}}]'::json, true, true, false, true, NOW(), NOW(), 13);

-- 14. Add 'trackingUrl' property
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, validation_rules, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES (
    uuid_generate_v7(),
    '0199cadd-639e-72a6-9079-52f3b283277e',
    'trackingUrl', 'Tracking URL', 'string',
    500, true, '[{"Url": {}}, {"Length": {"max": 500}}]'::json, true, true,
    false, true, NOW(), NOW(), 14
);

-- 15-18. Add cost tracking properties
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    precision, scale, nullable, default_value, validation_rules,
    filter_orderable, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'costPerLead', 'Cost Per Lead', 'decimal', 10, 2, true, NULL, '[{"GreaterThanOrEqual": {"value": 0}}]'::json, true, true, true, true, true, NOW(), NOW(), 15),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'totalCost', 'Total Cost', 'decimal', 10, 2, true, NULL, '[{"GreaterThanOrEqual": {"value": 0}}]'::json, true, true, true, true, true, NOW(), NOW(), 16),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'totalLeads', 'Total Leads', 'integer', NULL, NULL, true, '0'::json, '[{"GreaterThanOrEqual": {"value": 0}}]'::json, true, true, true, true, true, NOW(), NOW(), 17),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'conversionRate', 'Conversion Rate', 'decimal', 5, 2, true, NULL, '[{"Range": {"min": 0, "max": 100}}]'::json, true, true, true, true, true, NOW(), NOW(), 18);

-- 19-20. Add attribution model flags
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, default_value, filter_boolean, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'isFirstTouch', 'First Touch Attribution', 'boolean', false, 'true'::json, true, true, true, false, true, NOW(), NOW(), 19),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'isLastTouch', 'Last Touch Attribution', 'boolean', false, 'false'::json, true, true, true, false, true, NOW(), NOW(), 20);

-- 21-22. Add UI customization
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'color', 'Color', 'string', 7, true, true, true, false, true, NOW(), NOW(), 21),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'icon', 'Icon', 'string', 50, true, true, true, false, true, NOW(), NOW(), 22);

-- 23-25. Add integration properties
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'externalId', 'External ID', 'string', 100, true, true, true, false, true, NOW(), NOW(), 23),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'apiKey', 'API Key', 'string', 255, true, false, true, false, true, NOW(), NOW(), 24),
(uuid_generate_v7(), '0199cadd-639e-72a6-9079-52f3b283277e', 'lastSyncedAt', 'Last Synced', 'datetime_immutable', NULL, true, true, false, false, false, NOW(), NOW(), 25);

-- 26. Add soft delete
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, indexed, index_type, api_readable, api_writable,
    show_in_list, show_in_form, created_at, updated_at, property_order
) VALUES (
    uuid_generate_v7(),
    '0199cadd-639e-72a6-9079-52f3b283277e',
    'deletedAt', 'Deleted At', 'datetime_immutable',
    true, true, 'btree', false, false,
    false, false, NOW(), NOW(), 26
);
```

---

## API Platform Configuration Recommendations

### Update Entity-Level API Configuration

```sql
UPDATE generator_entity SET
    -- Fix labels
    entity_label = 'Lead Source',
    plural_label = 'Lead Sources',

    -- Fix API security
    api_security = 'is_granted(''ROLE_USER'')',
    operation_security = '{
        "Post": "is_granted(''ROLE_CRM_ADMIN'')",
        "Put": "is_granted(''ROLE_CRM_ADMIN'')",
        "Delete": "is_granted(''ROLE_CRM_ADMIN'')"
    }'::json,

    -- Fix default order
    api_default_order = '{"category": "asc", "name": "asc"}'::json,

    -- Update description
    description = 'Lead sources for multi-touch attribution tracking and ROI analysis'

WHERE entity_name = 'LeadSource';
```

### Expected API Endpoints

```
GET    /api/lead_sources              - List all lead sources (paginated)
GET    /api/lead_sources/{id}         - Get single lead source
POST   /api/lead_sources              - Create new lead source (ADMIN only)
PUT    /api/lead_sources/{id}         - Update lead source (ADMIN only)
DELETE /api/lead_sources/{id}         - Delete lead source (ADMIN only)
```

### API Query Examples

```bash
# Get all active lead sources, ordered by category
GET /api/lead_sources?active=true&order[category]=asc&order[name]=asc

# Search by name (partial match)
GET /api/lead_sources?name=google

# Filter by category
GET /api/lead_sources?category=Paid

# Get lead sources with cost tracking
GET /api/lead_sources?costPerLead[gte]=0&order[costPerLead]=asc

# Find lead source by UTM parameters
GET /api/lead_sources?utmSource=facebook&utmMedium=cpc

# Get default lead source
GET /api/lead_sources?default=true

# Full-text search (if implemented)
GET /api/lead_sources?search=linkedin+ads
```

---

## Implementation Checklist

### Phase 1: Database Schema Updates (HIGH PRIORITY)

- [ ] Update existing properties in `generator_property` table:
  - [ ] Fix `name`: add length=100, unique=true, validation
  - [ ] Fix `description`: add filter_strategy='partial', filter_searchable=true
  - [ ] Rename `group` to `category`: add enum, validation, filters
  - [ ] Fix `active`: set nullable=false, default=true, add filters

- [ ] Add new core properties:
  - [ ] `default` (boolean, NOT NULL, default false)
  - [ ] `sourceName` (string 100, NOT NULL)
  - [ ] `subcategory` (string 100, nullable)
  - [ ] `medium` (string 50, nullable)

- [ ] Add UTM tracking properties (5 fields):
  - [ ] `utmSource`, `utmMedium`, `utmCampaign`, `utmContent`, `utmTerm`

- [ ] Add tracking URL:
  - [ ] `trackingUrl` (string 500, nullable, URL validation)

- [ ] Add cost tracking properties (4 fields):
  - [ ] `costPerLead`, `totalCost`, `totalLeads`, `conversionRate`

- [ ] Add attribution flags:
  - [ ] `isFirstTouch`, `isLastTouch`

- [ ] Add UI customization:
  - [ ] `color`, `icon`

- [ ] Add integration fields:
  - [ ] `externalId`, `apiKey`, `lastSyncedAt`

- [ ] Add soft delete:
  - [ ] `deletedAt` (datetime_immutable, nullable)

### Phase 2: Entity Configuration Updates

- [ ] Update `generator_entity` table:
  - [ ] Fix labels: "Lead Source" / "Lead Sources"
  - [ ] Fix API security: allow ROLE_USER for GET, ROLE_CRM_ADMIN for mutations
  - [ ] Fix default order: `{"category": "asc", "name": "asc"}`
  - [ ] Update operation_security JSON

### Phase 3: Enum Creation

- [ ] Create `App\Enum\LeadSourceCategory` enum class:
  ```php
  namespace App\Enum;

  enum LeadSourceCategory: string
  {
      case PAID = 'Paid';
      case ORGANIC = 'Organic';
      case REFERRAL = 'Referral';
      case DIRECT = 'Direct';
      case PARTNER = 'Partner';
      case EVENT = 'Event';
      case OTHER = 'Other';
  }
  ```

### Phase 4: Code Generation

- [ ] Run Genmax generator:
  ```bash
  docker-compose exec app php bin/console genmax:generate LeadSource
  ```

- [ ] Verify generated files:
  - [ ] `/home/user/inf/app/src/Entity/Generated/LeadSourceGenerated.php`
  - [ ] `/home/user/inf/app/src/Entity/LeadSource.php`
  - [ ] `/home/user/inf/app/config/api_platform/LeadSource.yaml`

### Phase 5: Database Migration

- [ ] Create migration:
  ```bash
  docker-compose exec app php bin/console make:migration --no-interaction
  ```

- [ ] Review migration file for:
  - [ ] Table creation with all columns
  - [ ] Indexes (7+ indexes)
  - [ ] Unique constraints
  - [ ] Check constraints (cost >= 0, conversion 0-100)
  - [ ] Foreign keys (organization_id)

- [ ] Add custom trigger for single default per organization:
  ```sql
  CREATE OR REPLACE FUNCTION enforce_single_default_leadsource()
  RETURNS TRIGGER AS $$
  BEGIN
      IF NEW.default = true THEN
          UPDATE lead_source
          SET "default" = false
          WHERE organization_id = NEW.organization_id
            AND id != NEW.id
            AND "default" = true;
      END IF;
      RETURN NEW;
  END;
  $$ LANGUAGE plpgsql;

  CREATE TRIGGER trigger_enforce_single_default_leadsource
  BEFORE INSERT OR UPDATE ON lead_source
  FOR EACH ROW
  WHEN (NEW.default = true)
  EXECUTE FUNCTION enforce_single_default_leadsource();
  ```

- [ ] Run migration:
  ```bash
  docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
  ```

### Phase 6: Testing & Validation

- [ ] Test API endpoints:
  - [ ] GET collection (with filters)
  - [ ] GET single item
  - [ ] POST create (admin only)
  - [ ] PUT update (admin only)
  - [ ] DELETE (admin only)

- [ ] Test validation rules:
  - [ ] Required fields validation
  - [ ] Length constraints
  - [ ] Enum values
  - [ ] Cost >= 0
  - [ ] Conversion rate 0-100
  - [ ] URL validation

- [ ] Test business logic:
  - [ ] Only one default per organization
  - [ ] Soft delete functionality
  - [ ] Organization isolation

- [ ] Performance testing:
  - [ ] Index usage (EXPLAIN ANALYZE)
  - [ ] Query performance with 1000+ records
  - [ ] API response times

### Phase 7: Data Seeding

- [ ] Create fixtures for common lead sources:
  ```php
  // Common lead sources across all organizations
  $sources = [
      ['name' => 'google-ads', 'sourceName' => 'Google Ads', 'category' => 'Paid', 'subcategory' => 'Search Engine', 'medium' => 'cpc'],
      ['name' => 'facebook-ads', 'sourceName' => 'Facebook Ads', 'category' => 'Paid', 'subcategory' => 'Social Media', 'medium' => 'cpc'],
      ['name' => 'linkedin-ads', 'sourceName' => 'LinkedIn Ads', 'category' => 'Paid', 'subcategory' => 'Social Media', 'medium' => 'cpc'],
      ['name' => 'organic-search', 'sourceName' => 'Organic Search', 'category' => 'Organic', 'subcategory' => 'Search Engine', 'medium' => 'organic'],
      ['name' => 'referral', 'sourceName' => 'Referral', 'category' => 'Referral', 'subcategory' => 'Word of Mouth', 'medium' => 'referral', 'default' => true],
      ['name' => 'direct', 'sourceName' => 'Direct Traffic', 'category' => 'Direct', 'subcategory' => 'Type-in', 'medium' => 'direct'],
      ['name' => 'email-campaign', 'sourceName' => 'Email Campaign', 'category' => 'Paid', 'subcategory' => 'Email', 'medium' => 'email'],
  ];
  ```

### Phase 8: Documentation

- [ ] Update API documentation
- [ ] Create user guide for lead source tracking
- [ ] Document UTM parameter setup
- [ ] Document cost tracking methodology
- [ ] Create dashboard/reporting examples

---

## SQL Execution Plan

Execute these SQL statements in order:

```bash
# Connect to database
docker-compose exec -T database psql -U luminai_user -d luminai_db

# Run each UPDATE/INSERT statement from the "Genmax Property Definitions" section above
# Then update the entity configuration
# Then generate code
```

---

## Performance Considerations

### Index Strategy

The recommended schema includes **8 indexes** for optimal query performance:

1. **organization_id** - Multi-tenant filtering (most queries)
2. **category** - Grouping and filtering
3. **active** - Status filtering
4. **default + organization_id** - Finding default per org (partial index)
5. **medium** - UTM tracking queries
6. **utm_source** - UTM tracking queries
7. **created_at** - Sorting and range queries
8. **deleted_at** - Soft delete filtering (partial index)

### Query Optimization

- Use composite index on `(organization_id, name)` for unique constraint
- Use partial index on `(organization_id, default) WHERE default = true`
- Use partial index on `deleted_at WHERE deleted_at IS NULL` for active records
- Consider full-text search index for name/description if needed

### Estimated Storage

- Base table: ~2KB per row
- With 100 lead sources per organization: ~200KB
- With 1000 organizations: ~200MB
- Indexes add ~50% overhead: ~300MB total

### Scalability

- Current design supports:
  - 10,000+ organizations
  - 100+ lead sources per organization
  - 1,000,000+ total lead sources
  - Sub-millisecond queries with proper indexes

---

## Risk Assessment

### HIGH RISK

1. **No Generated Code**: Entity exists only in metadata, not generated yet
2. **Missing Critical Fields**: 19+ missing fields for proper lead attribution
3. **No Uniqueness Constraint**: Can create duplicate lead sources
4. **Nullable Active Field**: Can have NULL status (invalid state)

### MEDIUM RISK

5. **Overly Restrictive Security**: Prevents normal users from viewing lead sources
6. **No API Filters**: Cannot search or filter via API
7. **Poor Default Ordering**: Not user-friendly
8. **Missing Cost Tracking**: Cannot calculate ROI

### LOW RISK

9. **No Enum for Category**: Allows inconsistent data entry
10. **Missing Indexes**: Poor query performance at scale
11. **No Soft Delete**: Cannot recover deleted records
12. **Missing Labels**: Minor UX issue

---

## Compliance & Privacy

### GDPR Considerations

- **UTM Parameters**: First-party data collection, compliant
- **Cost Tracking**: Internal business data, no PII
- **API Keys**: Encrypt in database, never expose in API
- **Tracking URLs**: May contain PII if including email/name in parameters

### Data Retention

- Keep lead source data indefinitely (business intelligence)
- Soft delete instead of hard delete (audit trail)
- Archive after 7 years for compliance

---

## Integration Recommendations

### External Systems

1. **Google Ads**: Track `externalId` as Google Ads Campaign ID, sync daily
2. **Facebook Ads**: Track `externalId` as Facebook Ad Set ID, sync daily
3. **LinkedIn Ads**: Track `externalId` as LinkedIn Campaign Group ID, sync daily
4. **Marketing Automation**: Sync lead source on form submission via webhook
5. **Analytics**: Send lead source to Google Analytics 4 via Measurement Protocol

### Automation

1. **Webhook on Lead Create**: Capture UTM parameters from form submission
2. **Scheduled Job**: Sync cost data from ad platforms daily
3. **Trigger on Deal Update**: Update `totalLeads` and `conversionRate` automatically
4. **Alert on High CPL**: Notify if cost per lead exceeds threshold

---

## Success Metrics

After implementing these recommendations, track:

1. **Data Quality**:
   - 0 duplicate lead sources per organization
   - 100% of leads attributed to a source
   - <5% leads attributed to "Other" or "Unknown"

2. **Performance**:
   - API response time <100ms for filtered queries
   - Database query time <10ms for indexed queries

3. **Business Value**:
   - Accurate ROI calculation per lead source
   - Identification of top 3 performing sources
   - Cost reduction through data-driven budget allocation

---

## Conclusion

The LeadSource entity is **NOT production-ready** in its current state. It requires:

1. **27 properties** (currently has 6) - 21 missing fields
2. **8 database indexes** (currently has 0)
3. **Proper validation** (4 of 6 fields have no validation)
4. **API filters** (0 of 6 fields are filterable)
5. **Security adjustments** (overly restrictive)
6. **Code generation** (entity not generated yet)

**Estimated Implementation Time:**
- Phase 1-2 (Database): 2-3 hours
- Phase 3 (Enum): 30 minutes
- Phase 4 (Generation): 15 minutes
- Phase 5 (Migration): 1 hour
- Phase 6 (Testing): 2-3 hours
- Phase 7 (Seeding): 1 hour
- **Total: 7-9 hours**

**Priority:** HIGH - Critical for CRM functionality and marketing ROI tracking

---

**Report Generated:** 2025-10-19
**Database:** PostgreSQL 18
**Entity Framework:** Symfony 7.3 + Doctrine ORM
**API Framework:** API Platform 4.1
**Code Generator:** Genmax (Luminai)

---

## Appendix A: Genmax Property Reference

Complete field mapping for `generator_property` table:

| Field | Type | Purpose |
|-------|------|---------|
| property_name | varchar(100) | camelCase property name |
| property_label | varchar(100) | Display label |
| property_type | varchar(50) | Doctrine type (string, integer, boolean, etc.) |
| length | integer | String length constraint |
| precision | integer | Decimal precision |
| scale | integer | Decimal scale |
| nullable | boolean | Allow NULL values |
| unique | boolean | Unique constraint |
| default_value | json | Default value |
| relationship_type | varchar(50) | ManyToOne, OneToMany, etc. |
| target_entity | varchar(100) | Target entity class |
| validation_rules | json | Symfony validators as JSON |
| filter_strategy | varchar(50) | partial, exact, start, end, word_start |
| filter_searchable | boolean | Enable text search filter |
| filter_orderable | boolean | Enable sorting filter |
| filter_boolean | boolean | Enable boolean filter |
| filter_date | boolean | Enable date range filter |
| filter_numeric_range | boolean | Enable numeric range filter |
| indexed | boolean | Create database index |
| index_type | varchar(20) | btree, gin, etc. |
| composite_index_with | json | Array of properties for composite index |
| is_enum | boolean | Use enum type |
| enum_class | varchar(255) | Enum class name |
| enum_values | json | Array of enum values |

---

## Appendix B: Validation Rules Reference

Common validation rules for Symfony/API Platform:

```json
// Required field
["NotBlank"]

// String length
["NotBlank", {"Length": {"max": 100}}]

// Email
["Email"]

// URL
["Url"]

// Numeric range
[{"Range": {"min": 0, "max": 100}}]

// Greater than or equal
[{"GreaterThanOrEqual": {"value": 0}}]

// Choice (enum)
[{"Choice": {"choices": ["Paid", "Organic", "Referral"]}}]

// Regex
[{"Regex": {"pattern": "/^#[0-9A-F]{6}$/i", "message": "Must be hex color"}}]

// Multiple constraints
["NotBlank", {"Length": {"min": 3, "max": 100}}, {"Regex": {"pattern": "/^[a-z0-9-]+$/"}}]
```

---

## Appendix C: API Filter Types Reference

| Filter Type | generator_property Field | API Query Example |
|-------------|--------------------------|-------------------|
| Search (partial) | `filter_strategy = 'partial'` | `?name=google` |
| Search (exact) | `filter_strategy = 'exact'` | `?category=Paid` |
| Order | `filter_orderable = true` | `?order[name]=asc` |
| Boolean | `filter_boolean = true` | `?active=true` |
| Date Range | `filter_date = true` | `?createdAt[after]=2024-01-01` |
| Numeric Range | `filter_numeric_range = true` | `?costPerLead[gte]=50` |
| Exists | `filter_exists = true` | `?deletedAt[exists]=false` |

---

**End of Report**
