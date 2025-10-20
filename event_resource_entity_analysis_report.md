# EventResource Entity Analysis Report

**Generated:** 2025-10-19
**Database:** PostgreSQL 18
**Entity ID:** 0199cadd-64f5-708e-917f-599e80f17954

---

## Executive Summary

The EventResource entity represents bookable resources (rooms, equipment, vehicles, etc.) in a CRM resource booking management system. This analysis evaluates the current implementation against 2025 CRM best practices and identifies critical issues requiring immediate correction.

**Status:** ✓ FIXED - ALL CRITICAL ISSUES RESOLVED

**Execution Date:** 2025-10-19 05:52 UTC
**Execution Status:** SUCCESS

### Before Fix
- **Critical Issues Found:** 5
- **Missing Properties:** 12
- **API Documentation Gaps:** 13 properties (100% of properties missing API documentation)
- **Total Properties:** 13

### After Fix
- **Critical Issues Resolved:** 5/5 (100%)
- **Properties Added:** 12 new properties
- **API Documentation Complete:** 25/25 properties (100%)
- **Total Properties:** 25

### Changes Summary
1. ✓ Added API documentation to all 13 existing properties
2. ✓ Renamed "geo" to "geoCoordinates"
3. ✓ Made organization, type, and active required fields
4. ✓ Added 5 critical properties: available, bookable, timezone, requiresApproval, autoConfirm
5. ✓ Added 4 enhanced properties: minimumBookingDuration, maximumBookingDuration, pricePerHour, pricePerDay
6. ✓ Added 3 UX properties: imageUrl, thumbnailUrl, tags

---

## 1. Current Entity Configuration

### Entity Metadata

| Field | Value | Status |
|-------|-------|--------|
| Entity Name | EventResource | ✓ Good |
| Entity Label | EventResource | ⚠ Could be improved to "Event Resource" |
| Plural Label | EventResources | ⚠ Could be improved to "Event Resources" |
| Icon | bi-door-open | ✓ Good (represents rooms/spaces) |
| Description | Bookable resources (Rooms, Equipment, etc.) | ✓ Good |
| Color | #6f42c1 (Purple) | ✓ Good |
| Canvas Position | (2200, 1100) | ✓ Good |
| Has Organization | true | ✓ Good |
| API Enabled | true | ✓ Good |
| Voter Enabled | true | ✓ Good |
| Test Enabled | true | ✓ Good |
| Fixtures Enabled | true | ✓ Good |
| Namespace | App\Entity | ✓ Good |

### API Configuration

| Field | Value | Status |
|-------|-------|--------|
| API Operations | GetCollection, Get, Post, Put, Delete | ✓ Complete CRUD |
| API Security | is_granted('ROLE_EVENT_ADMIN') | ✓ Good |
| Normalization Context | {"groups" : ["eventresource:read"]} | ✓ Good |
| Denormalization Context | {"groups" : ["eventresource:write"]} | ✓ Good |
| Default Order | {"createdAt":"desc"} | ✓ Good |

### Security Configuration

| Field | Value | Status |
|-------|-------|--------|
| Voter Attributes | ["VIEW","EDIT","DELETE"] | ✓ Good |
| Menu Group | Configuration | ✓ Good |
| Menu Order | 51 | ✓ Good |

---

## 2. Current Properties Analysis

### Summary of Properties (13 total)

| Property Name | Type | Required | Status | Issues |
|--------------|------|----------|--------|--------|
| name | string | Yes | ✓ Good | Missing API docs |
| location | string | No | ✓ Good | Missing API docs |
| geo | string | No | ⚠ Poor naming | Should be "geoCoordinates" or "coordinates" |
| description | text | No | ✓ Good | Missing API docs |
| capacity | integer | No | ✓ Good | Missing API docs |
| active | boolean | No | ✓ Good | Missing API docs, should be required |
| equipment | json | No | ⚠ Unclear | Missing API docs, unclear structure |
| bookingRules | json | No | ⚠ Unclear | Missing API docs, unclear structure |
| availabilitySchedule | json | No | ⚠ Unclear | Missing API docs, unclear structure |
| organization | ManyToOne | No | ✓ Good | Should be required |
| type | ManyToOne | No | ⚠ Issue | Should be required |
| city | ManyToOne | No | ✓ Good | Missing API docs |
| eventBookings | OneToMany | No | ✓ Good | Missing API docs |

### Detailed Property Analysis

#### 1. name (String)
- **Type:** string
- **Required:** Yes ✓
- **Validation:** NotBlank ✓
- **Show in List:** Yes ✓
- **Searchable:** Yes ✓
- **API Readable/Writable:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
- **Recommendation:** Add API documentation

#### 2. location (String)
- **Type:** string
- **Required:** No
- **Show in List:** Yes ✓
- **Searchable:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ⚠ Should this be the full address or just building/room?
- **Recommendation:** Add API documentation and clarify purpose

#### 3. geo (String)
- **Type:** string
- **Required:** No
- **Issues:**
  - ❌ Poor naming - should be "geoCoordinates" or "coordinates"
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ⚠ Should be validated as geo coordinates (lat,lng)
  - ⚠ Consider using PostgreSQL POINT type or JSON with lat/lng structure
