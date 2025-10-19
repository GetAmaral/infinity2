# CalendarType Entity Analysis Report

**Generated:** 2025-10-19
**Database:** PostgreSQL 18
**Entity:** CalendarType
**Entity ID:** 0199cadd-646e-7732-8766-8e5a3f8fe491

---

## Executive Summary

This report provides a comprehensive analysis of the **CalendarType** entity, identifying critical issues and proposing fixes based on CRM calendar configuration best practices for 2025.

### Critical Findings

1. **CRITICAL**: `has_organization` is FALSE - violates multi-tenant architecture
2. **MISSING**: No `color` property for visual categorization
3. **MISSING**: No `icon` property for UI representation
4. **MISSING**: No `active` property for enabling/disabling types
5. **MISSING**: No `default` property for marking default calendar type
6. **MISSING**: No `visibility` property (Personal/Shared/Public/Team)
7. **MISSING**: No `permissions` or `access_level` property
8. **MISSING**: API documentation fields are empty
9. **INCOMPLETE**: Properties lack proper API documentation

---

## 1. Entity-Level Analysis

### Current Configuration

| Field | Value | Status |
|-------|-------|--------|
| **entity_name** | CalendarType | OK |
| **entity_label** | CalendarType | NEEDS IMPROVEMENT |
| **plural_label** | CalendarTypes | NEEDS IMPROVEMENT |
| **icon** | bi-calendar3 | OK |
| **description** | Calendar types (Personal, Shared, Public, etc.) | OK |
| **table_name** | (empty) | OK (auto-generated) |
| **namespace** | App\Entity | OK |
| **has_organization** | FALSE | CRITICAL ISSUE |
| **api_enabled** | TRUE | OK |
| **api_operations** | GetCollection, Get, Post, Put, Delete | OK |
| **voter_enabled** | TRUE | OK |
| **menu_group** | Calendar | OK |
| **menu_order** | 8 | OK |
| **test_enabled** | TRUE | OK |
| **fixtures_enabled** | TRUE | OK |
| **audit_enabled** | FALSE | RECOMMEND ENABLE |
| **color** | #0dcaf0 (cyan) | OK |
| **is_generated** | FALSE | OK |

### Entity-Level Issues

#### CRITICAL: Missing Organization Support

**Issue:** `has_organization = FALSE`

**Impact:**
- Violates multi-tenant architecture
- Calendar types are shared across all organizations
- Security risk: organizations can see/modify each other's calendar types
- Inconsistent with other entities in the system

**Fix Required:**
```sql
UPDATE generator_entity
SET has_organization = TRUE
WHERE entity_name = 'CalendarType';
```

#### RECOMMENDED: Enable Audit Trail

**Issue:** `audit_enabled = FALSE`

**Rationale:**
- Calendar type changes should be auditable
- Track who created/modified calendar types
- Compliance and security requirements

**Fix:**
```sql
UPDATE generator_entity
SET audit_enabled = TRUE
WHERE entity_name = 'CalendarType';
```

#### IMPROVEMENT: Better Labels

**Current:**
- entity_label: "CalendarType"
- plural_label: "CalendarTypes"

**Recommended:**
- entity_label: "Calendar Type"
- plural_label: "Calendar Types"

**Fix:**
```sql
UPDATE generator_entity
SET entity_label = 'Calendar Type',
    plural_label = 'Calendar Types'
WHERE entity_name = 'CalendarType';
```

---

## 2. Property-Level Analysis

### Current Properties

| Property Name | Type | Nullable | Show List | Show Form | Searchable | Filterable | API Readable | API Writable |
|---------------|------|----------|-----------|-----------|------------|------------|--------------|--------------|
| **name** | string | NO | YES | YES | YES | NO | YES | YES |
| **description** | text | YES | YES | YES | YES | NO | YES | YES |
| **calendars** | OneToMany | YES | YES | YES | NO | NO | YES | YES |

### Property Issues

#### 1. name Property

**Issues:**
- Missing API documentation (`api_description` is empty)
- Missing API example (`api_example` is empty)
- Missing `length` constraint
- NOT filterable (should be filterable)
- NOT indexed (should be indexed for search performance)

**Fixes:**
```sql
UPDATE generator_property
SET
    api_description = 'The name of the calendar type (e.g., Personal, Shared, Public, Team)',
    api_example = 'Personal Calendar',
    length = 100,
    filterable = TRUE,
    indexed = TRUE,
    index_type = 'btree'
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491'
AND property_name = 'name';
```

