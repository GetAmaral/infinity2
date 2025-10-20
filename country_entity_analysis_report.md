# Country Entity - Comprehensive Analysis Report

**Generated**: 2025-10-19
**Database**: PostgreSQL 18
**Entity ID**: 0199cadd-6283-79d9-b2f4-a52101ced83f
**Analysis Type**: Full Property Audit + Best Practices Assessment

---

## Executive Summary

The **Country** entity currently has **CRITICAL DEFICIENCIES** that prevent it from being a production-ready geographic reference entity for a modern CRM system. The entity is missing essential ISO standard properties, lacks proper boolean naming conventions, has incomplete API configuration, and is missing critical fields for international operations.

**Status**: INCOMPLETE - Requires immediate remediation

---

## 1. Current Entity Configuration

### Entity Metadata
```yaml
Entity Name: Country
Entity Label: Country
Plural Label: Countries
API Enabled: true
API Security: is_granted('ROLE_SUPER_ADMIN')
Has Organization: false (System entity)
Menu Group: System
Menu Order: 6
Icon: bi-globe
```

### API Platform Configuration
```yaml
Operations: [GetCollection, Get, Post, Put, Delete]
Normalization Context: {"groups" : ["country:read"]}
Denormalization Context: {"groups" : ["country:write"]}
Default Order: {"createdAt":"desc"}
```

---

## 2. Current Properties (3 Total)

| Property Name | Type | Length | Nullable | Unique | Indexed | Issues |
|--------------|------|--------|----------|--------|---------|---------|
| **name** | string | NULL | false | false | false | No length constraint, missing iso2 alternative, not indexed |
| **dialingCode** | string | 3 | true | false | false | WRONG: Should be "phoneCode", too short (needs 10), nullable should be false |
| **holidayTemplates** | OneToMany | - | true | - | - | Relationship configured but unrelated to core country data |

---

## 3. Critical Issues Identified

### 3.1 Naming Convention Violations

#### Issue 1: Property "dialingCode"
**Severity**: HIGH
**Problem**: Violates naming convention - should be "phoneCode" for consistency with international standards
**Convention**: CRM systems in 2025 standardize on "phoneCode" or "callingCode"
**Fix Required**: Rename to `phoneCode`

#### Issue 2: Missing Boolean Convention Properties
**Severity**: CRITICAL
**Problem**: Boolean properties MUST use naming pattern: "active", "euMember" NOT "isActive", "isEuMember"
**Convention**: Project standard per CLAUDE.md requirements
**Missing Properties**:
- `active` (boolean) - Country is active/enabled
- `euMember` (boolean) - Country is EU member state
- `schengenMember` (boolean) - Country is Schengen Area member
- `oecd Member` (boolean) - OECD membership status

### 3.2 Missing ISO Standard Properties

#### Critical ISO Fields (MUST HAVE)
**Severity**: CRITICAL
**Impact**: Cannot integrate with international systems, payment gateways, or comply with data localization requirements

Missing properties:
1. **iso2** (string, length: 2, unique: true, indexed: true)
   - ISO 3166-1 alpha-2 code
   - Example: "US", "GB", "DE"
   - Required for: Currency mapping, domain TLDs, international APIs

2. **iso3** (string, length: 3, unique: true, indexed: true)
   - ISO 3166-1 alpha-3 code
   - Example: "USA", "GBR", "DEU"
   - Required for: Banking systems, shipping APIs, UN standards

3. **numericCode** (string, length: 3, unique: true)
   - ISO 3166-1 numeric code
   - Example: "840", "826", "276"
   - Required for: Non-Latin script compatibility

4. **currencyCode** (string, length: 3, nullable: false)
   - ISO 4217 currency code
   - Example: "USD", "EUR", "GBP"
   - Required for: E-commerce, invoicing, financial transactions

5. **currencySymbol** (string, length: 5)
   - Currency symbol
   - Example: "$", "€", "£"
   - Required for: Display formatting, user interface

### 3.3 Missing Geographic Properties

**Severity**: HIGH
**Impact**: Cannot perform geographic segmentation, timezone handling, or regional analysis

Missing properties:
1. **capital** (string, length: 100)
   - Capital city name
   - Example: "Washington, D.C.", "London", "Berlin"

2. **continent** (string, length: 50)
   - Continent name
   - Example: "North America", "Europe", "Asia"
   - Options: Africa, Antarctica, Asia, Europe, North America, Oceania, South America

3. **region** (string, length: 100)
   - UN geographic region
   - Example: "Western Europe", "Northern America"

4. **subregion** (string, length: 100)
   - UN geographic subregion
   - Example: "Southern Europe", "Central Asia"

5. **latitude** (decimal, precision: 10, scale: 7)
   - Geographic latitude of capital/center
   - Example: 38.9072