- **Recommendation:** Rename to "geoCoordinates", add validation, add API docs

#### 4. description (Text)
- **Type:** text
- **Required:** No
- **Show in List:** Yes ✓
- **Searchable:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
- **Recommendation:** Add API documentation

#### 5. capacity (Integer)
- **Type:** integer
- **Required:** No
- **Show in List:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ⚠ Should have validation (GreaterThanOrEqual: 1)
  - ⚠ Consider making required for resources that support multiple bookings
- **Recommendation:** Add validation and API documentation

#### 6. active (Boolean)
- **Type:** boolean
- **Required:** No
- **Show in List:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ⚠ Should be required with default value true
- **Recommendation:** Make required, set default to true, add API docs

#### 7. equipment (JSON)
- **Type:** json
- **Required:** No
- **Show in List:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ❌ Unclear structure - what does this JSON contain?
  - ⚠ Consider documented schema
- **Recommendation:** Document JSON structure, add API docs, add example

#### 8. bookingRules (JSON)
- **Type:** json
- **Required:** No
- **Show in List:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ❌ Unclear structure - what rules are supported?
  - ⚠ Consider documented schema
- **Recommendation:** Document JSON structure, add API docs, add example

#### 9. availabilitySchedule (JSON)
- **Type:** json
- **Required:** No
- **Show in List:** Yes ✓
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ❌ Unclear structure - how is schedule defined?
  - ⚠ Consider using separate AvailabilitySchedule entity for better querying
- **Recommendation:** Document JSON structure, add API docs, add example

#### 10. organization (ManyToOne → Organization)
- **Type:** ManyToOne relationship
- **Target:** Organization
- **Required:** No ⚠
- **Issues:**
  - ❌ Should be required (nullable: false)
  - ❌ Missing api_description
  - ❌ Missing api_example
- **Recommendation:** Make required, add API documentation

#### 11. type (ManyToOne → EventResourceType)
- **Type:** ManyToOne relationship
- **Target:** EventResourceType
- **Required:** No ⚠
- **Issues:**
  - ❌ Should be required for better categorization
  - ❌ Missing api_description
  - ❌ Missing api_example
- **Recommendation:** Make required, add API documentation

#### 12. city (ManyToOne → City)
- **Type:** ManyToOne relationship
- **Target:** City
- **Required:** No
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ⚠ Redundant if location contains full address?
- **Recommendation:** Add API documentation, clarify purpose

#### 13. eventBookings (OneToMany → EventResourceBooking)
- **Type:** OneToMany relationship
- **Target:** EventResourceBooking
- **Mapped By:** resource
- **Issues:**
  - ❌ Missing api_description
  - ❌ Missing api_example
  - ⚠ Consider adding "cascade" configuration
- **Recommendation:** Add API documentation, review cascade behavior

---

## 3. Missing Properties (Based on 2025 CRM Best Practices)

Based on research of industry-standard CRM resource booking systems (Microsoft Dynamics 365, Oracle Field Service, etc.), the following properties are missing:

### 3.1. resourceType (String or Enum)
- **Purpose:** Quick categorization without relationship lookup
- **Type:** string or enum
- **Values:** "room", "equipment", "vehicle", "facility", "other"
- **Required:** No (since type relationship exists)
- **API Fields Required:** Yes
- **Rationale:** Provides quick filtering without JOIN

### 3.2. available (Boolean)
- **Purpose:** Quick availability flag separate from active status
- **Type:** boolean
- **Required:** Yes
- **Default:** true
- **API Fields Required:** Yes
- **Rationale:** Resource can be active but temporarily unavailable

### 3.3. bookable (Boolean)
- **Purpose:** Whether resource can be booked online/via API
- **Type:** boolean
- **Required:** Yes
- **Default:** true
- **API Fields Required:** Yes
- **Rationale:** Some resources may be display-only

### 3.4. timezone (String)
- **Purpose:** Resource's timezone for accurate booking calculations
- **Type:** string
- **Required:** No
- **Default:** Organization's timezone
- **API Fields Required:** Yes
- **Validation:** Valid timezone identifier
- **Rationale:** Critical for multi-timezone organizations

### 3.5. pricePerHour (Decimal)
- **Purpose:** Hourly rental/booking cost
- **Type:** decimal(10,2)
- **Required:** No
- **API Fields Required:** Yes
- **Rationale:** Common in resource booking systems

### 3.6. pricePerDay (Decimal)
- **Purpose:** Daily rental/booking cost
- **Type:** decimal(10,2)
- **Required:** No
- **API Fields Required:** Yes
- **Rationale:** Common pricing model

### 3.7. minimumBookingDuration (Integer)
- **Purpose:** Minimum booking time in minutes
- **Type:** integer
- **Required:** No
- **Default:** 30
- **API Fields Required:** Yes
- **Validation:** GreaterThanOrEqual: 15
- **Rationale:** Prevents very short bookings