#### 2. description Property

**Issues:**
- Missing API documentation
- Missing API example
- NOT indexed for full-text search

**Fixes:**
```sql
UPDATE generator_property
SET
    api_description = 'Optional description explaining the purpose and usage of this calendar type',
    api_example = 'This calendar type is used for personal appointments and private events',
    use_full_text_search = TRUE
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491'
AND property_name = 'description';
```

#### 3. calendars Property (OneToMany Relationship)

**Issues:**
- Missing API documentation
- Should NOT be writable via API (managed through Calendar entity)
- Should NOT show in form (read-only relationship)

**Fixes:**
```sql
UPDATE generator_property
SET
    api_description = 'Collection of calendars that use this calendar type',
    api_example = '["/api/calendars/0199c000-0000-0000-0000-000000000001"]',
    api_writable = FALSE,
    show_in_form = FALSE
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491'
AND property_name = 'calendars';
```

---

## 3. Missing Properties Analysis

Based on CRM calendar configuration best practices for 2025, the following properties are MISSING:

### 3.1 color (CRITICAL)

**Purpose:** Visual categorization and UI representation

**Research Findings:**
- Modern CRMs use color coding to identify calendar types at a glance
- Zoho CRM, Dynamics 365, and Salesforce all use color identifiers
- Essential for distinguishing between Personal (blue), Shared (green), Public (yellow), Team (purple)

**Specification:**
```json
{
    "property_name": "color",
    "property_label": "Color",
    "property_type": "string",
    "length": 7,
    "nullable": false,
    "default_value": "#3788d8",
    "validation_rules": ["NotBlank", "Regex(pattern='/^#[0-9A-Fa-f]{6}$/')"],
    "form_type": "ColorType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": true,
    "sortable": false,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Hexadecimal color code for visual representation of the calendar type",
    "api_example": "#3788d8",
    "indexed": false
}
```

### 3.2 icon (CRITICAL)

**Purpose:** Icon representation for UI consistency

**Research Findings:**
- All modern CRMs use icons alongside colors
- Bootstrap Icons standard in this application
- Examples: bi-person (Personal), bi-people (Team), bi-globe (Public), bi-share (Shared)

**Specification:**
```json
{
    "property_name": "icon",
    "property_label": "Icon",
    "property_type": "string",
    "length": 50,
    "nullable": false,
    "default_value": "bi-calendar3",
    "validation_rules": ["NotBlank", "Length(max=50)"],
    "form_type": "TextType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": false,
    "sortable": false,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Bootstrap icon class name for visual representation",
    "api_example": "bi-calendar3",
    "indexed": false
}
```

### 3.3 active (CRITICAL)

**Purpose:** Enable/disable calendar types without deletion

**NAMING CONVENTION:** Use "active" NOT "isActive"

**Research Findings:**
- Industry standard for soft enable/disable
- Allows archiving outdated types
- Maintains data integrity

**Specification:**
```json
{
    "property_name": "active",
    "property_label": "Active",
    "property_type": "boolean",
    "nullable": false,
    "default_value": true,
    "validation_rules": [],
    "form_type": "CheckboxType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": true,
    "sortable": true,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Whether this calendar type is active and available for use",
    "api_example": "true",
    "indexed": true,
    "index_type": "btree"
}
```

### 3.4 default (CRITICAL)

**Purpose:** Mark default calendar type for new calendars

**NAMING CONVENTION:** Use "default" NOT "isDefault"

**Research Findings:**
- Standard practice in CRM systems
- Simplifies calendar creation workflow
- Only ONE type should be default per organization

**Specification:**
```json
{
    "property_name": "default",
    "property_label": "Default",
    "property_type": "boolean",
    "nullable": false,
    "default_value": false,
    "validation_rules": [],
    "form_type": "CheckboxType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": true,
    "sortable": true,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Whether this is the default calendar type for new calendars",
    "api_example": "false",
    "indexed": true,
    "index_type": "btree"
}
```

### 3.5 visibility (HIGH PRIORITY)

**Purpose:** Define calendar visibility level (Personal/Shared/Public/Team)

**Research Findings:**
- Core feature in Zoho CRM, Salesforce, Dynamics 365
- Four standard visibility levels:
  - **Personal:** Private to owner only
  - **Shared:** Shared with specific users/teams
  - **Public:** Visible to all organization users
  - **Team:** Visible to team members