6. **longitude** (decimal, precision: 10, scale: 7)
   - Geographic longitude of capital/center
   - Example: -77.0369

7. **timezones** (json, nullable: true)
   - Array of timezone identifiers
   - Example: ["America/New_York", "America/Chicago"]

### 3.4 Missing Localization Properties

**Severity**: MEDIUM
**Impact**: Poor internationalization support, limited multi-language capability

Missing properties:
1. **nativeName** (string, length: 100)
   - Country name in native language
   - Example: "Deutschland" (for Germany)

2. **officialName** (string, length: 200)
   - Official country name
   - Example: "United States of America"

3. **nationalityName** (string, length: 100)
   - Demonym/nationality name
   - Example: "American", "British", "German"

4. **languages** (json)
   - Array of official language codes (ISO 639)
   - Example: ["en", "es"] for USA

5. **tld** (string, length: 10)
   - Country code top-level domain
   - Example: ".us", ".uk", ".de"

### 3.5 Missing Administrative Properties

**Severity**: MEDIUM
**Impact**: Cannot manage country data lifecycle, visibility, or business rules

Missing properties:
1. **active** (boolean, default: true)
   - Whether country is active in system
   - **CRITICAL**: Uses correct boolean convention (NOT "isActive")

2. **availableForShipping** (boolean, default: true)
   - Country available for shipping operations

3. **availableForBilling** (boolean, default: true)
   - Country available for billing operations

4. **euMember** (boolean, default: false)
   - EU membership status
   - **CRITICAL**: Uses correct boolean convention (NOT "isEuMember")

5. **schengenMember** (boolean, default: false)
   - Schengen Area membership

6. **oecdMember** (boolean, default: false)
   - OECD membership

7. **dataResidencyRequired** (boolean, default: false)
   - Data localization laws in effect

8. **taxIdRequired** (boolean, default: false)
   - Tax ID/VAT required for transactions

9. **postalCodeFormat** (string, length: 50)
   - Postal code regex pattern
   - Example: "^\d{5}(-\d{4})?$" for USA

10. **postalCodeRequired** (boolean, default: true)
    - Whether postal code is mandatory

11. **addressFormat** (text)
    - Address format template
    - Example: "{street}\n{city}, {region} {postalCode}"

### 3.6 Missing Statistical/Reference Properties

**Severity**: LOW
**Impact**: Limited analytical and reporting capabilities

Missing properties:
1. **population** (integer)
   - Current population estimate

2. **area** (decimal, precision: 12, scale: 2)
   - Total area in square kilometers

3. **unMemberSince** (integer)
   - Year joined United Nations (or NULL)

4. **callingCode** (string, length: 10) - REPLACES dialingCode
   - International dialing code with + prefix
   - Example: "+1", "+44", "+49"
   - Note: Some countries have multiple codes

5. **flagEmoji** (string, length: 10)
   - Unicode flag emoji
   - Example: "🇺🇸", "🇬🇧", "🇩🇪"

6. **flagSvgUrl** (string, length: 255)
   - URL to official flag SVG

---

## 4. API Platform Configuration Issues

### 4.1 Missing Filter Configuration

**Severity**: HIGH
**Problem**: No properties have API filters configured

Current state:
- `filter_strategy`: NULL for all properties
- `filter_searchable`: NULL for all properties
- `filter_orderable`: NULL for all properties
- `filter_boolean`: NULL for all properties

Required fixes:

| Property | filter_strategy | filter_searchable | filter_orderable | filter_boolean |
|----------|----------------|-------------------|------------------|----------------|
| name | partial | true | true | false |
| iso2 | exact | true | true | false |
| iso3 | exact | true | true | false |
| continent | exact | false | true | false |
| region | exact | false | true | false |
| active | - | false | true | true |
| euMember | - | false | true | true |
| currencyCode | exact | true | true | false |

### 4.2 Missing API Groups Configuration

**Severity**: MEDIUM
**Problem**: Properties not assigned to API normalization/denormalization groups

All properties should have:
- `api_readable`: true
- `api_writable`: true (except computed fields)
- `api_groups`: JSON array with read/write groups

Suggested API groups:
```json
{
  "read": ["country:read", "country:list"],
  "write": ["country:write"]
}
```

### 4.3 Missing Validation Rules

**Severity**: HIGH
**Problem**: Only "name" has validation, other fields unvalidated

Current validation:
- `name`: `["NotBlank"]` - GOOD but incomplete
- `dialingCode`: `[{"max": 3, "constraint": "Length"}]` - WRONG format
- `holidayTemplates`: NULL

Required validation rules:

```json
{
  "name": ["NotBlank", {"constraint": "Length", "min": 2, "max": 100}],
  "iso2": ["NotBlank", {"constraint": "Length", "min": 2, "max": 2}, {"constraint": "Regex", "pattern": "/^[A-Z]{2}$/"}],
  "iso3": ["NotBlank", {"constraint": "Length", "min": 3, "max": 3}, {"constraint": "Regex", "pattern": "/^[A-Z]{3}$/"}],
  "numericCode": ["NotBlank", {"constraint": "Length", "min": 3, "max": 3}, {"constraint": "Regex", "pattern": "/^\\d{3}$/"}],
  "currencyCode": ["NotBlank", {"constraint": "Length", "min": 3, "max": 3}, {"constraint": "Regex", "pattern": "/^[A-Z]{3}$/"}],
  "phoneCode": ["NotBlank", {"constraint": "Length", "max": 10}, {"constraint": "Regex", "pattern": "/^\\+\\d{1,4}$/"}]
}
```

### 4.4 Missing Property Descriptions

**Severity**: LOW
**Problem**: No `api_description` or `api_example` fields populated

All properties should have:
- Clear API descriptions
- Example values for documentation

---

## 5. Database Schema Issues

### 5.1 Missing Indexes

**Severity**: HIGH
**Performance Impact**: Severe for lookups by ISO codes

Required indexes:
1. `name` - frequently searched, not indexed
2. `iso2` - PRIMARY lookup field, MUST be indexed + unique
3. `iso3` - Alternative lookup, MUST be indexed + unique
4. `numericCode` - Alternative lookup, should be indexed + unique
5. `currencyCode` - Common filter field, should be indexed
6. `continent` - Common grouping field, should be indexed

### 5.2 Missing Unique Constraints

**Severity**: CRITICAL
**Data Integrity Risk**: Duplicate countries possible

Required unique constraints:
1. `name` - Should be unique (one "United States")
2. `iso2` - MUST be unique (ISO standard requirement)
3. `iso3` - MUST be unique (ISO standard requirement)
4. `numericCode` - MUST be unique (ISO standard requirement)

### 5.3 Missing NOT NULL Constraints

**Severity**: HIGH
**Data Quality Risk**: Incomplete records possible

Properties that MUST NOT be nullable:
1. `name` - Already correct
2. `iso2` - Should be NOT NULL
3. `iso3` - Should be NOT NULL
4. `numericCode` - Should be NOT NULL
5. `currencyCode` - Should be NOT NULL
6. `phoneCode` (rename from dialingCode) - Should be NOT NULL
7. `continent` - Should be NOT NULL
8. `active` - Should be NOT NULL with default true

---

## 6. Industry Best Practices Comparison

### 6.1 CRM Geographic Standards (2025)

Based on research of leading CRM platforms (HubSpot, Dynamics 365, Salesforce):

**Required Properties** ✓ = Has, ✗ = Missing:
- ✗ ISO 3166-1 alpha-2 code (iso2)
- ✗ ISO 3166-1 alpha-3 code (iso3)
- ✗ ISO 4217 currency code (currencyCode)
- ✗ Phone code with + prefix (phoneCode)
- ✓ Country name (name)
- ✗ Capital city (capital)
- ✗ Continent (continent)
- ✗ Active status (active) - MUST use correct convention
- ✗ Data residency compliance (dataResidencyRequired)

**Score**: 1/9 (11%) - FAILING

### 6.2 E-Commerce Standards

**Required Properties** for e-commerce integration:
- ✗ ISO2 code for currency mapping
- ✗ Currency code (currencyCode)
- ✗ Postal code format validation (postalCodeFormat)
- ✗ Shipping availability flag (availableForShipping)
- ✗ Billing availability flag (availableForBilling)
- ✗ Tax ID requirements (taxIdRequired)
- ✗ Address format template (addressFormat)

**Score**: 0/7 (0%) - FAILING

### 6.3 Data Localization Compliance (2025)

Modern CRM systems MUST track:
- ✗ Data residency requirements (dataResidencyRequired)
- ✗ EU membership status (euMember)
- ✗ Regional grouping (region, subregion)
- ✗ Active status for GDPR compliance (active)

**Score**: 0/4 (0%) - FAILING

---

## 7. Recommended Property Additions (Priority Order)

### CRITICAL (Must implement immediately)

1. **iso2** - string(2), unique, indexed, not null
   - ISO 3166-1 alpha-2 code
   - Validation: `/^[A-Z]{2}$/`
   - Example: "US"

2. **iso3** - string(3), unique, indexed, not null
   - ISO 3166-1 alpha-3 code
   - Validation: `/^[A-Z]{3}$/`
   - Example: "USA"

3. **numericCode** - string(3), unique, indexed, not null
   - ISO 3166-1 numeric code
   - Validation: `/^\d{3}$/`
   - Example: "840"