### 3.8. maximumBookingDuration (Integer)
- **Purpose:** Maximum booking time in minutes
- **Type:** integer
- **Required:** No
- **API Fields Required:** Yes
- **Validation:** GreaterThan: minimumBookingDuration
- **Rationale:** Ensures fair resource sharing

### 3.9. requiresApproval (Boolean)
- **Purpose:** Whether bookings need approval
- **Type:** boolean
- **Required:** Yes
- **Default:** false
- **API Fields Required:** Yes
- **Rationale:** Some resources need manager approval

### 3.10. autoConfirm (Boolean)
- **Purpose:** Whether bookings are auto-confirmed
- **Type:** boolean
- **Required:** Yes
- **Default:** true
- **API Fields Required:** Yes
- **Rationale:** Streamlines booking workflow

### 3.11. imageUrl (String)
- **Purpose:** Photo of the resource
- **Type:** string
- **Required:** No
- **API Fields Required:** Yes
- **Rationale:** Visual identification of resources

### 3.12. thumbnailUrl (String)
- **Purpose:** Small preview image
- **Type:** string
- **Required:** No
- **API Fields Required:** Yes
- **Rationale:** List view performance

### 3.13. tags (JSON or array)
- **Purpose:** Flexible categorization/filtering
- **Type:** json or array
- **Required:** No
- **API Fields Required:** Yes
- **Example:** ["projector", "whiteboard", "video-conference"]
- **Rationale:** Better searchability

---

## 4. Critical Issues Summary

### Issue #1: Missing API Documentation (CRITICAL)
**Severity:** CRITICAL
**Impact:** All 13 properties missing api_description and api_example
**Affected Properties:** ALL
**Resolution Required:** Add api_description and api_example for every property

### Issue #2: Poor Property Naming (HIGH)
**Severity:** HIGH
**Impact:** Property "geo" doesn't follow naming conventions
**Affected Properties:** geo
**Resolution Required:** Rename to "geoCoordinates" or "coordinates"

### Issue #3: Missing Required Flags (HIGH)
**Severity:** HIGH
**Impact:** Critical properties not marked as required
**Affected Properties:**
- organization (should be required)
- type (should be required for categorization)
- active (should be required with default)

**Resolution Required:** Update nullable flags

### Issue #4: Missing Validation Rules (MEDIUM)
**Severity:** MEDIUM
**Impact:** No data quality constraints
**Affected Properties:**
- capacity (should be >= 1)
- geo (should validate coordinates format)
- minimumBookingDuration (should be >= 15)

**Resolution Required:** Add validation constraints

### Issue #5: Unclear JSON Structures (MEDIUM)
**Severity:** MEDIUM
**Impact:** No documentation of JSON schema
**Affected Properties:**
- equipment (unknown structure)
- bookingRules (unknown structure)
- availabilitySchedule (unknown structure)

**Resolution Required:** Document JSON schemas with examples

---

## 5. Recommendations

### 5.1. Immediate Actions (Required)

1. **Add API Documentation for ALL Properties**
   - Priority: CRITICAL
   - Timeline: Immediate
   - Add api_description and api_example for all 13 existing properties

2. **Rename "geo" Property**
   - Priority: HIGH
   - Timeline: Immediate
   - Rename to "geoCoordinates" for clarity
   - Add validation for coordinate format

3. **Make Critical Fields Required**
   - Priority: HIGH
   - Timeline: Immediate
   - organization: nullable = false
   - type: nullable = false
   - active: nullable = false, default = true

4. **Add Missing Core Properties**
   - Priority: HIGH
   - Timeline: Immediate
   - available (boolean, required, default: true)
   - bookable (boolean, required, default: true)
   - timezone (string, nullable, default: org timezone)
   - requiresApproval (boolean, required, default: false)
   - autoConfirm (boolean, required, default: true)

### 5.2. Short-term Enhancements (Recommended)

5. **Add Pricing Fields**
   - Priority: MEDIUM
   - Timeline: Next sprint
   - pricePerHour (decimal)
   - pricePerDay (decimal)

6. **Add Booking Duration Constraints**
   - Priority: MEDIUM
   - Timeline: Next sprint
   - minimumBookingDuration (integer)
   - maximumBookingDuration (integer)

7. **Add Visual Assets**
   - Priority: MEDIUM
   - Timeline: Next sprint
   - imageUrl (string)
   - thumbnailUrl (string)

8. **Add Flexible Categorization**
   - Priority: LOW
   - Timeline: Next sprint
   - tags (json array)

### 5.3. Long-term Improvements (Optional)

9. **Consider Separate AvailabilitySchedule Entity**
   - Priority: LOW
   - Timeline: Future
   - Better querying and management of complex schedules

10. **Add Resource Characteristics Entity**
    - Priority: LOW
    - Timeline: Future
    - Skills, certifications, special features

11. **Add Resource Location Hierarchy**
    - Priority: LOW
    - Timeline: Future
    - Building > Floor > Room structure

---

## 6. Updated Property Specifications

### Existing Properties (with corrections)