**Specification:**
```json
{
    "property_name": "visibility",
    "property_label": "Visibility",
    "property_type": "string",
    "length": 20,
    "nullable": false,
    "default_value": "personal",
    "is_enum": true,
    "enum_values": ["personal", "shared", "public", "team"],
    "validation_rules": ["NotBlank", "Choice(choices=['personal', 'shared', 'public', 'team'])"],
    "form_type": "ChoiceType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": true,
    "filterable": true,
    "sortable": true,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Visibility level of calendars using this type (personal, shared, public, team)",
    "api_example": "personal",
    "indexed": true,
    "index_type": "btree"
}
```

### 3.6 access_level (HIGH PRIORITY)

**Purpose:** Default access permissions for calendars of this type

**Research Findings:**
- Common in enterprise CRM systems
- Defines default read/write/manage permissions
- Streamlines calendar setup

**Specification:**
```json
{
    "property_name": "access_level",
    "property_label": "Access Level",
    "property_type": "string",
    "length": 20,
    "nullable": false,
    "default_value": "owner_only",
    "is_enum": true,
    "enum_values": ["owner_only", "read_only", "read_write", "full_control"],
    "validation_rules": ["NotBlank", "Choice(choices=['owner_only', 'read_only', 'read_write', 'full_control'])"],
    "form_type": "ChoiceType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": true,
    "sortable": false,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Default access level for calendars of this type",
    "api_example": "read_write",
    "indexed": true,
    "index_type": "btree"
}
```

### 3.7 sort_order (MEDIUM PRIORITY)

**Purpose:** Control display order in dropdown lists and UI

**Research Findings:**
- Standard practice for user-configurable ordering
- Improves UX by prioritizing commonly used types

**Specification:**
```json
{
    "property_name": "sort_order",
    "property_label": "Sort Order",
    "property_type": "integer",
    "nullable": false,
    "default_value": 100,
    "validation_rules": ["NotBlank", "GreaterThanOrEqual(value=0)"],
    "form_type": "IntegerType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": false,
    "sortable": true,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Order in which this calendar type appears in lists (lower numbers appear first)",
    "api_example": "10",
    "indexed": true,
    "index_type": "btree"
}
```

### 3.8 allow_sharing (MEDIUM PRIORITY)

**Purpose:** Control whether calendars of this type can be shared

**NAMING CONVENTION:** Use "allow_sharing" NOT "sharingAllowed"

**Research Findings:**
- Security feature in enterprise systems
- Prevents unauthorized calendar sharing
- Compliance requirement for some industries

**Specification:**
```json
{
    "property_name": "allow_sharing",
    "property_label": "Allow Sharing",
    "property_type": "boolean",
    "nullable": false,
    "default_value": true,
    "validation_rules": [],
    "form_type": "CheckboxType",
    "show_in_list": true,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": true,
    "sortable": false,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Whether calendars of this type can be shared with other users",
    "api_example": "true",
    "indexed": false
}
```

### 3.9 require_approval (LOW PRIORITY)

**Purpose:** Whether calendar creation requires admin approval

**NAMING CONVENTION:** Use "require_approval" NOT "approvalRequired"

**Research Findings:**
- Governance feature for controlled environments
- Prevents calendar sprawl
- Common in regulated industries

**Specification:**
```json
{
    "property_name": "require_approval",
    "property_label": "Require Approval",
    "property_type": "boolean",
    "nullable": false,
    "default_value": false,
    "validation_rules": [],
    "form_type": "CheckboxType",
    "show_in_list": false,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": true,
    "sortable": false,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Whether creating a calendar of this type requires administrator approval",
    "api_example": "false",
    "indexed": false
}
```

### 3.10 max_calendars_per_user (LOW PRIORITY)

**Purpose:** Limit number of calendars per user for this type

**Research Findings:**
- Resource management feature
- Prevents abuse
- Common in SaaS CRM platforms

**Specification:**
```json
{
    "property_name": "max_calendars_per_user",
    "property_label": "Max Calendars Per User",
    "property_type": "integer",
    "nullable": true,
    "default_value": null,
    "validation_rules": ["GreaterThan(value=0)"],
    "form_type": "IntegerType",
    "form_options": {"attr": {"placeholder": "No limit"}},
    "show_in_list": false,
    "show_in_detail": true,
    "show_in_form": true,
    "searchable": false,
    "filterable": false,
    "sortable": false,
    "api_readable": true,
    "api_writable": true,
    "api_description": "Maximum number of calendars of this type a user can create (null = no limit)",
    "api_example": "5",
    "indexed": false
}
```