4. **currencyCode** - string(3), indexed, not null
   - ISO 4217 currency code
   - Validation: `/^[A-Z]{3}$/`
   - Example: "USD"

5. **phoneCode** - string(10), not null [RENAME from dialingCode]
   - International calling code
   - Validation: `/^\+\d{1,4}$/`
   - Example: "+1"

6. **active** - boolean, default: true, not null
   - Country is active in system
   - **CRITICAL**: Uses correct convention (NOT "isActive")

7. **continent** - string(50), indexed, not null
   - Continent name
   - Example: "North America"

### HIGH Priority

8. **capital** - string(100)
   - Capital city name
   - Example: "Washington, D.C."

9. **currencySymbol** - string(5)
   - Currency display symbol
   - Example: "$"

10. **euMember** - boolean, default: false
    - EU membership status
    - **CRITICAL**: Uses correct convention (NOT "isEuMember")

11. **region** - string(100)
    - UN geographic region
    - Example: "Northern America"

12. **nativeName** - string(100)
    - Native language name
    - Example: "United States of America"

13. **officialName** - string(200)
    - Official full name
    - Example: "United States of America"

### MEDIUM Priority

14. **subregion** - string(100)
    - UN geographic subregion
    - Example: "Northern America"

15. **latitude** - decimal(10, 7)
    - Geographic latitude
    - Example: 38.9072

16. **longitude** - decimal(10, 7)
    - Geographic longitude
    - Example: -77.0369

17. **timezones** - json
    - Array of timezone identifiers
    - Example: `["America/New_York", "America/Chicago"]`

18. **languages** - json
    - Array of official language codes
    - Example: `["en", "es"]`

19. **tld** - string(10)
    - Top-level domain
    - Example: ".us"

20. **nationalityName** - string(100)
    - Demonym
    - Example: "American"

21. **availableForShipping** - boolean, default: true
    - Shipping operations enabled

22. **availableForBilling** - boolean, default: true
    - Billing operations enabled

23. **schengenMember** - boolean, default: false
    - Schengen Area membership

24. **oecdMember** - boolean, default: false
    - OECD membership

25. **dataResidencyRequired** - boolean, default: false
    - Data localization laws

26. **postalCodeFormat** - string(50)
    - Postal code regex pattern
    - Example: "^\d{5}(-\d{4})?$"

27. **postalCodeRequired** - boolean, default: true
    - Postal code mandatory flag

28. **addressFormat** - text
    - Address template
    - Example: "{street}\n{city}, {region} {postalCode}"

29. **taxIdRequired** - boolean, default: false
    - Tax ID requirement

### LOW Priority

30. **population** - integer
    - Population estimate

31. **area** - decimal(12, 2)
    - Total area (km²)

32. **unMemberSince** - integer
    - UN membership year

33. **flagEmoji** - string(10)
    - Unicode flag
    - Example: "🇺🇸"

34. **flagSvgUrl** - string(255)
    - Flag image URL

---

## 8. Complete Entity Definition (Recommended)

### SQL Migration Script