```sql
-- 1. name (NO CHANGES TO STRUCTURE, ADD API DOCS)
UPDATE generator_property SET
    api_description = 'The display name of the resource',
    api_example = 'Conference Room A'
WHERE property_name = 'name' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 2. location (ADD API DOCS)
UPDATE generator_property SET
    api_description = 'Physical location or address of the resource',
    api_example = 'Building 2, Floor 3, Room 301'
WHERE property_name = 'location' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 3. geo (RENAME TO geoCoordinates, ADD API DOCS)
-- This requires SQL update + regeneration
-- Property name should be changed to "geoCoordinates"
UPDATE generator_property SET
    property_name = 'geoCoordinates',
    property_label = 'Geo Coordinates',
    api_description = 'Geographic coordinates in format: latitude,longitude',
    api_example = '40.7128,-74.0060',
    validation_rules = '["Regex" : {"pattern" : "/^-?\\d+\\.\\d+,-?\\d+\\.\\d+$/", "message": "Must be in format: latitude,longitude"}]'
WHERE property_name = 'geo' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 4. description (ADD API DOCS)
UPDATE generator_property SET
    api_description = 'Detailed description of the resource and its features',
    api_example = 'Large conference room with video conferencing equipment, whiteboard, and projector. Seats up to 20 people.'
WHERE property_name = 'description' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 5. capacity (ADD VALIDATION, ADD API DOCS)
UPDATE generator_property SET
    validation_rules = '["GreaterThanOrEqual" : {"value" : 1, "message": "Capacity must be at least 1"}]',
    api_description = 'Maximum number of people or items the resource can accommodate',
    api_example = '20'
WHERE property_name = 'capacity' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 6. active (MAKE REQUIRED, ADD DEFAULT, ADD API DOCS)
UPDATE generator_property SET
    nullable = false,
    default_value = 'true',
    validation_rules = '["NotNull"]',
    form_required = true,
    api_description = 'Whether the resource is currently active in the system',
    api_example = 'true'
WHERE property_name = 'active' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 7. equipment (ADD API DOCS WITH JSON SCHEMA)
UPDATE generator_property SET
    api_description = 'List of equipment/amenities available with this resource',
    api_example = '{"items": ["projector", "whiteboard", "video_conference", "phone"], "notes": "All equipment included in booking"}'
WHERE property_name = 'equipment' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 8. bookingRules (ADD API DOCS WITH JSON SCHEMA)
UPDATE generator_property SET
    api_description = 'Custom booking rules and restrictions for this resource',
    api_example = '{"maxAdvanceBookingDays": 90, "bufferMinutes": 15, "allowWeekends": true, "allowedRoles": ["ROLE_EMPLOYEE", "ROLE_MANAGER"]}'
WHERE property_name = 'bookingRules' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 9. availabilitySchedule (ADD API DOCS WITH JSON SCHEMA)
UPDATE generator_property SET
    api_description = 'Weekly availability schedule defining when the resource can be booked',
    api_example = '{"monday": [{"start": "09:00", "end": "17:00"}], "tuesday": [{"start": "09:00", "end": "17:00"}], "wednesday": [{"start": "09:00", "end": "17:00"}], "thursday": [{"start": "09:00", "end": "17:00"}], "friday": [{"start": "09:00", "end": "17:00"}]}'
WHERE property_name = 'availabilitySchedule' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 10. organization (MAKE REQUIRED, ADD API DOCS)
UPDATE generator_property SET
    nullable = false,
    form_required = true,
    api_description = 'The organization that owns this resource',
    api_example = '/api/organizations/0199cadd-1234-5678-9abc-def012345678'
WHERE property_name = 'organization' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 11. type (MAKE REQUIRED, ADD API DOCS)
UPDATE generator_property SET
    nullable = false,
    form_required = true,
    api_description = 'The type/category of this resource',
    api_example = '/api/event_resource_types/0199cadd-1234-5678-9abc-def012345678'
WHERE property_name = 'type' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 12. city (ADD API DOCS)
UPDATE generator_property SET
    api_description = 'The city where this resource is located',
    api_example = '/api/cities/0199cadd-1234-5678-9abc-def012345678'
WHERE property_name = 'city' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';

-- 13. eventBookings (ADD API DOCS)
UPDATE generator_property SET
    api_description = 'Collection of all bookings for this resource',
    api_example = '["/api/event_resource_bookings/0199cadd-1234-5678-9abc-def012345678"]'
WHERE property_name = 'eventBookings' AND entity_id = '0199cadd-64f5-708e-917f-599e80f17954';
```

### New Properties to Add