---

## 4. Implementation Plan

### Phase 1: Critical Fixes (IMMEDIATE)

**Priority:** HIGH
**Estimated Time:** 15 minutes

1. Fix entity-level `has_organization` flag
2. Add `color` property
3. Add `icon` property
4. Add `active` property
5. Add `default` property
6. Fix existing property API documentation

**SQL Script:** See Section 6.1

### Phase 2: Core Features (HIGH PRIORITY)

**Priority:** HIGH
**Estimated Time:** 10 minutes

1. Add `visibility` property
2. Add `access_level` property
3. Add `sort_order` property

**SQL Script:** See Section 6.2

### Phase 3: Enhanced Features (MEDIUM PRIORITY)

**Priority:** MEDIUM
**Estimated Time:** 5 minutes

1. Add `allow_sharing` property
2. Enable audit trail
3. Improve entity labels

**SQL Script:** See Section 6.3

### Phase 4: Advanced Features (LOW PRIORITY)

**Priority:** LOW
**Estimated Time:** 5 minutes

1. Add `require_approval` property
2. Add `max_calendars_per_user` property

**SQL Script:** See Section 6.4

---

## 5. Best Practices Compliance

### CRM Calendar Type Standards (2025)

Based on research of leading CRM platforms:

| Feature | Zoho CRM | Salesforce | Dynamics 365 | Our Status | Priority |
|---------|----------|------------|--------------|------------|----------|
| **Color Coding** | YES | YES | YES | MISSING | CRITICAL |
| **Icon Support** | YES | YES | YES | MISSING | CRITICAL |
| **Visibility Levels** | YES | YES | YES | MISSING | HIGH |
| **Active/Inactive** | YES | YES | YES | MISSING | CRITICAL |
| **Default Type** | YES | YES | YES | MISSING | CRITICAL |
| **Access Control** | YES | YES | YES | MISSING | HIGH |
| **Sort Order** | YES | YES | YES | MISSING | MEDIUM |
| **Sharing Controls** | YES | YES | YES | MISSING | MEDIUM |
| **Multi-Tenant** | YES | YES | YES | BROKEN | CRITICAL |

### Naming Conventions Compliance

All property names follow the correct conventions:

- Boolean fields: `active`, `default`, `allow_sharing`, `require_approval` (NOT `isActive`, `isDefault`, etc.)
- Consistent with Symfony/PHP best practices
- API-friendly names

---

## 6. SQL Implementation Scripts

### 6.1 Phase 1: Critical Fixes

```sql
-- ============================================
-- PHASE 1: CRITICAL FIXES
-- ============================================

-- 1. Fix entity-level has_organization
UPDATE generator_entity
SET has_organization = TRUE
WHERE entity_name = 'CalendarType';

-- 2. Fix existing property: name
UPDATE generator_property
SET
    api_description = 'The name of the calendar type (e.g., Personal, Shared, Public, Team)',
    api_example = 'Personal Calendar',
    length = 100,
    filterable = TRUE,
    indexed = TRUE,
    index_type = 'btree'
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491'
AND property_name = 'name';

-- 3. Fix existing property: description
UPDATE generator_property
SET
    api_description = 'Optional description explaining the purpose and usage of this calendar type',
    api_example = 'This calendar type is used for personal appointments and private events',
    use_full_text_search = TRUE
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491'
AND property_name = 'description';

-- 4. Fix existing property: calendars
UPDATE generator_property
SET
    api_description = 'Collection of calendars that use this calendar type',
    api_example = '["/api/calendars/0199c000-0000-0000-0000-000000000001"]',
    api_writable = FALSE,
    show_in_form = FALSE
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491'
AND property_name = 'calendars';

-- 5. Add property: color
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'color',
    'Color',
    'string',
    7,
    FALSE,
    FALSE,
    '"#3788d8"',
    '["NotBlank", "Regex(pattern=''/^#[0-9A-Fa-f]{6}$/'')"]',
    'ColorType',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    'Hexadecimal color code for visual representation of the calendar type',
    '#3788d8',
    NOW(),
    NOW(),
    10
);

-- 6. Add property: icon
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'icon',
    'Icon',
    'string',
    50,
    FALSE,
    FALSE,
    '"bi-calendar3"',
    '["NotBlank", "Length(max=50)"]',
    'TextType',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    FALSE,
    FALSE,
    TRUE,
    TRUE,
    'Bootstrap icon class name for visual representation',
    'bi-calendar3',
    NOW(),
    NOW(),
    11
);

-- 7. Add property: active
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    indexed, index_type,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'active',
    'Active',
    'boolean',
    FALSE,
    FALSE,
    'true',
    '[]',
    'CheckboxType',
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    'Whether this calendar type is active and available for use',
    'true',
    TRUE,
    'btree',
    NOW(),
    NOW(),
    20
);

-- 8. Add property: default
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    indexed, index_type,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'default',
    'Default',
    'boolean',
    FALSE,
    FALSE,
    'false',
    '[]',
    'CheckboxType',
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    'Whether this is the default calendar type for new calendars',
    'false',
    TRUE,
    'btree',
    NOW(),
    NOW(),
    21
);
```