```sql
-- Add missing critical properties
ALTER TABLE country ADD COLUMN iso2 VARCHAR(2) UNIQUE NOT NULL;
ALTER TABLE country ADD COLUMN iso3 VARCHAR(3) UNIQUE NOT NULL;
ALTER TABLE country ADD COLUMN numeric_code VARCHAR(3) UNIQUE NOT NULL;
ALTER TABLE country ADD COLUMN currency_code VARCHAR(3) NOT NULL;
ALTER TABLE country RENAME COLUMN dialing_code TO phone_code;
ALTER TABLE country ALTER COLUMN phone_code TYPE VARCHAR(10);
ALTER TABLE country ALTER COLUMN phone_code SET NOT NULL;
ALTER TABLE country ADD COLUMN active BOOLEAN DEFAULT true NOT NULL;
ALTER TABLE country ADD COLUMN continent VARCHAR(50) NOT NULL;

-- Add high priority properties
ALTER TABLE country ADD COLUMN capital VARCHAR(100);
ALTER TABLE country ADD COLUMN currency_symbol VARCHAR(5);
ALTER TABLE country ADD COLUMN eu_member BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE country ADD COLUMN region VARCHAR(100);
ALTER TABLE country ADD COLUMN native_name VARCHAR(100);
ALTER TABLE country ADD COLUMN official_name VARCHAR(200);

-- Add medium priority properties
ALTER TABLE country ADD COLUMN subregion VARCHAR(100);
ALTER TABLE country ADD COLUMN latitude DECIMAL(10, 7);
ALTER TABLE country ADD COLUMN longitude DECIMAL(10, 7);
ALTER TABLE country ADD COLUMN timezones JSON;
ALTER TABLE country ADD COLUMN languages JSON;
ALTER TABLE country ADD COLUMN tld VARCHAR(10);
ALTER TABLE country ADD COLUMN nationality_name VARCHAR(100);
ALTER TABLE country ADD COLUMN available_for_shipping BOOLEAN DEFAULT true NOT NULL;
ALTER TABLE country ADD COLUMN available_for_billing BOOLEAN DEFAULT true NOT NULL;
ALTER TABLE country ADD COLUMN schengen_member BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE country ADD COLUMN oecd_member BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE country ADD COLUMN data_residency_required BOOLEAN DEFAULT false NOT NULL;
ALTER TABLE country ADD COLUMN postal_code_format VARCHAR(50);
ALTER TABLE country ADD COLUMN postal_code_required BOOLEAN DEFAULT true NOT NULL;
ALTER TABLE country ADD COLUMN address_format TEXT;
ALTER TABLE country ADD COLUMN tax_id_required BOOLEAN DEFAULT false NOT NULL;

-- Add low priority properties
ALTER TABLE country ADD COLUMN population INTEGER;
ALTER TABLE country ADD COLUMN area DECIMAL(12, 2);
ALTER TABLE country ADD COLUMN un_member_since INTEGER;
ALTER TABLE country ADD COLUMN flag_emoji VARCHAR(10);
ALTER TABLE country ADD COLUMN flag_svg_url VARCHAR(255);

-- Add indexes for performance
CREATE INDEX idx_country_iso2 ON country(iso2);
CREATE INDEX idx_country_iso3 ON country(iso3);
CREATE INDEX idx_country_numeric_code ON country(numeric_code);
CREATE INDEX idx_country_currency_code ON country(currency_code);
CREATE INDEX idx_country_continent ON country(continent);
CREATE INDEX idx_country_active ON country(active);
CREATE INDEX idx_country_name ON country(name);

-- Add unique constraint to name
ALTER TABLE country ADD CONSTRAINT uk_country_name UNIQUE (name);
```

---

## 9. Genmax Property Configuration

### Property Configuration Examples

For each property, configure in `generator_property` table:

#### Example: iso2 Property
```php
$iso2 = new GeneratorProperty();
$iso2->setEntity($country);
$iso2->setPropertyName('iso2');
$iso2->setPropertyLabel('ISO 3166-1 Alpha-2');
$iso2->setPropertyType('string');
$iso2->setLength(2);
$iso2->setNullable(false);
$iso2->setUnique(true);
$iso2->setIndexed(true);
$iso2->setFilterStrategy('exact');
$iso2->setFilterSearchable(true);
$iso2->setFilterOrderable(true);
$iso2->setApiReadable(true);
$iso2->setApiWritable(true);
$iso2->setValidationRules([
    'NotBlank' => [],
    'Length' => ['min' => 2, 'max' => 2],
    'Regex' => ['pattern' => '/^[A-Z]{2}$/']
]);
$iso2->setApiDescription('Two-letter country code (ISO 3166-1 alpha-2)');
$iso2->setApiExample('US');
```

#### Example: active Property (Boolean Convention)
```php
$active = new GeneratorProperty();
$active->setEntity($country);
$active->setPropertyName('active'); // CORRECT - NOT "isActive"
$active->setPropertyLabel('Active');
$active->setPropertyType('boolean');
$active->setNullable(false);
$active->setDefaultValue('true');
$active->setFilterBoolean(true);
$active->setFilterOrderable(true);
$active->setApiReadable(true);
$active->setApiWritable(true);
$active->setApiDescription('Whether country is active in the system');
$active->setApiExample(true);
```

#### Example: euMember Property (Boolean Convention)
```php
$euMember = new GeneratorProperty();
$euMember->setEntity($country);
$euMember->setPropertyName('euMember'); // CORRECT - NOT "isEuMember"
$euMember->setPropertyLabel('EU Member');
$euMember->setPropertyType('boolean');
$euMember->setNullable(false);
$euMember->setDefaultValue('false');
$euMember->setFilterBoolean(true);
$euMember->setFilterOrderable(true);
$euMember->setApiReadable(true);
$euMember->setApiWritable(true);
$euMember->setApiDescription('Whether country is a European Union member state');
$euMember->setApiExample(false);
```

---

## 10. API Platform Complete Configuration

### Required Updates to generator_entity

```php
$country->setApiOperations(['GetCollection', 'Get', 'Post', 'Put', 'Delete']);
$country->setApiSecurity("is_granted('ROLE_USER')"); // Change from SUPER_ADMIN
$country->setOperationSecurity([
    'Post' => "is_granted('ROLE_ADMIN')",
    'Put' => "is_granted('ROLE_ADMIN')",
    'Delete' => "is_granted('ROLE_SUPER_ADMIN')"
]);
$country->setApiNormalizationContext([
    'groups' => ['country:read', 'country:list']
]);
$country->setApiDenormalizationContext([
    'groups' => ['country:write']
]);
$country->setApiDefaultOrder([
    'name' => 'ASC' // Change from createdAt DESC
]);
```