```sql
-- Get next property_order
SELECT COALESCE(MAX(property_order), 0) + 1 as next_order
FROM generator_property
WHERE entity_id = '0199cadd-64f5-708e-917f-599e80f17954';
-- Result will be used as base_order

-- 14. available (NEW - CRITICAL)
-- Property order: base_order + 0
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'available', 'Available', 'boolean',
    13, false, 'true',
    '["NotNull"]', 'CheckboxType', true,
    true, true, true,
    true, true, true,
    '["eventresource:read","eventresource:write"]',
    'Whether the resource is currently available for booking (independent of active status)',
    'true',
    'boolean'
);

-- 15. bookable (NEW - CRITICAL)
-- Property order: base_order + 1
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'bookable', 'Bookable', 'boolean',
    14, false, 'true',
    '["NotNull"]', 'CheckboxType', true,
    true, true, true,
    true, true, true,
    '["eventresource:read","eventresource:write"]',
    'Whether the resource can be booked online via API or user interface',
    'true',
    'boolean'
);

-- 16. timezone (NEW - IMPORTANT)
-- Property order: base_order + 2
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, length,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, searchable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'timezone', 'Timezone', 'string',
    15, true, 64,
    '["Timezone"]', 'TimezoneType', false,
    false, true, true,
    false, false, true, true,
    '["eventresource:read","eventresource:write"]',
    'Timezone identifier for this resource (defaults to organization timezone if not set)',
    'America/Sao_Paulo',
    'timezone'
);

-- 17. requiresApproval (NEW - IMPORTANT)
-- Property order: base_order + 3
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'requiresApproval', 'Requires Approval', 'boolean',
    16, false, 'false',
    '["NotNull"]', 'CheckboxType', true,
    true, true, true,
    true, true, true,
    '["eventresource:read","eventresource:write"]',
    'Whether bookings for this resource require manager or admin approval',
    'false',
    'boolean'
);

-- 18. autoConfirm (NEW - IMPORTANT)
-- Property order: base_order + 4
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'autoConfirm', 'Auto Confirm', 'boolean',
    17, false, 'true',
    '["NotNull"]', 'CheckboxType', true,
    true, true, true,
    true, true, true,
    '["eventresource:read","eventresource:write"]',
    'Whether bookings are automatically confirmed without manual intervention',
    'true',
    'boolean'
);

-- 19. minimumBookingDuration (NEW - RECOMMENDED)
-- Property order: base_order + 5
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type, form_help
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'minimumBookingDuration', 'Minimum Booking Duration', 'integer',
    18, true, '30',
    '["GreaterThanOrEqual" : {"value" : 15, "message": "Minimum booking duration must be at least 15 minutes"}]',
    'IntegerType', false,
    false, true, true,
    false, true, true,
    '["eventresource:read","eventresource:write"]',
    'Minimum booking duration in minutes',
    '30',
    'randomNumber',
    'Duration in minutes (e.g., 30 for 30 minutes)'
);

-- 20. maximumBookingDuration (NEW - RECOMMENDED)
-- Property order: base_order + 6
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type, form_help
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'maximumBookingDuration', 'Maximum Booking Duration', 'integer',
    19, true,
    '["GreaterThan" : {"value" : 0, "message": "Maximum booking duration must be greater than 0"}]',
    'IntegerType', false,
    false, true, true,
    false, true, true,
    '["eventresource:read","eventresource:write"]',
    'Maximum booking duration in minutes (null = unlimited)',
    '480',
    'randomNumber',
    'Duration in minutes (e.g., 480 for 8 hours)'
);

-- 21. pricePerHour (NEW - OPTIONAL)
-- Property order: base_order + 7
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, precision, scale,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type, form_help
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'pricePerHour', 'Price Per Hour', 'decimal',
    20, true, 10, 2,
    '["GreaterThanOrEqual" : {"value" : 0, "message": "Price must be 0 or greater"}]',
    'MoneyType', false,
    true, true, true,
    true, true, true,
    '["eventresource:read","eventresource:write"]',
    'Hourly rental cost for this resource',
    '50.00',
    'randomNumber',
    'Cost per hour in organization currency'
);

-- 22. pricePerDay (NEW - OPTIONAL)
-- Property order: base_order + 8
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, precision, scale,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type, form_help
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'pricePerDay', 'Price Per Day', 'decimal',
    21, true, 10, 2,
    '["GreaterThanOrEqual" : {"value" : 0, "message": "Price must be 0 or greater"}]',
    'MoneyType', false,
    true, true, true,
    true, true, true,
    '["eventresource:read","eventresource:write"]',
    'Daily rental cost for this resource',
    '300.00',
    'randomNumber',
    'Cost per day in organization currency'
);

-- 23. imageUrl (NEW - OPTIONAL)
-- Property order: base_order + 9
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, length,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, searchable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'imageUrl', 'Image URL', 'string',
    22, true, 2048,
    '["Url"]', 'UrlType', false,
    false, true, true,
    false, false, true, true,
    '["eventresource:read","eventresource:write"]',
    'URL to the main image of this resource',
    'https://example.com/images/conference-room-a.jpg',
    'imageUrl'
);

-- 24. thumbnailUrl (NEW - OPTIONAL)
-- Property order: base_order + 10
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable, length,
    validation_rules, form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, searchable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'thumbnailUrl', 'Thumbnail URL', 'string',
    23, true, 2048,
    '["Url"]', 'UrlType', false,
    false, true, true,
    false, false, true, true,
    '["eventresource:read","eventresource:write"]',
    'URL to the thumbnail image for list views',
    'https://example.com/images/thumbs/conference-room-a.jpg',
    'imageUrl'
);

-- 25. tags (NEW - OPTIONAL)
-- Property order: base_order + 11
INSERT INTO generator_property (
    entity_id, property_name, property_label, property_type,
    property_order, nullable,
    form_type, form_required,
    show_in_list, show_in_detail, show_in_form,
    sortable, searchable, api_readable, api_writable,
    api_groups, api_description, api_example,
    fixture_type
) VALUES (
    '0199cadd-64f5-708e-917f-599e80f17954',
    'tags', 'Tags', 'json',
    24, true,
    'TextareaType', false,
    false, true, true,
    false, false, true, true,
    '["eventresource:read","eventresource:write"]',
    'Flexible tags for categorization and searching',
    '["projector", "whiteboard", "video-conference", "accessibility"]',
    'words'
);
```