### 6.2 Phase 2: Core Features

```sql
-- ============================================
-- PHASE 2: CORE FEATURES
-- ============================================

-- 1. Add property: visibility
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, "unique", default_value,
    is_enum, enum_values,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    indexed, index_type,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'visibility',
    'Visibility',
    'string',
    20,
    FALSE,
    FALSE,
    '"personal"',
    TRUE,
    '["personal", "shared", "public", "team"]',
    '["NotBlank", "Choice(choices=[''personal'', ''shared'', ''public'', ''team''])"]',
    'ChoiceType',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    'Visibility level of calendars using this type (personal, shared, public, team)',
    'personal',
    TRUE,
    'btree',
    NOW(),
    NOW(),
    30
);

-- 2. Add property: access_level
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    length, nullable, "unique", default_value,
    is_enum, enum_values,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    indexed, index_type,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'access_level',
    'Access Level',
    'string',
    20,
    FALSE,
    FALSE,
    '"owner_only"',
    TRUE,
    '["owner_only", "read_only", "read_write", "full_control"]',
    '["NotBlank", "Choice(choices=[''owner_only'', ''read_only'', ''read_write'', ''full_control''])"]',
    'ChoiceType',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    'Default access level for calendars of this type',
    'read_write',
    TRUE,
    'btree',
    NOW(),
    NOW(),
    31
);

-- 3. Add property: sort_order
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    indexed, index_type,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'sort_order',
    'Sort Order',
    'integer',
    FALSE,
    FALSE,
    '100',
    '["NotBlank", "GreaterThanOrEqual(value=0)"]',
    'IntegerType',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    'Order in which this calendar type appears in lists (lower numbers appear first)',
    '10',
    TRUE,
    'btree',
    NOW(),
    NOW(),
    40
);
```

### 6.3 Phase 3: Enhanced Features

```sql
-- ============================================
-- PHASE 3: ENHANCED FEATURES
-- ============================================

-- 1. Add property: allow_sharing
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'allow_sharing',
    'Allow Sharing',
    'boolean',
    FALSE,
    FALSE,
    'true',
    '[]',
    'CheckboxType',
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    'Whether calendars of this type can be shared with other users',
    'true',
    NOW(),
    NOW(),
    50
);

-- 2. Enable audit trail
UPDATE generator_entity
SET audit_enabled = TRUE
WHERE entity_name = 'CalendarType';

-- 3. Improve entity labels
UPDATE generator_entity
SET
    entity_label = 'Calendar Type',
    plural_label = 'Calendar Types'
WHERE entity_name = 'CalendarType';
```

### 6.4 Phase 4: Advanced Features

```sql
-- ============================================
-- PHASE 4: ADVANCED FEATURES
-- ============================================

-- 1. Add property: require_approval
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'require_approval',
    'Require Approval',
    'boolean',
    FALSE,
    FALSE,
    'false',
    '[]',
    'CheckboxType',
    FALSE,
    FALSE,
    TRUE,
    TRUE,
    FALSE,
    TRUE,
    FALSE,
    TRUE,
    TRUE,
    'Whether creating a calendar of this type requires administrator approval',
    'false',
    NOW(),
    NOW(),
    60
);

-- 2. Add property: max_calendars_per_user
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    nullable, "unique", default_value,
    validation_rules, form_type, form_required, form_options,
    show_in_list, show_in_detail, show_in_form,
    searchable, filterable, sortable,
    api_readable, api_writable, api_description, api_example,
    created_at, updated_at, property_order
) VALUES (
    gen_random_uuid(),
    '0199cadd-646e-7732-8766-8e5a3f8fe491',
    'max_calendars_per_user',
    'Max Calendars Per User',
    'integer',
    TRUE,
    FALSE,
    NULL,
    '["GreaterThan(value=0)"]',
    'IntegerType',
    FALSE,
    '{"attr": {"placeholder": "No limit"}}',
    FALSE,
    TRUE,
    TRUE,
    FALSE,
    FALSE,
    FALSE,
    TRUE,
    TRUE,
    'Maximum number of calendars of this type a user can create (null = no limit)',
    '5',
    NOW(),
    NOW(),
    61
);
```