### Expected API YAML Output (config/api_platform/Country.yaml)

```yaml
resources:
  App\Entity\Country:
    shortName: Country

    normalizationContext:
      groups: ["country:read", "country:list"]

    denormalizationContext:
      groups: ["country:write"]

    order:
      name: ASC

    security: "is_granted('ROLE_USER')"

    operations:
      - class: ApiPlatform\Metadata\GetCollection
        security: "is_granted('ROLE_USER')"

      - class: ApiPlatform\Metadata\Get
        security: "is_granted('ROLE_USER')"

      - class: ApiPlatform\Metadata\Post
        security: "is_granted('ROLE_ADMIN')"

      - class: ApiPlatform\Metadata\Put
        security: "is_granted('ROLE_ADMIN')"

      - class: ApiPlatform\Metadata\Delete
        security: "is_granted('ROLE_SUPER_ADMIN')"

    properties:
      name:
        filters:
          - type: SearchFilter
            strategy: partial
          - type: OrderFilter

      iso2:
        filters:
          - type: SearchFilter
            strategy: exact
          - type: OrderFilter

      iso3:
        filters:
          - type: SearchFilter
            strategy: exact
          - type: OrderFilter

      numericCode:
        filters:
          - type: SearchFilter
            strategy: exact

      currencyCode:
        filters:
          - type: SearchFilter
            strategy: exact
          - type: OrderFilter

      phoneCode:
        filters:
          - type: SearchFilter
            strategy: partial

      continent:
        filters:
          - type: SearchFilter
            strategy: exact
          - type: OrderFilter

      region:
        filters:
          - type: SearchFilter
            strategy: exact
          - type: OrderFilter

      active:
        filters:
          - type: BooleanFilter
          - type: OrderFilter

      euMember:
        filters:
          - type: BooleanFilter

      schengenMember:
        filters:
          - type: BooleanFilter

      availableForShipping:
        filters:
          - type: BooleanFilter

      availableForBilling:
        filters:
          - type: BooleanFilter
```

---

## 11. Data Population Reference

### Sample Data Structure

```json
{
  "name": "United States",
  "iso2": "US",
  "iso3": "USA",
  "numericCode": "840",
  "currencyCode": "USD",
  "currencySymbol": "$",
  "phoneCode": "+1",
  "capital": "Washington, D.C.",
  "continent": "North America",
  "region": "Americas",
  "subregion": "Northern America",
  "nativeName": "United States",
  "officialName": "United States of America",
  "nationalityName": "American",
  "latitude": 38.9072,
  "longitude": -77.0369,
  "timezones": [
    "America/New_York",
    "America/Chicago",
    "America/Denver",
    "America/Los_Angeles",
    "America/Anchorage",
    "Pacific/Honolulu"
  ],
  "languages": ["en"],
  "tld": ".us",
  "active": true,
  "euMember": false,
  "schengenMember": false,
  "oecdMember": true,
  "availableForShipping": true,
  "availableForBilling": true,
  "dataResidencyRequired": false,
  "postalCodeFormat": "^\\d{5}(-\\d{4})?$",
  "postalCodeRequired": true,
  "addressFormat": "{street}\n{city}, {region} {postalCode}",
  "taxIdRequired": false,
  "population": 331900000,
  "area": 9833520.00,
  "unMemberSince": 1945,
  "flagEmoji": "🇺🇸",
  "flagSvgUrl": "https://flagcdn.com/us.svg"
}
```

### Data Sources

1. **ISO Codes**: https://www.iso.org/iso-3166-country-codes.html
2. **Country Data**: https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes
3. **Currency Codes**: https://www.iso.org/iso-4217-currency-codes.html
4. **Flag Images**: https://flagcdn.com/
5. **Geographic Data**: https://www.geonames.org/

---

## 12. Implementation Checklist

### Phase 1: Critical Properties (Day 1)
- [ ] Add iso2 property (string, 2, unique, indexed, not null)
- [ ] Add iso3 property (string, 3, unique, indexed, not null)
- [ ] Add numericCode property (string, 3, unique, indexed, not null)
- [ ] Add currencyCode property (string, 3, indexed, not null)
- [ ] Rename dialingCode to phoneCode (string, 10, not null)
- [ ] Add active property (boolean, default true, not null) - **CORRECT CONVENTION**
- [ ] Add continent property (string, 50, indexed, not null)
- [ ] Configure validation rules for all properties
- [ ] Configure API filters for all properties
- [ ] Run genmax:generate Country
- [ ] Create and run migration

