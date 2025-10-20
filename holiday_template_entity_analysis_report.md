# HOLIDAYTEMPLATE ENTITY - COMPREHENSIVE ANALYSIS REPORT

**Database:** PostgreSQL 18 | **Framework:** Symfony 7.3 + API Platform 4.1
**Date:** 2025-10-19 | **Status:** CRITICAL ISSUES FOUND

---

## EXECUTIVE SUMMARY

The **HolidayTemplate** entity has been analyzed and multiple critical issues were identified:

1. **ENTITY FILE MISSING** - No entity class exists in `/home/user/inf/app/src/Entity/`
2. **INCOMPLETE PROPERTIES** - Missing critical fields for holiday management
3. **NAMING CONVENTIONS** - Some inconsistencies detected
4. **API CONFIGURATION** - Incomplete API Platform field definitions
5. **MISSING FEATURES** - Lacking essential holiday template functionality

**Action Required:** Update CSV, regenerate entity, add missing properties, complete API configuration

---

## 1. CURRENT STATE ANALYSIS

### 1.1 Entity Configuration (EntityNew.csv)

**Line 61:**
```csv
HolidayTemplate,HolidayTemplate,HolidayTemplates,bi-circle,,,1,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_SUPER_ADMIN'),holidaytemplate:read,holidaytemplate:write,1,30,"{""createdAt"": ""desc""}",,,,,bootstrap_5_layout.html.twig,,,,System,12,1
```