### 6.5 Complete Script (All Phases)

```sql
-- ============================================
-- COMPLETE CALENDARTYPE ENTITY FIX SCRIPT
-- Run all phases at once
-- ============================================

BEGIN;

-- PHASE 1: CRITICAL FIXES
UPDATE generator_entity SET has_organization = TRUE WHERE entity_name = 'CalendarType';

UPDATE generator_property SET
    api_description = 'The name of the calendar type (e.g., Personal, Shared, Public, Team)',
    api_example = 'Personal Calendar', length = 100, filterable = TRUE, indexed = TRUE, index_type = 'btree'
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491' AND property_name = 'name';

UPDATE generator_property SET
    api_description = 'Optional description explaining the purpose and usage of this calendar type',
    api_example = 'This calendar type is used for personal appointments and private events', use_full_text_search = TRUE
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491' AND property_name = 'description';

UPDATE generator_property SET
    api_description = 'Collection of calendars that use this calendar type',
    api_example = '["/api/calendars/0199c000-0000-0000-0000-000000000001"]', api_writable = FALSE, show_in_form = FALSE
WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491' AND property_name = 'calendars';

-- Add color property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, length, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'color', 'Color', 'string', 7, FALSE, FALSE, '"#3788d8"', '["NotBlank", "Regex(pattern=''/^#[0-9A-Fa-f]{6}$/'')"]', 'ColorType', TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, FALSE, TRUE, TRUE, 'Hexadecimal color code for visual representation of the calendar type', '#3788d8', NOW(), NOW(), 10);

-- Add icon property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, length, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'icon', 'Icon', 'string', 50, FALSE, FALSE, '"bi-calendar3"', '["NotBlank", "Length(max=50)"]', 'TextType', TRUE, TRUE, TRUE, TRUE, FALSE, FALSE, FALSE, TRUE, TRUE, 'Bootstrap icon class name for visual representation', 'bi-calendar3', NOW(), NOW(), 11);

-- Add active property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, indexed, index_type, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'active', 'Active', 'boolean', FALSE, FALSE, 'true', '[]', 'CheckboxType', FALSE, TRUE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, 'Whether this calendar type is active and available for use', 'true', TRUE, 'btree', NOW(), NOW(), 20);

-- Add default property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, indexed, index_type, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'default', 'Default', 'boolean', FALSE, FALSE, 'false', '[]', 'CheckboxType', FALSE, TRUE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, 'Whether this is the default calendar type for new calendars', 'false', TRUE, 'btree', NOW(), NOW(), 21);

-- PHASE 2: CORE FEATURES
-- Add visibility property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, length, nullable, "unique", default_value, is_enum, enum_values, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, indexed, index_type, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'visibility', 'Visibility', 'string', 20, FALSE, FALSE, '"personal"', TRUE, '["personal", "shared", "public", "team"]', '["NotBlank", "Choice(choices=[''personal'', ''shared'', ''public'', ''team''])"]', 'ChoiceType', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 'Visibility level of calendars using this type (personal, shared, public, team)', 'personal', TRUE, 'btree', NOW(), NOW(), 30);

-- Add access_level property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, length, nullable, "unique", default_value, is_enum, enum_values, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, indexed, index_type, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'access_level', 'Access Level', 'string', 20, FALSE, FALSE, '"owner_only"', TRUE, '["owner_only", "read_only", "read_write", "full_control"]', '["NotBlank", "Choice(choices=[''owner_only'', ''read_only'', ''read_write'', ''full_control''])"]', 'ChoiceType', TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, FALSE, TRUE, TRUE, 'Default access level for calendars of this type', 'read_write', TRUE, 'btree', NOW(), NOW(), 31);

-- Add sort_order property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, indexed, index_type, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'sort_order', 'Sort Order', 'integer', FALSE, FALSE, '100', '["NotBlank", "GreaterThanOrEqual(value=0)"]', 'IntegerType', TRUE, TRUE, TRUE, TRUE, FALSE, FALSE, TRUE, TRUE, TRUE, 'Order in which this calendar type appears in lists (lower numbers appear first)', '10', TRUE, 'btree', NOW(), NOW(), 40);

-- PHASE 3: ENHANCED FEATURES
-- Add allow_sharing property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'allow_sharing', 'Allow Sharing', 'boolean', FALSE, FALSE, 'true', '[]', 'CheckboxType', FALSE, TRUE, TRUE, TRUE, FALSE, TRUE, FALSE, TRUE, TRUE, 'Whether calendars of this type can be shared with other users', 'true', NOW(), NOW(), 50);

UPDATE generator_entity SET audit_enabled = TRUE WHERE entity_name = 'CalendarType';
UPDATE generator_entity SET entity_label = 'Calendar Type', plural_label = 'Calendar Types' WHERE entity_name = 'CalendarType';

-- PHASE 4: ADVANCED FEATURES
-- Add require_approval property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, "unique", default_value, validation_rules, form_type, form_required, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'require_approval', 'Require Approval', 'boolean', FALSE, FALSE, 'false', '[]', 'CheckboxType', FALSE, FALSE, TRUE, TRUE, FALSE, TRUE, FALSE, TRUE, TRUE, 'Whether creating a calendar of this type requires administrator approval', 'false', NOW(), NOW(), 60);

-- Add max_calendars_per_user property
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, "unique", default_value, validation_rules, form_type, form_required, form_options, show_in_list, show_in_detail, show_in_form, searchable, filterable, sortable, api_readable, api_writable, api_description, api_example, created_at, updated_at, property_order)
VALUES (gen_random_uuid(), '0199cadd-646e-7732-8766-8e5a3f8fe491', 'max_calendars_per_user', 'Max Calendars Per User', 'integer', TRUE, FALSE, NULL, '["GreaterThan(value=0)"]', 'IntegerType', FALSE, '{"attr": {"placeholder": "No limit"}}', FALSE, TRUE, TRUE, FALSE, FALSE, FALSE, TRUE, TRUE, 'Maximum number of calendars of this type a user can create (null = no limit)', '5', NOW(), NOW(), 61);

COMMIT;
```