### Phase 2: High Priority Properties (Day 2)
- [ ] Add capital property
- [ ] Add currencySymbol property
- [ ] Add euMember property (boolean) - **CORRECT CONVENTION**
- [ ] Add region property
- [ ] Add nativeName property
- [ ] Add officialName property
- [ ] Update API configuration
- [ ] Run genmax:generate Country
- [ ] Create and run migration

### Phase 3: Medium Priority Properties (Day 3-4)
- [ ] Add all geographic properties (subregion, latitude, longitude, timezones)
- [ ] Add localization properties (languages, tld, nationalityName)
- [ ] Add operational flags (availableForShipping, availableForBilling)
- [ ] Add membership properties (schengenMember, oecdMember) - **CORRECT CONVENTION**
- [ ] Add compliance properties (dataResidencyRequired, taxIdRequired) - **CORRECT CONVENTION**
- [ ] Add address properties (postalCodeFormat, postalCodeRequired, addressFormat) - **CORRECT CONVENTION**
- [ ] Run genmax:generate Country
- [ ] Create and run migration

### Phase 4: Low Priority Properties (Day 5)
- [ ] Add statistical properties (population, area, unMemberSince)
- [ ] Add flag properties (flagEmoji, flagSvgUrl)
- [ ] Run genmax:generate Country
- [ ] Create and run migration

### Phase 5: Data Population (Day 6-7)
- [ ] Import ISO 3166-1 data for all countries
- [ ] Import ISO 4217 currency data
- [ ] Import geographic data (coordinates, timezones)
- [ ] Import flag data
- [ ] Validate data integrity
- [ ] Test API endpoints

### Phase 6: Testing & Documentation (Day 8)
- [ ] Test all API filters
- [ ] Test validation rules
- [ ] Verify unique constraints
- [ ] Performance test with indexes
- [ ] Document API endpoints
- [ ] Create fixture data for development

---

## 13. Testing Requirements

### Unit Tests Required

1. **Validation Tests**
   - ISO2 format validation (2 uppercase letters)
   - ISO3 format validation (3 uppercase letters)
   - Numeric code validation (3 digits)
   - Currency code validation (3 uppercase letters)
   - Phone code validation (+ followed by 1-4 digits)

2. **Uniqueness Tests**
   - Cannot create duplicate ISO2 codes
   - Cannot create duplicate ISO3 codes
   - Cannot create duplicate numeric codes
   - Cannot create duplicate country names

3. **API Filter Tests**
   - Search by name (partial match)
   - Filter by ISO2 (exact match)
   - Filter by continent
   - Filter by active status
   - Filter by EU membership
   - Order by name

### Integration Tests Required

1. **API Endpoint Tests**
   - GET /api/countries
   - GET /api/countries/{id}
   - POST /api/countries (admin only)
   - PUT /api/countries/{id} (admin only)
   - DELETE /api/countries/{id} (super admin only)

2. **Security Tests**
   - Regular users can read
   - Only admins can create/update
   - Only super admins can delete

---

## 14. Performance Considerations

### Index Strategy

```sql
-- Primary lookups (most frequent)
CREATE INDEX idx_country_iso2 ON country(iso2);
CREATE INDEX idx_country_iso3 ON country(iso3);
CREATE INDEX idx_country_name ON country(name);

-- Secondary lookups
CREATE INDEX idx_country_currency_code ON country(currency_code);
CREATE INDEX idx_country_continent ON country(continent);

-- Filter indexes
CREATE INDEX idx_country_active ON country(active);
CREATE INDEX idx_country_eu_member ON country(eu_member);

-- Composite indexes for common queries
CREATE INDEX idx_country_active_continent ON country(active, continent);
CREATE INDEX idx_country_active_currency ON country(active, currency_code);
```

### Query Optimization

Expected query patterns:
1. Lookup by ISO2: `WHERE iso2 = 'US'` (most common)
2. List active countries: `WHERE active = true ORDER BY name`
3. Filter by continent: `WHERE continent = 'Europe' AND active = true`
4. Currency grouping: `GROUP BY currency_code`

All critical query patterns are covered by recommended indexes.

---

## 15. Migration Risk Assessment

### Breaking Changes
1. **Renaming dialingCode to phoneCode** - May break existing code
   - Search codebase for references to dialingCode
   - Update all references before migration
   - Consider adding deprecation notice

### Data Migration Required
1. **Populate new required fields** - Cannot be NULL
   - iso2, iso3, numericCode must be populated for existing records
   - If no existing records, safe to add constraints
   - If records exist, two-step migration:
     - Step 1: Add columns as nullable
     - Step 2: Populate data
     - Step 3: Add NOT NULL constraints