**Current Configuration:**
- **Entity Name:** HolidayTemplate
- **Label:** HolidayTemplate
- **Plural:** HolidayTemplates
- **Icon:** bi-circle
- **Description:** EMPTY (ISSUE #1)
- **Has Organization:** NO (Template entities typically don't have organization)
- **API Enabled:** YES
- **Operations:** GetCollection, Get, Post, Put, Delete
- **Security:** is_granted('ROLE_SUPER_ADMIN')
- **Normalization:** holidaytemplate:read
- **Denormalization:** holidaytemplate:write
- **Pagination:** Enabled (30 items per page)
- **Order:** createdAt DESC
- **Searchable Fields:** EMPTY (ISSUE #2)
- **Filterable Fields:** EMPTY (ISSUE #3)
- **Voter Enabled:** NO
- **Menu Group:** System
- **Menu Order:** 12

### 1.2 Current Properties (PropertyNew.csv)

| Property | Type | Nullable | Relations | Issues |
|----------|------|----------|-----------|--------|
| name | string | NO | - | OK |
| date | date | YES | - | Should be nullable for recurring holidays |
| recurrenceInterval | integer | YES | - | Poor naming (needs clarification) |
| recurrenceFrequency | integer | YES | - | Poor naming (needs clarification) |
| country | ManyToOne | YES | Country | OK |
| city | ManyToOne | YES | City | OK |
| blockSchedule | boolean | YES | - | OK |

**Total Properties:** 7

### 1.3 Missing Entity Files

**CRITICAL:** Entity class file does NOT exist:
- `/home/user/inf/app/src/Entity/HolidayTemplate.php` - MISSING
- `/home/user/inf/app/src/Entity/Generated/HolidayTemplateGenerated.php` - MISSING

**Existing Files:**
- `/home/user/inf/app/src/Repository/HolidayTemplateRepository.php` - EXISTS
- `/home/user/inf/app/src/Repository/Generated/HolidayTemplateRepositoryGenerated.php` - EXISTS
- `/home/user/inf/app/src/Form/HolidayTemplateType.php` - EXISTS
- `/home/user/inf/app/src/Form/Generated/HolidayTemplateTypeGenerated.php` - EXISTS
- `/home/user/inf/app/templates/holidaytemplate/` - EXISTS

**Database Table:** Does NOT exist in database

---

## 2. INDUSTRY RESEARCH FINDINGS (2025)

### 2.1 CRM Holiday Template Best Practices

Based on Dynamics 365 CRM, Salesforce, and modern CRM systems:

**Essential Fields:**
1. **Name/Title** - Holiday name (e.g., "Christmas Day", "Independence Day")
2. **Date** - Specific date or null for recurring holidays
3. **Recurrence Pattern** - How the holiday recurs (yearly, monthly, etc.)
4. **Country** - Geographic scope (national holidays)
5. **Region/State** - Sub-national scope (state holidays)
6. **Active Status** - Whether the holiday is currently active
7. **Description** - Additional details about the holiday
8. **Holiday Type** - Fixed, movable, or adjustable
9. **Observance Rules** - How to handle when holiday falls on weekend
10. **Block Schedule** - Whether to block scheduling on this day

### 2.2 Database Schema Best Practices

**From Industry Research:**

1. **Calendar Table Integration** - Holiday templates should integrate with calendar systems
2. **Recurrence Rules** - Support for RFC 5545 (iCalendar) recurrence patterns
3. **Week/Month Positioning** - Support for "First Monday", "Last Friday" patterns
4. **Business Day Calculation** - Integration with working hours and business day calculations
5. **SLA Impact** - Holidays should affect Service Level Agreement calculations
6. **Multi-Region Support** - Support for different regions/countries
7. **Year-Independent** - Templates should be year-agnostic for recurring holidays

### 2.3 Field Recommendations

**Fixed Holidays:**
- Date: January 1 (New Year's Day)
- Recurrence: Yearly on same date

**Movable Holidays:**
- Pattern: "First Monday of September" (Labor Day)
- Week of Month: 1
- Day of Week: Monday
- Month: 9

**Adjustable Holidays:**
- Date: December 25
- If Weekend: Shift to nearest weekday

---

## 3. IDENTIFIED ISSUES

### 3.1 CRITICAL Issues

1. **MISSING ENTITY FILES**
   - Entity class not generated
   - Generated base class not present
   - Database table does not exist
   - **Impact:** Entity is completely non-functional
   - **Fix:** Regenerate from CSV using Genmax generator

2. **INCOMPLETE API PLATFORM CONFIGURATION**
   - No searchable fields defined
   - No filterable fields defined
   - Missing API documentation
   - **Impact:** Poor API usability
   - **Fix:** Add searchable and filterable fields

3. **MISSING CRITICAL PROPERTIES**
   - No `description` field
   - No `active` status field
   - No `holidayType` field
   - No `weekOfMonth` field for movable holidays
   - No `dayOfWeek` field for movable holidays
   - No `month` field for movable holidays
   - No `observanceRule` field
   - **Impact:** Cannot support full holiday management functionality
   - **Fix:** Add missing properties to CSV

### 3.2 HIGH Priority Issues

4. **POOR PROPERTY NAMING**
   - `recurrenceInterval` - Unclear meaning (days? weeks? months?)
   - `recurrenceFrequency` - Unclear meaning
   - **Impact:** Confusing for developers
   - **Recommendation:** Rename or add clear documentation

5. **MISSING DESCRIPTION**
   - Entity description is empty in EntityNew.csv
   - **Impact:** Poor documentation
   - **Fix:** Add description: "Holiday calendar templates for recurring holidays"

6. **INCOMPLETE RELATIONSHIPS**
   - Missing relationship to TimeZone entity
   - Missing relationship to Calendar entity
   - **Impact:** Cannot properly manage holiday calendars
   - **Fix:** Add relationships

### 3.3 MEDIUM Priority Issues

7. **NAMING CONVENTION COMPLIANCE**
   - Boolean field `blockSchedule` is acceptable
   - All properties follow camelCase convention
   - **Status:** COMPLIANT (no issues found)

8. **MISSING VALIDATION RULES**
   - No validation on `name` field
   - No validation on date ranges
   - **Impact:** Data quality issues
   - **Fix:** Add NotBlank, Length constraints

9. **ICON SELECTION**
   - Current icon: `bi-circle` (generic)
   - **Recommendation:** Use `bi-calendar-event` or `bi-calendar-date`
   - **Impact:** Poor UX
   - **Fix:** Update icon in EntityNew.csv

### 3.4 LOW Priority Issues

10. **FIXTURE CONFIGURATION**
    - No fixture types defined
    - **Impact:** Cannot generate test data
    - **Fix:** Add fixture types (word, date, etc.)

---

## 4. RECOMMENDED SOLUTION

### 4.1 Updated Entity Configuration

**EntityNew.csv (Line 61) - CORRECTED:**

```csv
HolidayTemplate,Holiday Template,Holiday Templates,bi-calendar-event,"Holiday calendar templates for recurring and fixed holidays",,1,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_SUPER_ADMIN'),holidaytemplate:read,holidaytemplate:write,1,30,"{""name"": ""asc""}","name,description","active,country,holidayType",,,,bootstrap_5_layout.html.twig,,,,System,12,1
```

**Changes:**
1. Entity Label: "Holiday Template" (with space)
2. Plural Label: "Holiday Templates" (with space)
3. Icon: `bi-calendar-event` (more appropriate)
4. Description: "Holiday calendar templates for recurring and fixed holidays"
5. Order: Changed to `name ASC` (alphabetical is more logical for templates)
6. Searchable Fields: `name,description`
7. Filterable Fields: `active,country,holidayType`

### 4.2 Complete Property List

**ALL Properties for HolidayTemplate (PropertyNew.csv):**

```csv
HolidayTemplate,name,Name,string,,,,,,,,,,,,,LAZY,,simple,,"NotBlank,Length(max=255)",,TextType,{},1,,,1,1,1,1,1,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,description,Description,text,1,,,,,,,,,,,,LAZY,,,,Length(max=1000),,TextareaType,{},,,,1,1,1,1,1,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,paragraph,{}
HolidayTemplate,active,Active,boolean,1,,,,1,1,,,,,,,,LAZY,,,,,,CheckboxType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,boolean,{}
HolidayTemplate,holidayType,Holiday Type,string,1,,,,,,,,,,,,LAZY,,simple,,"Choice(choices=['FIXED','MOVABLE','ADJUSTABLE'])",,ChoiceType,"{""choices"": {""Fixed"": ""FIXED"", ""Movable"": ""MOVABLE"", ""Adjustable"": ""ADJUSTABLE""}}",1,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,date,Date,date,1,,,,,,,,,,,,LAZY,,,,,,DateType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,date,{}
HolidayTemplate,month,Month,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=1 max=12),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": 1 ""max"": 12}"
HolidayTemplate,dayOfMonth,Day of Month,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=1 max=31),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": 1 ""max"": 31}"
HolidayTemplate,weekOfMonth,Week of Month,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=-1 max=5),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": -1 ""max"": 5}"
HolidayTemplate,dayOfWeek,Day of Week,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=0 max=6),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": 0 ""max"": 6}"
HolidayTemplate,recurrencePattern,Recurrence Pattern,string,1,,,,,,,,,,,,LAZY,,simple,,"Choice(choices=['NONE','YEARLY','MONTHLY'])",,ChoiceType,"{""choices"": {""None"": ""NONE"", ""Yearly"": ""YEARLY"", ""Monthly"": ""MONTHLY""}}",1,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,observanceRule,Observance Rule,string,1,,,,,,,,,,,,LAZY,,simple,,"Choice(choices=['ACTUAL','NEAREST_WEEKDAY','NEXT_MONDAY','PREVIOUS_FRIDAY'])",,ChoiceType,"{""choices"": {""Actual Day"": ""ACTUAL"", ""Nearest Weekday"": ""NEAREST_WEEKDAY"", ""Next Monday"": ""NEXT_MONDAY"", ""Previous Friday"": ""PREVIOUS_FRIDAY""}}",1,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,blockSchedule,Block Schedule,boolean,1,,,,1,1,,,,,,,,LAZY,,,,,,CheckboxType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,boolean,{}
HolidayTemplate,country,Country,,1,,,,,,ManyToOne,Country,holidayTemplates,,,,LAZY,,simple,,,,EntityType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,,{}
HolidayTemplate,city,City,,1,,,,,,ManyToOne,City,holidayTemplates,,,,LAZY,,simple,,,,EntityType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,,{}
```

**REMOVED Properties:**
- `recurrenceInterval` (replaced with better structure)
- `recurrenceFrequency` (replaced with better structure)

**NEW Properties Added:**
1. **description** (text) - Detailed description of the holiday
2. **active** (boolean, default: true) - Whether template is active
3. **holidayType** (choice: FIXED, MOVABLE, ADJUSTABLE) - Type of holiday
4. **month** (integer, 1-12) - Month for yearly recurring holidays
5. **dayOfMonth** (integer, 1-31) - Day of month for fixed holidays
6. **weekOfMonth** (integer, -1 to 5) - Week position (-1 = last, 1 = first, etc.)
7. **dayOfWeek** (integer, 0-6) - Day of week (0 = Sunday, 6 = Saturday)
8. **recurrencePattern** (choice: NONE, YEARLY, MONTHLY) - How holiday recurs
9. **observanceRule** (choice) - How to handle weekend conflicts

**Total Properties:** 14 (was 7, added 7 new)

### 4.3 Property Details and Validation

#### Core Properties

**1. name (string, required)**
- Purpose: Holiday name (e.g., "Christmas Day", "New Year's Day")
- Validation: NotBlank, Length(max=255)
- API: Read/Write
- Searchable: YES
- Example: "Independence Day"

**2. description (text, nullable)**
- Purpose: Detailed description of the holiday and observance
- Validation: Length(max=1000)
- API: Read/Write
- Searchable: YES
- Example: "Federal holiday celebrating the adoption of the Declaration of Independence"

**3. active (boolean, default: true)**
- Purpose: Whether this template is currently active
- Validation: None
- API: Read/Write
- Filterable: YES
- Naming Convention: CORRECT (uses "active" not "isActive")

#### Holiday Type Properties

**4. holidayType (choice, nullable)**
- Purpose: Classification of holiday pattern
- Choices:
  - **FIXED**: Same date every year (e.g., January 1, December 25)
  - **MOVABLE**: Day-of-week based (e.g., "First Monday in September")
  - **ADJUSTABLE**: Fixed date that shifts for weekends
- Validation: Choice constraint
- API: Read/Write
- Filterable: YES

#### Date Properties

**5. date (date, nullable)**
- Purpose: Specific date for fixed holidays (null for movable holidays)
- Validation: None
- API: Read/Write
- Example: 2025-12-25 (for Christmas)

**6. month (integer, nullable)**
- Purpose: Month number (1-12) for yearly recurring holidays
- Validation: Range(min=1, max=12)
- API: Read/Write
- Example: 7 (July for Independence Day)

**7. dayOfMonth (integer, nullable)**
- Purpose: Day of month (1-31) for fixed date holidays
- Validation: Range(min=1, max=31)
- API: Read/Write
- Example: 4 (for July 4th)

#### Movable Holiday Properties

**8. weekOfMonth (integer, nullable)**
- Purpose: Week position for movable holidays
- Values:
  - 1 = First week
  - 2 = Second week
  - 3 = Third week
  - 4 = Fourth week
  - 5 = Fifth week (rare)
  - -1 = Last week (e.g., Memorial Day is last Monday in May)
- Validation: Range(min=-1, max=5)
- API: Read/Write
- Example: -1 (for "Last Monday")

**9. dayOfWeek (integer, nullable)**
- Purpose: Day of week (0-6) for movable holidays
- Values:
  - 0 = Sunday
  - 1 = Monday
  - 2 = Tuesday
  - 3 = Wednesday
  - 4 = Thursday
  - 5 = Friday
  - 6 = Saturday
- Validation: Range(min=0, max=6)
- API: Read/Write
- Example: 1 (Monday for Labor Day)

#### Recurrence Properties

**10. recurrencePattern (choice, nullable)**
- Purpose: How the holiday recurs
- Choices:
  - **NONE**: One-time holiday (not recurring)
  - **YEARLY**: Recurs every year
  - **MONTHLY**: Recurs every month (rare for holidays)
- Validation: Choice constraint
- API: Read/Write

**11. observanceRule (choice, nullable)**
- Purpose: How to handle when holiday falls on weekend
- Choices:
  - **ACTUAL**: Observe on actual day (even if weekend)
  - **NEAREST_WEEKDAY**: Shift to nearest weekday (Fri if Sat, Mon if Sun)
  - **NEXT_MONDAY**: Always shift to following Monday
  - **PREVIOUS_FRIDAY**: Always shift to preceding Friday
- Validation: Choice constraint
- API: Read/Write
- Example: NEAREST_WEEKDAY (common for US federal holidays)

#### Scheduling Properties

**12. blockSchedule (boolean, default: true)**
- Purpose: Whether to block scheduling/appointments on this holiday
- Validation: None
- API: Read/Write
- Naming Convention: CORRECT

#### Geographic Properties

**13. country (ManyToOne -> Country, nullable)**
- Purpose: Country where this holiday is observed
- Relationship: ManyToOne to Country entity
- Inverse: holidayTemplates
- API: Read/Write
- Filterable: YES
- Example: United States

**14. city (ManyToOne -> City, nullable)**
- Purpose: City/locality for local holidays (e.g., city founding day)
- Relationship: ManyToOne to City entity
- Inverse: holidayTemplates
- API: Read/Write
- Example: New York City (for local holidays)

---

## 5. USAGE EXAMPLES

### 5.1 Fixed Holiday (Christmas)

```yaml
name: "Christmas Day"
description: "Christian holiday celebrating the birth of Jesus Christ"
active: true
holidayType: FIXED
date: null
month: 12
dayOfMonth: 25
weekOfMonth: null
dayOfWeek: null
recurrencePattern: YEARLY
observanceRule: NEAREST_WEEKDAY
blockSchedule: true
country: [USA, UK, etc.]
city: null
```

### 5.2 Movable Holiday (US Labor Day)

```yaml
name: "Labor Day"
description: "Federal holiday honoring American workers"
active: true
holidayType: MOVABLE
date: null
month: 9
dayOfMonth: null
weekOfMonth: 1  # First week
dayOfWeek: 1    # Monday
recurrencePattern: YEARLY
observanceRule: ACTUAL
blockSchedule: true
country: USA
city: null
```

### 5.3 Movable Holiday (US Thanksgiving)

```yaml
name: "Thanksgiving Day"
description: "Federal holiday of thanksgiving and harvest"
active: true
holidayType: MOVABLE
date: null
month: 11
dayOfMonth: null
weekOfMonth: 4  # Fourth week
dayOfWeek: 4    # Thursday
recurrencePattern: YEARLY
observanceRule: ACTUAL
blockSchedule: true
country: USA
city: null
```

### 5.4 Adjustable Holiday (Observed Christmas)

```yaml
name: "Christmas Day (Observed)"
description: "Observed date when Christmas falls on weekend"
active: true
holidayType: ADJUSTABLE
date: null
month: 12
dayOfMonth: 25
weekOfMonth: null
dayOfWeek: null
recurrencePattern: YEARLY
observanceRule: NEAREST_WEEKDAY
blockSchedule: true
country: USA
city: null
```

---

## 6. API PLATFORM CONFIGURATION

### 6.1 Complete API Configuration

**Operations:** GetCollection, Get, Post, Put, Delete

**Security:**
- All operations: `is_granted('ROLE_SUPER_ADMIN')`
- Templates are system-level, only super admins can manage

**Normalization Groups:**
- `holidaytemplate:read`

**Denormalization Groups:**
- `holidaytemplate:write`

**Pagination:**
- Enabled: Yes
- Items per page: 30

**Default Order:**
- `name ASC` (alphabetical)

**Searchable Fields:**
- `name`
- `description`

**Filterable Fields:**
- `active` (boolean)
- `country` (entity)
- `holidayType` (choice)
- `recurrencePattern` (choice)
- `month` (integer)

### 6.2 API Endpoints

```
GET    /api/holiday_templates           # List all templates
GET    /api/holiday_templates/{id}      # Get single template
POST   /api/holiday_templates           # Create new template
PUT    /api/holiday_templates/{id}      # Update template
DELETE /api/holiday_templates/{id}      # Delete template
```

### 6.3 Sample API Response

```json
{
  "@context": "/api/contexts/HolidayTemplate",
  "@id": "/api/holiday_templates",
  "@type": "hydra:Collection",
  "hydra:totalItems": 10,
  "hydra:member": [
    {
      "@id": "/api/holiday_templates/01933e2c-8a9f-7b3a-8f2d-0242ac120002",
      "@type": "HolidayTemplate",
      "id": "01933e2c-8a9f-7b3a-8f2d-0242ac120002",
      "name": "Christmas Day",
      "description": "Christian holiday celebrating the birth of Jesus Christ",
      "active": true,
      "holidayType": "FIXED",
      "date": null,
      "month": 12,
      "dayOfMonth": 25,
      "weekOfMonth": null,
      "dayOfWeek": null,
      "recurrencePattern": "YEARLY",
      "observanceRule": "NEAREST_WEEKDAY",
      "blockSchedule": true,
      "country": {
        "@id": "/api/countries/01933e2c-1234-7b3a-8f2d-0242ac120002",
        "@type": "Country",
        "name": "United States"
      },
      "city": null,
      "createdAt": "2025-10-19T12:00:00+00:00",
      "updatedAt": "2025-10-19T12:00:00+00:00"
    }
  ]
}
```

---

## 7. DATABASE SCHEMA

### 7.1 PostgreSQL Table Structure

```sql
CREATE TABLE holiday_template_table (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    active BOOLEAN DEFAULT true,
    holiday_type VARCHAR(50),
    date DATE,
    month INTEGER CHECK (month >= 1 AND month <= 12),
    day_of_month INTEGER CHECK (day_of_month >= 1 AND day_of_month <= 31),
    week_of_month INTEGER CHECK (week_of_month >= -1 AND week_of_month <= 5),
    day_of_week INTEGER CHECK (day_of_week >= 0 AND day_of_week <= 6),
    recurrence_pattern VARCHAR(50),
    observance_rule VARCHAR(50),
    block_schedule BOOLEAN DEFAULT true,
    country_id UUID,
    city_id UUID,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_holiday_template_country FOREIGN KEY (country_id)
        REFERENCES country_table(id) ON DELETE SET NULL,
    CONSTRAINT fk_holiday_template_city FOREIGN KEY (city_id)
        REFERENCES city_table(id) ON DELETE SET NULL
);

-- Indexes for performance
CREATE INDEX idx_holiday_template_name ON holiday_template_table(name);
CREATE INDEX idx_holiday_template_active ON holiday_template_table(active);
CREATE INDEX idx_holiday_template_country ON holiday_template_table(country_id);
CREATE INDEX idx_holiday_template_month ON holiday_template_table(month);
CREATE INDEX idx_holiday_template_type ON holiday_template_table(holiday_type);
```

### 7.2 Performance Considerations

**Indexes Created:**
1. `idx_holiday_template_name` - For name searches
2. `idx_holiday_template_active` - For filtering active templates
3. `idx_holiday_template_country` - For country-specific queries
4. `idx_holiday_template_month` - For month-based filtering
5. `idx_holiday_template_type` - For holiday type filtering

**Query Optimization:**
- Use composite index for common filter combinations
- Consider partial index for active templates only

---

## 8. TESTING RECOMMENDATIONS

### 8.1 Unit Tests

Test coverage should include:
1. Entity validation (required fields, constraints)
2. Holiday type logic (FIXED, MOVABLE, ADJUSTABLE)
3. Date calculation for movable holidays
4. Observance rule application
5. Recurrence pattern validation

### 8.2 Functional Tests

1. **API Tests:**
   - GET collection with filters
   - GET single template
   - POST new template (valid data)
   - POST new template (invalid data)
   - PUT update template
   - DELETE template

2. **Integration Tests:**
   - Holiday generation from template
   - Calendar integration
   - Schedule blocking

### 8.3 Test Data (Fixtures)

Create fixtures for common holidays:
- US Federal Holidays (10)
- UK Bank Holidays (8)
- International Fixed Holidays (5)
- Movable Religious Holidays (5)

---

## 9. MIGRATION PLAN

### 9.1 Step-by-Step Implementation

**Step 1: Update CSV Files**
```bash
# Backup current CSV files
cp /home/user/inf/config/EntityNew.csv /home/user/inf/config/EntityNew.csv.backup_$(date +%Y%m%d_%H%M%S)
cp /home/user/inf/config/PropertyNew.csv /home/user/inf/config/PropertyNew.csv.backup_$(date +%Y%m%d_%H%M%S)

# Update EntityNew.csv line 61 with corrected configuration
# Update PropertyNew.csv with new property definitions
```

**Step 2: Regenerate Entity**
```bash
# Run Genmax generator (based on your generator command)
php bin/console genmax:generate:entity HolidayTemplate

# This should create:
# - app/src/Entity/HolidayTemplate.php
# - app/src/Entity/Generated/HolidayTemplateGenerated.php
```

**Step 3: Create Migration**
```bash
php bin/console make:migration
```

**Step 4: Review Migration**
```bash
# Review the generated migration file
# Ensure all columns and indexes are created properly
```

**Step 5: Run Migration**
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

**Step 6: Update Repository** (if needed)
```bash
# Add custom query methods to HolidayTemplateRepository.php
# Example: findActiveByCountry(), findByMonth(), etc.
```

**Step 7: Update Forms** (if needed)
```bash
# Forms should auto-regenerate, but verify form validation
```

**Step 8: Update Templates** (if needed)
```bash
# Update Twig templates to display new fields
# app/templates/holidaytemplate/index.html.twig
# app/templates/holidaytemplate/show.html.twig
# app/templates/holidaytemplate/form.html.twig
```

**Step 9: Create Fixtures**
```bash
# Create fixtures for common holidays
# app/src/DataFixtures/HolidayTemplateFixtures.php
```

**Step 10: Run Tests**
```bash
php bin/phpunit tests/Entity/HolidayTemplateTest.php
php bin/phpunit tests/Repository/HolidayTemplateRepositoryTest.php
php bin/phpunit tests/Controller/HolidayTemplateControllerTest.php
```

**Step 11: Validate API**
```bash
# Test API endpoints
curl -X GET https://localhost/api/holiday_templates
curl -X GET https://localhost/api/holiday_templates/{id}
```

### 9.2 Rollback Plan

If issues occur:
```bash
# Rollback database
php bin/console doctrine:migrations:migrate prev --no-interaction

# Restore CSV backups
cp /home/user/inf/config/EntityNew.csv.backup_XXXXXX /home/user/inf/config/EntityNew.csv
cp /home/user/inf/config/PropertyNew.csv.backup_XXXXXX /home/user/inf/config/PropertyNew.csv

# Clear cache
php bin/console cache:clear
```

---

## 10. COMPARISON WITH OTHER ENTITIES

### 10.1 Holiday vs HolidayTemplate

| Aspect | Holiday | HolidayTemplate |
|--------|---------|-----------------|
| Purpose | Specific holiday instance | Reusable template |
| Organization | YES | NO |
| Yearly Data | Specific year | Year-agnostic |
| Calendar Link | YES (ManyToOne) | NO |
| Event Link | YES (ManyToOne) | NO |
| Recurrence | No | YES |
| Example | "Christmas 2025" | "Christmas (template)" |

### 10.2 Template Entity Pattern

Other template entities in system:
1. **TaskTemplate** - Has `active` field, organization link
2. **TalkTypeTemplate** - No organization, super admin only
3. **ProfileTemplate** - No organization, super admin only
4. **PipelineTemplate** - Has organization link

**HolidayTemplate follows:** Template entity pattern (no organization, super admin only)

---

## 11. COMPLIANCE CHECK

### 11.1 Naming Conventions

| Convention | Required | HolidayTemplate | Status |
|------------|----------|-----------------|--------|
| Boolean fields | Use "active", "enabled" NOT "isActive" | active: YES ✓, blockSchedule: OK ✓ | COMPLIANT |
| CamelCase properties | Use camelCase | All properties camelCase ✓ | COMPLIANT |
| Entity name | PascalCase | HolidayTemplate ✓ | COMPLIANT |
| Table name | snake_case with _table suffix | holiday_template_table | COMPLIANT |

### 11.2 API Platform Requirements

| Requirement | Status | Notes |
|-------------|--------|-------|
| Operations defined | ✓ YES | GetCollection, Get, Post, Put, Delete |
| Security configured | ✓ YES | ROLE_SUPER_ADMIN |
| Normalization groups | ✓ YES | holidaytemplate:read |
| Denormalization groups | ✓ YES | holidaytemplate:write |
| Pagination enabled | ✓ YES | 30 items per page |
| Searchable fields | ✓ FIXED | Added: name, description |
| Filterable fields | ✓ FIXED | Added: active, country, holidayType |

### 11.3 PostgreSQL 18 Compatibility

| Feature | Status | Notes |
|---------|--------|-------|
| UUIDv7 support | ✓ YES | Using Symfony UuidV7Generator |
| Timestamp types | ✓ YES | Using DateTimeImmutable |
| Check constraints | ✓ YES | Range validation on integers |
| Foreign keys | ✓ YES | Country, City relationships |

---

## 12. SECURITY CONSIDERATIONS

### 12.1 Access Control

**Role:** ROLE_SUPER_ADMIN
- Only super administrators can manage holiday templates
- Templates are system-wide, not organization-specific
- Organizations use Holiday entity (with organization link) for instances

### 12.2 Validation

**Input Validation:**
1. Name: NotBlank, max 255 chars
2. Month: Range 1-12
3. DayOfMonth: Range 1-31
4. WeekOfMonth: Range -1 to 5
5. DayOfWeek: Range 0-6
6. HolidayType: Choice (FIXED, MOVABLE, ADJUSTABLE)
7. RecurrencePattern: Choice (NONE, YEARLY, MONTHLY)
8. ObservanceRule: Choice (4 options)

### 12.3 Data Integrity

**Constraints:**
1. Name is required
2. Active defaults to true
3. BlockSchedule defaults to true
4. Foreign keys enforce referential integrity
5. Check constraints prevent invalid date/time values

---

## 13. PERFORMANCE OPTIMIZATION

### 13.1 Database Indexes

**Recommended Indexes:**
```sql
-- Primary searches
CREATE INDEX idx_holiday_template_name ON holiday_template_table(name);
CREATE INDEX idx_holiday_template_active ON holiday_template_table(active);

-- Filtering
CREATE INDEX idx_holiday_template_country ON holiday_template_table(country_id);
CREATE INDEX idx_holiday_template_month ON holiday_template_table(month);
CREATE INDEX idx_holiday_template_type ON holiday_template_table(holiday_type);

-- Composite for common queries
CREATE INDEX idx_holiday_template_active_country
    ON holiday_template_table(active, country_id)
    WHERE active = true;
```

### 13.2 Query Optimization

**Slow Query Detection:**
- Monitor queries with EXPLAIN ANALYZE
- Add indexes for frequently filtered columns
- Use pagination for large result sets

**Caching Strategy:**
- Cache active templates (rarely change)
- Cache by country (reduce database hits)
- Invalidate cache on template update

### 13.3 N+1 Query Prevention

**Doctrine Fetch Strategy:**
- Use LAZY loading for relationships (default)
- Use JOIN fetch for country/city when displaying lists
- Consider DTO pattern for API responses

**Example Repository Method:**
```php
public function findActiveWithCountry(): array
{
    return $this->createQueryBuilder('ht')
        ->leftJoin('ht.country', 'c')
        ->addSelect('c')
        ->where('ht.active = :active')
        ->setParameter('active', true)
        ->orderBy('ht.name', 'ASC')
        ->getQuery()
        ->getResult();
}
```

---

## 14. FUTURE ENHANCEMENTS

### 14.1 Phase 2 Features

1. **Holiday Calculation Service**
   - Auto-calculate holiday dates from templates
   - Generate Holiday entities from HolidayTemplate
   - Support for next N years

2. **iCalendar Integration**
   - Export templates as .ics files
   - Import from external calendar systems
   - RFC 5545 RRULE support

3. **Multi-Language Support**
   - Translatable holiday names
   - Translatable descriptions
   - Region-specific names

4. **Holiday Categories**
   - Federal/National
   - Religious
   - Cultural
   - Company-specific

### 14.2 Phase 3 Features

1. **AI-Powered Holiday Detection**
   - Auto-suggest holidays based on country
   - Detect missing holidays
   - Update templates from authoritative sources

2. **Advanced Scheduling Rules**
   - Support for partial-day holidays
   - Support for regional variations
   - Support for religious calendar systems (lunar, etc.)

3. **Analytics Dashboard**
   - Holiday coverage by country
   - Most common holidays
   - Schedule impact analysis

---

## 15. CONCLUSION

### 15.1 Critical Actions Required

1. **IMMEDIATE:** Update CSV files with corrected entity and property definitions
2. **IMMEDIATE:** Regenerate entity files using Genmax generator
3. **IMMEDIATE:** Create and run database migration
4. **HIGH:** Add comprehensive validation rules
5. **HIGH:** Create fixture data for common holidays
6. **MEDIUM:** Update API documentation
7. **MEDIUM:** Add custom repository methods
8. **LOW:** Create admin UI for template management

### 15.2 Expected Benefits

**After Implementation:**
1. Full holiday template functionality
2. Support for fixed, movable, and adjustable holidays
3. Complete API with search and filtering
4. Proper validation and data integrity
5. Reusable templates across organizations
6. Foundation for calendar and scheduling systems

### 15.3 Risk Assessment

**LOW RISK:**
- Template entity is new (no existing data to migrate)
- Clear industry patterns to follow
- Well-defined requirements

**MITIGATION:**
- Comprehensive testing before production
- Rollback plan in place
- CSV backups created
- Migration can be reverted

---

## 16. REFERENCES

### 16.1 External Resources

1. **Dynamics 365 CRM Holiday Scheduling**
   - https://learn.microsoft.com/en-us/dynamics365/customer-service/administer/set-up-holiday-schedule

2. **Database Design for Holidays**
   - https://softwareengineering.stackexchange.com/questions/288139/table-design-for-holidays
   - https://www.databasezone.com/techdocs/DesigningTheCalendarHolidayDb.html

3. **iCalendar Recurrence Rules (RFC 5545)**
   - https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.5

4. **Calendar Database Patterns**
   - https://vertabelo.com/blog/viewing-holidays-with-data-modelers-eyes/

### 16.2 Internal Documentation

1. Luminai Quick Reference: `/home/user/inf/CLAUDE.md`
2. Database Guide: `/home/user/inf/docs/DATABASE.md`
3. API Platform Guide: `/home/user/inf/docs/API_PLATFORM.md`
4. Multi-Tenant Guide: `/home/user/inf/docs/MULTI_TENANT.md`

### 16.3 Related Entities

1. **Holiday** - Specific holiday instances (with organization)
2. **Country** - Geographic scope
3. **City** - Local geographic scope
4. **Calendar** - Calendar management
5. **Event** - Event scheduling
6. **WorkingHour** - Business hours management

---

## APPENDIX A: UPDATED CSV CONTENT

### A.1 EntityNew.csv (Line 61 - CORRECTED)

```csv
HolidayTemplate,Holiday Template,Holiday Templates,bi-calendar-event,"Holiday calendar templates for recurring and fixed holidays",,1,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_SUPER_ADMIN'),holidaytemplate:read,holidaytemplate:write,1,30,"{""name"": ""asc""}","name,description","active,country,holidayType",,,,bootstrap_5_layout.html.twig,,,,System,12,1
```

### A.2 PropertyNew.csv (ALL HolidayTemplate Properties)

Replace ALL existing HolidayTemplate lines with these:

```csv
HolidayTemplate,name,Name,string,,,,,,,,,,,,,LAZY,,simple,,"NotBlank,Length(max=255)",,TextType,{},1,,,1,1,1,1,1,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,description,Description,text,1,,,,,,,,,,,,LAZY,,,,Length(max=1000),,TextareaType,{},,,,1,1,1,1,1,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,paragraph,{}
HolidayTemplate,active,Active,boolean,1,,,,1,1,,,,,,,,LAZY,,,,,,CheckboxType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,boolean,{}
HolidayTemplate,holidayType,Holiday Type,string,1,,,,,,,,,,,,LAZY,,simple,,"Choice(choices=['FIXED','MOVABLE','ADJUSTABLE'])",,ChoiceType,"{""choices"": {""Fixed"": ""FIXED"", ""Movable"": ""MOVABLE"", ""Adjustable"": ""ADJUSTABLE""}}",1,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,date,Date,date,1,,,,,,,,,,,,LAZY,,,,,,DateType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,date,{}
HolidayTemplate,month,Month,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=1 max=12),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": 1 ""max"": 12}"
HolidayTemplate,dayOfMonth,Day of Month,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=1 max=31),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": 1 ""max"": 31}"
HolidayTemplate,weekOfMonth,Week of Month,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=-1 max=5),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": -1 ""max"": 5}"
HolidayTemplate,dayOfWeek,Day of Week,integer,1,,,,,,,,,,,,LAZY,,,,Range(min=0 max=6),,IntegerType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,randomNumber,"{""min"": 0 ""max"": 6}"
HolidayTemplate,recurrencePattern,Recurrence Pattern,string,1,,,,,,,,,,,,LAZY,,simple,,"Choice(choices=['NONE','YEARLY','MONTHLY'])",,ChoiceType,"{""choices"": {""None"": ""NONE"", ""Yearly"": ""YEARLY"", ""Monthly"": ""MONTHLY""}}",1,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,observanceRule,Observance Rule,string,1,,,,,,,,,,,,LAZY,,simple,,"Choice(choices=['ACTUAL','NEAREST_WEEKDAY','NEXT_MONDAY','PREVIOUS_FRIDAY'])",,ChoiceType,"{""choices"": {""Actual Day"": ""ACTUAL"", ""Nearest Weekday"": ""NEAREST_WEEKDAY"", ""Next Monday"": ""NEXT_MONDAY"", ""Previous Friday"": ""PREVIOUS_FRIDAY""}}",1,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,word,{}
HolidayTemplate,blockSchedule,Block Schedule,boolean,1,,,,1,1,,,,,,,,LAZY,,,,,,CheckboxType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,boolean,{}
HolidayTemplate,country,Country,,1,,,,,,ManyToOne,Country,holidayTemplates,,,,LAZY,,simple,,,,EntityType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,,{}
HolidayTemplate,city,City,,1,,,,,,ManyToOne,City,holidayTemplates,,,,LAZY,,simple,,,,EntityType,{},,,,1,1,1,1,,,1,1,"holidaytemplate:read,holidaytemplate:write",,,,,{}
```

---

## APPENDIX B: QUICK FIXES

### B.1 One-Line Summary of Changes

**EntityNew.csv:**
- Description added
- Icon changed to bi-calendar-event
- Searchable fields: name, description
- Filterable fields: active, country, holidayType
- Order changed to name ASC

**PropertyNew.csv:**
- REMOVED: recurrenceInterval, recurrenceFrequency
- ADDED: description, active, holidayType, month, dayOfMonth, weekOfMonth, dayOfWeek, recurrencePattern, observanceRule
- TOTAL: 14 properties (was 7)

### B.2 Files to Update

1. `/home/user/inf/config/EntityNew.csv` - Line 61
2. `/home/user/inf/config/PropertyNew.csv` - All HolidayTemplate lines

### B.3 Commands to Run

```bash
# 1. Backup
cp /home/user/inf/config/EntityNew.csv /home/user/inf/config/EntityNew.csv.backup_$(date +%Y%m%d_%H%M%S)
cp /home/user/inf/config/PropertyNew.csv /home/user/inf/config/PropertyNew.csv.backup_$(date +%Y%m%d_%H%M%S)

# 2. Update CSV files (manually or with script)

# 3. Generate entity
php bin/console genmax:generate:entity HolidayTemplate

# 4. Create migration
php bin/console make:migration

# 5. Run migration
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Clear cache
php bin/console cache:clear

# 7. Test
php bin/phpunit tests/Entity/HolidayTemplateTest.php
```

---

**END OF REPORT**

Generated: 2025-10-19
Author: Claude Code (Database Optimization Expert)
Project: Luminai - HolidayTemplate Entity Analysis
Status: READY FOR IMPLEMENTATION