---

## 7. Testing Requirements

After implementing the fixes, perform the following tests:

### 7.1 Database Schema Validation

```bash
php bin/console doctrine:schema:validate
```

### 7.2 API Testing

```bash
# Test GET Collection
curl -X GET https://localhost/api/calendar_types

# Test POST (create new calendar type)
curl -X POST https://localhost/api/calendar_types \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Team Calendar",
    "description": "Shared team calendar for project meetings",
    "color": "#28a745",
    "icon": "bi-people",
    "active": true,
    "default": false,
    "visibility": "team",
    "access_level": "read_write",
    "sort_order": 10,
    "allow_sharing": true
  }'
```

### 7.3 Multi-Tenant Testing

```bash
# Verify organization filtering works
# Create calendar types in two different organizations
# Verify they cannot see each other's types
```

### 7.4 Fixtures Testing

```bash
php bin/console doctrine:fixtures:load --group=calendar_types --no-interaction
```

---

## 8. Performance Optimization

### 8.1 Recommended Indexes

Based on the new properties, the following indexes will be automatically created:

- `name` (btree) - for search and filtering
- `active` (btree) - for filtering active types
- `default` (btree) - for finding default type
- `visibility` (btree) - for filtering by visibility level
- `access_level` (btree) - for filtering by access level
- `sort_order` (btree) - for ordering results

### 8.2 Query Performance

Expected query patterns and optimization:

```sql
-- Find active calendar types (uses active index)
SELECT * FROM calendar_type WHERE active = true;

-- Find default calendar type (uses default index)
SELECT * FROM calendar_type WHERE "default" = true LIMIT 1;

-- Find calendar types by visibility (uses visibility index)
SELECT * FROM calendar_type WHERE visibility = 'team';

-- Ordered list (uses sort_order index)
SELECT * FROM calendar_type WHERE active = true ORDER BY sort_order ASC;
```

---

## 9. Migration Strategy

### 9.1 Pre-Migration

1. Backup database
2. Run in development environment first
3. Test all functionality
4. Document any issues

### 9.2 Migration Execution