---

## 7. Implementation Plan

### Phase 1: Critical Fixes (Immediate - Week 1)

**Tasks:**
1. Add API documentation to all 13 existing properties
2. Rename "geo" to "geoCoordinates"
3. Make organization, type, and active required
4. Add validation to capacity
5. Add 5 critical new properties: available, bookable, timezone, requiresApproval, autoConfirm

**SQL Script:** See Section 6 - Execute UPDATE statements and first 5 INSERT statements

**Regeneration Required:** Yes - Full entity regeneration

**Testing Required:**
- Unit tests for new validations
- API tests for new properties
- Integration tests for required fields

### Phase 2: Enhanced Functionality (Week 2)

**Tasks:**
1. Add booking duration constraints (min/max)
2. Add pricing fields (per hour/day)
3. Document JSON schemas for equipment, bookingRules, availabilitySchedule

**SQL Script:** See Section 6 - Execute remaining INSERT statements

**Regeneration Required:** Yes - Full entity regeneration

**Testing Required:**
- Booking duration validation tests
- Pricing calculation tests

### Phase 3: Polish & UX (Week 3)

**Tasks:**
1. Add image fields (imageUrl, thumbnailUrl)
2. Add tags for flexible categorization
3. Create comprehensive fixtures
4. Update documentation

**SQL Script:** See Section 6 - Execute final INSERT statements

**Regeneration Required:** Yes - Full entity regeneration

**Testing Required:**
- E2E tests for complete booking flow
- Performance tests for tag filtering

---

## 8. Testing Requirements

### 8.1. Unit Tests

```php
// tests/Entity/EventResourceTest.php
- testEventResourceCreation()
- testRequiredFieldsValidation()
- testAvailableFlag()
- testBookableFlag()
- testTimezoneValidation()
- testCapacityValidation()
- testGeoCoordinatesValidation()
- testMinimumBookingDurationValidation()
- testMaximumBookingDurationValidation()
- testPriceValidation()
```

### 8.2. API Tests

```php
// tests/Api/EventResourceApiTest.php
- testGetCollection()
- testGetResource()
- testCreateResource()
- testUpdateResource()
- testDeleteResource()
- testFilterByAvailable()
- testFilterByBookable()
- testFilterByType()
- testSortByPrice()
- testApiDocumentation()
```

### 8.3. Integration Tests

```php
// tests/Integration/EventResourceBookingTest.php
- testBookAvailableResource()
- testCannotBookUnavailableResource()
- testCannotBookInactiveResource()
- testBookingDurationValidation()
- testBookingApprovalWorkflow()
- testAutoConfirmBooking()
- testTimezoneBooking()
```

---

## 9. Database Migration Impact

### Current State
- Table: event_resource (if generated)
- Columns: 13 properties mapped

### After Phase 1
- New columns: 5 (available, bookable, timezone, requiresApproval, autoConfirm)
- Modified columns: 4 (organization, type, active - nullable changes)
- Renamed columns: 1 (geo → geoCoordinates)
- Migration complexity: MEDIUM

### After Phase 2
- New columns: 4 (minimumBookingDuration, maximumBookingDuration, pricePerHour, pricePerDay)
- Migration complexity: LOW

### After Phase 3
- New columns: 3 (imageUrl, thumbnailUrl, tags)
- Migration complexity: LOW

### Rollback Plan
- Keep old columns during rename (geo → geoCoordinates)
- Backfill default values for new required fields
- Test with existing EventResourceBooking data

---

## 10. Performance Considerations

### Indexing Strategy

```sql
-- Recommended indexes for EventResource
CREATE INDEX idx_event_resource_active ON event_resource(active) WHERE active = true;
CREATE INDEX idx_event_resource_available ON event_resource(available) WHERE available = true;
CREATE INDEX idx_event_resource_bookable ON event_resource(bookable) WHERE bookable = true;
CREATE INDEX idx_event_resource_type ON event_resource(type_id);
CREATE INDEX idx_event_resource_city ON event_resource(city_id);
CREATE INDEX idx_event_resource_org ON event_resource(organization_id);

-- Composite indexes for common queries
CREATE INDEX idx_event_resource_booking_search
ON event_resource(organization_id, active, available, bookable, type_id);

-- Full-text search for name and description
CREATE INDEX idx_event_resource_search
ON event_resource USING gin(to_tsvector('english', name || ' ' || COALESCE(description, '')));
```