### Rollback Plan
1. Keep old columns during transition period
2. Run both old and new columns in parallel
3. Validate data consistency
4. Drop old columns after verification

---

## 16. Boolean Naming Convention Compliance

### CRITICAL PROJECT REQUIREMENT

**Project Convention**: Boolean properties MUST NOT use "is" prefix

**Examples**:
- ✅ CORRECT: `active`, `euMember`, `schengenMember`, `availableForShipping`
- ❌ WRONG: `isActive`, `isEuMember`, `isSchengenMember`, `isAvailableForShipping`

**Current Compliance**: PASS (no existing boolean properties)

**Required Boolean Properties**:
1. `active` (NOT isActive)
2. `euMember` (NOT isEuMember)
3. `schengenMember` (NOT isSchengenMember)
4. `oecdMember` (NOT isOecdMember)
5. `availableForShipping` (NOT isAvailableForShipping)
6. `availableForBilling` (NOT isAvailableForBilling)
7. `dataResidencyRequired` (NOT isDataResidencyRequired)
8. `postalCodeRequired` (NOT isPostalCodeRequired)
9. `taxIdRequired` (NOT isTaxIdRequired)

**All boolean properties must follow this convention for consistency across the entire project.**

---

## 17. Final Recommendations

### Immediate Actions (Priority 1)

1. **Add Critical ISO Properties**
   - Implement iso2, iso3, numericCode immediately
   - These are non-negotiable for international operations
   - Cannot integrate with payment gateways without these

2. **Fix Naming Convention**
   - Rename dialingCode to phoneCode
   - Ensure all boolean properties follow convention (no "is" prefix)
   - Add length constraint to phoneCode (+1 to +9999)

3. **Add Active Flag**
   - Critical for managing country visibility
   - MUST use `active` NOT `isActive`

4. **Configure API Filters**
   - Enable search filters on name, iso2, iso3
   - Enable boolean filters on active, euMember
   - Essential for API usability

### Short-term Actions (Priority 2)

5. **Add Geographic Properties**
   - Capital, continent, region for segmentation
   - Latitude/longitude for mapping features

6. **Add Currency Properties**
   - currencyCode for financial transactions
   - currencySymbol for display formatting

7. **Add EU/Schengen Flags**
   - euMember for compliance reporting
   - schengenMember for travel features
   - MUST use correct boolean convention

### Long-term Actions (Priority 3)

8. **Complete Localization Support**
   - Native names, official names
   - Language codes, TLD

9. **Add Statistical Data**
   - Population, area, UN membership
   - Useful for reporting and analytics

10. **Implement Address Validation**
    - Postal code formats
    - Address format templates
    - Essential for e-commerce

---

## 18. Success Criteria

The Country entity will be considered complete when:

1. ✅ All CRITICAL properties implemented (7 properties)
2. ✅ All HIGH priority properties implemented (6 properties)
3. ✅ All boolean properties follow naming convention (no "is" prefix)
4. ✅ ISO standards compliance achieved (100%)
5. ✅ API filters configured for all searchable fields
6. ✅ Validation rules configured for all properties
7. ✅ Unique constraints on ISO codes
8. ✅ Indexes on all lookup fields
9. ✅ Full API documentation with examples
10. ✅ Unit and integration tests passing

**Current Status**: 1/10 criteria met (10%)

**Target Status**: 10/10 criteria met (100%)

---

## 19. Conclusion

The Country entity is currently in a **critically incomplete state** with only 3 properties defined, missing essential ISO standard fields, and lacking proper API configuration. The entity cannot support international operations, e-commerce integration, or compliance requirements in its current form.

**Estimated Effort**:
- Critical fixes: 8-16 hours
- High priority additions: 16-24 hours
- Medium priority additions: 16-24 hours
- Low priority additions: 8-16 hours
- **Total**: 48-80 hours (6-10 days)

**Risk Level**: HIGH - Production deployment without these properties will cause:
- Payment integration failures (missing currency codes)
- Address validation failures (missing postal formats)
- Data compliance violations (missing residency flags)
- Poor user experience (missing localization)
- API usability issues (missing filters)

**Recommendation**: Implement at minimum all CRITICAL and HIGH priority properties before any production deployment. Follow boolean naming conventions strictly. Ensure all API Platform fields are properly configured.

---

**Report Generated**: 2025-10-19
**Database**: PostgreSQL 18
**Analysis Tool**: Genmax + Manual Review
**Standards Referenced**: ISO 3166-1, ISO 4217, ISO 639, UN M49
**Project Conventions**: Boolean naming (active, euMember NOT isActive, isEuMember)

**Next Steps**: Review this report with development team and create implementation plan with priority assignments.