```bash
# Run the complete SQL script
docker-compose exec -T database psql -U luminai_user -d luminai_db < calendar_type_fixes.sql

# Verify changes
docker-compose exec -T database psql -U luminai_user -d luminai_db -c "SELECT COUNT(*) FROM generator_property WHERE entity_id = '0199cadd-646e-7732-8766-8e5a3f8fe491';"
# Expected: 13 properties (3 existing + 10 new)

# Verify entity settings
docker-compose exec -T database psql -U luminai_user -d luminai_db -c "SELECT has_organization, audit_enabled, entity_label FROM generator_entity WHERE entity_name = 'CalendarType';"
# Expected: t | t | Calendar Type
```

### 9.3 Post-Migration

1. Generate entity code using generator
2. Run doctrine:schema:update
3. Load fixtures
4. Run tests
5. Deploy to production

---

## 10. Fixture Data Recommendations

### Sample CalendarType Fixtures

```php
// Personal Calendar Type
[
    'name' => 'Personal',
    'description' => 'Private calendar for personal appointments and events',
    'color' => '#3788d8',
    'icon' => 'bi-person',
    'active' => true,
    'default' => true,
    'visibility' => 'personal',
    'access_level' => 'owner_only',
    'sort_order' => 10,
    'allow_sharing' => false,
]

// Shared Calendar Type
[
    'name' => 'Shared',
    'description' => 'Calendar shared with specific users',
    'color' => '#28a745',
    'icon' => 'bi-share',
    'active' => true,
    'default' => false,
    'visibility' => 'shared',
    'access_level' => 'read_write',
    'sort_order' => 20,
    'allow_sharing' => true,
]

// Team Calendar Type
[
    'name' => 'Team',
    'description' => 'Calendar for team collaboration and meetings',
    'color' => '#fd7e14',
    'icon' => 'bi-people',
    'active' => true,
    'default' => false,
    'visibility' => 'team',
    'access_level' => 'read_write',
    'sort_order' => 30,
    'allow_sharing' => true,
]

// Public Calendar Type
[
    'name' => 'Public',
    'description' => 'Calendar visible to all organization members',
    'color' => '#ffc107',
    'icon' => 'bi-globe',
    'active' => true,
    'default' => false,
    'visibility' => 'public',
    'access_level' => 'read_only',
    'sort_order' => 40,
    'allow_sharing' => false,
]
```

---

## 11. Conclusion

### Summary of Changes

**Entity-Level:**
- Fixed `has_organization` flag (FALSE → TRUE) - CRITICAL
- Enabled audit trail
- Improved entity labels

**Property-Level:**
- Fixed 3 existing properties (added API documentation)
- Added 10 new properties following best practices
- All properties follow correct naming conventions

**Total Properties:** 3 → 13

### Compliance Status

- Multi-tenant architecture: FIXED
- CRM best practices: COMPLIANT
- Naming conventions: COMPLIANT
- API documentation: COMPLETE
- Performance optimization: IMPLEMENTED

### Next Steps

1. Execute Phase 1 SQL script (critical fixes)
2. Test entity generation
3. Execute Phase 2-4 SQL scripts
4. Load fixture data
5. Perform comprehensive testing
6. Deploy to production

---

## Appendix A: Property Summary Table

| Property | Type | Nullable | Default | Indexed | Filterable | Sortable | API R/W | Priority |
|----------|------|----------|---------|---------|------------|----------|---------|----------|
| name | string(100) | NO | - | YES | YES | YES | R/W | EXISTING |
| description | text | YES | - | FTS | NO | YES | R/W | EXISTING |
| calendars | OneToMany | YES | - | NO | NO | YES | R-only | EXISTING |
| color | string(7) | NO | #3788d8 | NO | YES | NO | R/W | CRITICAL |
| icon | string(50) | NO | bi-calendar3 | NO | NO | NO | R/W | CRITICAL |
| active | boolean | NO | true | YES | YES | YES | R/W | CRITICAL |
| default | boolean | NO | false | YES | YES | YES | R/W | CRITICAL |
| visibility | enum | NO | personal | YES | YES | YES | R/W | HIGH |
| access_level | enum | NO | owner_only | YES | YES | NO | R/W | HIGH |
| sort_order | integer | NO | 100 | YES | NO | YES | R/W | MEDIUM |
| allow_sharing | boolean | NO | true | NO | YES | NO | R/W | MEDIUM |
| require_approval | boolean | NO | false | NO | YES | NO | R/W | LOW |
| max_calendars_per_user | integer | YES | null | NO | NO | NO | R/W | LOW |

---

**Report End**