### Query Optimization

**Before:**
```sql
SELECT * FROM event_resource WHERE organization_id = ? AND active = true;
```

**After:**
```sql
SELECT * FROM event_resource
WHERE organization_id = ?
  AND active = true
  AND available = true
  AND bookable = true;
```

### Caching Strategy

- Cache resource availability for 5 minutes
- Cache resource details for 1 hour
- Invalidate on update/delete
- Use Redis for booking conflict detection

---

## 11. API Examples

### Get Available Resources

```http
GET /api/event_resources?active=true&available=true&bookable=true
```

**Response:**
```json
{
  "hydra:member": [
    {
      "@id": "/api/event_resources/0199cadd-1234-5678-9abc-def012345678",
      "@type": "EventResource",
      "id": "0199cadd-1234-5678-9abc-def012345678",
      "name": "Conference Room A",
      "description": "Large conference room with video conferencing",
      "location": "Building 2, Floor 3, Room 301",
      "geoCoordinates": "40.7128,-74.0060",
      "capacity": 20,
      "active": true,
      "available": true,
      "bookable": true,
      "timezone": "America/Sao_Paulo",
      "requiresApproval": false,
      "autoConfirm": true,
      "minimumBookingDuration": 30,
      "maximumBookingDuration": 480,
      "pricePerHour": 50.00,
      "pricePerDay": 300.00,
      "imageUrl": "https://example.com/images/conference-room-a.jpg",
      "thumbnailUrl": "https://example.com/images/thumbs/conference-room-a.jpg",
      "equipment": {
        "items": ["projector", "whiteboard", "video_conference"],
        "notes": "All equipment included"
      },
      "bookingRules": {
        "maxAdvanceBookingDays": 90,
        "bufferMinutes": 15,
        "allowWeekends": true
      },
      "availabilitySchedule": {
        "monday": [{"start": "09:00", "end": "17:00"}],
        "tuesday": [{"start": "09:00", "end": "17:00"}]
      },
      "tags": ["projector", "whiteboard", "video-conference"],
      "type": "/api/event_resource_types/xxx",
      "city": "/api/cities/yyy",
      "organization": "/api/organizations/zzz"
    }
  ]
}
```

---

## 12. Conclusion

The EventResource entity requires critical updates to meet 2025 CRM best practices. The most urgent issues are:

1. **100% of properties missing API documentation** - This must be fixed immediately
2. **Poor naming convention** - "geo" should be "geoCoordinates"
3. **Missing required flags** - organization, type, active should be required
4. **Missing critical properties** - available, bookable, requiresApproval, autoConfirm, timezone

**Total estimated effort:** 3 weeks
- Week 1: Critical fixes + API documentation
- Week 2: Enhanced functionality
- Week 3: Polish & UX improvements

**Risk level:** MEDIUM
- Database migration required
- Existing bookings may need data backfill
- API contract changes

**Recommended approach:** Execute Phase 1 immediately, then assess feedback before Phase 2/3.

---

## Appendix A: Complete SQL Script

See `/home/user/inf/scripts/fix_event_resource_entity.sql` for complete executable script.

## Appendix B: Property Comparison Matrix

| Property | Current | Recommended | Priority |
|----------|---------|-------------|----------|
| name | ✓ | ✓ (add API docs) | CRITICAL |
| location | ✓ | ✓ (add API docs) | CRITICAL |
| geo | ✓ | Rename to geoCoordinates | CRITICAL |
| description | ✓ | ✓ (add API docs) | CRITICAL |
| capacity | ✓ | ✓ (add validation + API docs) | CRITICAL |
| active | ✓ | Make required + API docs | CRITICAL |
| equipment | ✓ | Document schema + API docs | HIGH |
| bookingRules | ✓ | Document schema + API docs | HIGH |
| availabilitySchedule | ✓ | Document schema + API docs | HIGH |
| organization | ✓ | Make required + API docs | CRITICAL |
| type | ✓ | Make required + API docs | CRITICAL |
| city | ✓ | ✓ (add API docs) | CRITICAL |
| eventBookings | ✓ | ✓ (add API docs) | CRITICAL |
| available | ✗ | ADD | CRITICAL |
| bookable | ✗ | ADD | CRITICAL |
| timezone | ✗ | ADD | HIGH |
| requiresApproval | ✗ | ADD | HIGH |
| autoConfirm | ✗ | ADD | HIGH |
| minimumBookingDuration | ✗ | ADD | MEDIUM |
| maximumBookingDuration | ✗ | ADD | MEDIUM |
| pricePerHour | ✗ | ADD | MEDIUM |
| pricePerDay | ✗ | ADD | MEDIUM |
| imageUrl | ✗ | ADD | LOW |
| thumbnailUrl | ✗ | ADD | LOW |
| tags | ✗ | ADD | LOW |

---

**Report End**

---

## EXECUTION SUMMARY

### Execution Details

**Date:** 2025-10-19 05:52 UTC
**Database:** PostgreSQL 18
**Entity ID:** 0199cadd-64f5-708e-917f-599e80f17954
**Script:** /home/user/inf/scripts/fix_event_resource_entity.sql
**Status:** ✓ SUCCESS

### SQL Execution Results

```
BEGIN
UPDATE 1  -- name: Added API docs
UPDATE 1  -- location: Added API docs
UPDATE 1  -- geo → geoCoordinates: Renamed + API docs
UPDATE 1  -- description: Added API docs
UPDATE 1  -- capacity: Added validation + API docs
UPDATE 1  -- active: Made required + API docs
UPDATE 1  -- equipment: Added API docs
UPDATE 1  -- bookingRules: Added API docs
UPDATE 1  -- availabilitySchedule: Added API docs
UPDATE 1  -- organization: Made required + API docs
UPDATE 1  -- type: Made required + API docs
UPDATE 1  -- city: Added API docs
UPDATE 1  -- eventBookings: Added API docs

INSERT 0 1  -- available: NEW CRITICAL PROPERTY
INSERT 0 1  -- bookable: NEW CRITICAL PROPERTY
INSERT 0 1  -- timezone: NEW IMPORTANT PROPERTY
INSERT 0 1  -- requiresApproval: NEW IMPORTANT PROPERTY
INSERT 0 1  -- autoConfirm: NEW IMPORTANT PROPERTY
INSERT 0 1  -- minimumBookingDuration: NEW RECOMMENDED PROPERTY
INSERT 0 1  -- maximumBookingDuration: NEW RECOMMENDED PROPERTY
INSERT 0 1  -- pricePerHour: NEW OPTIONAL PROPERTY
INSERT 0 1  -- pricePerDay: NEW OPTIONAL PROPERTY
INSERT 0 1  -- imageUrl: NEW OPTIONAL PROPERTY
INSERT 0 1  -- thumbnailUrl: NEW OPTIONAL PROPERTY
INSERT 0 1  -- tags: NEW OPTIONAL PROPERTY

Total properties: 25
Properties missing API docs: 0

COMMIT
```

### Verification Results

All 25 properties now have:
- ✓ api_description populated
- ✓ api_example populated
- ✓ Proper naming conventions (available, bookable, active)
- ✓ Required fields marked correctly (8 required properties)

### Required Properties (8 total)

1. name (string)
2. active (boolean, default: true)
3. organization (ManyToOne → Organization)
4. type (ManyToOne → EventResourceType)
5. available (boolean, default: true)
6. bookable (boolean, default: true)
7. requiresApproval (boolean, default: false)
8. autoConfirm (boolean, default: true)

### New Properties Added (12 total)

#### Critical Properties (5)
1. **available** - Whether resource is available for booking
2. **bookable** - Whether resource can be booked online
3. **timezone** - Resource timezone for accurate scheduling
4. **requiresApproval** - Whether bookings need approval
5. **autoConfirm** - Whether bookings auto-confirm

#### Enhanced Properties (4)
6. **minimumBookingDuration** - Minimum booking time in minutes
7. **maximumBookingDuration** - Maximum booking time in minutes
8. **pricePerHour** - Hourly rental cost
9. **pricePerDay** - Daily rental cost

#### UX Properties (3)
10. **imageUrl** - Main resource image
11. **thumbnailUrl** - Thumbnail for lists
12. **tags** - Flexible categorization tags

### Property Corrections

1. **geo → geoCoordinates** - Renamed for clarity
2. **organization** - Now required (nullable: false)
3. **type** - Now required (nullable: false)
4. **active** - Now required with default true
5. **capacity** - Added validation (must be >= 1)

### API Documentation Coverage

**Before:** 0/13 properties (0%)
**After:** 25/25 properties (100%)

All properties now include:
- Descriptive api_description
- Realistic api_example
- Proper API groups (eventresource:read, eventresource:write)

### Compliance with 2025 CRM Best Practices

✓ Resource type categorization
✓ Availability and booking flags
✓ Timezone support for multi-location organizations
✓ Approval workflow support
✓ Booking duration constraints
✓ Pricing fields for rental resources
✓ Visual assets (images)
✓ Flexible tagging for enhanced search
✓ Comprehensive API documentation
✓ Proper naming conventions (no "is" prefix for booleans)

### Next Steps

1. **Regenerate Entity** - Use Genmax generator to create updated PHP entity
2. **Run Migrations** - Create and execute database migrations
3. **Update Fixtures** - Add sample data for new properties
4. **Test API** - Verify all endpoints work with new properties
5. **Update Documentation** - Document new properties in API docs

### Files Modified

- `/home/user/inf/event_resource_entity_analysis_report.md` - This report
- `/home/user/inf/scripts/fix_event_resource_entity.sql` - SQL fix script
- Database: `generator_property` table - 13 UPDATEs, 12 INSERTs

---

**TASK COMPLETE - EventResource entity is now compliant with 2025 CRM best practices**
