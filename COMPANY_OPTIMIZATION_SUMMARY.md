# Company Entity Optimization Summary

**Date**: 2025-10-18
**Entity**: Company
**Database**: PostgreSQL 18 with UUIDv7
**Project**: Luminai CRM

---

## Executive Summary

Comprehensive optimization of the **Company** entity based on 2025 CRM industry standards from:
- ✅ Salesforce Account Object
- ✅ HubSpot Company Properties
- ✅ Modern B2B CRM Best Practices
- ✅ GDPR Compliance Standards
- ✅ PostgreSQL Performance Optimization

### Key Metrics

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| **Total Properties** | 26 | 51 | +25 (+96%) |
| **Indexed Properties** | 0 | 17 | +17 |
| **Validated Properties** | 0 | 16 | +16 |
| **Filterable Properties** | 0 | 26 | +26 |
| **Form Configs** | 0 | 9 | +9 |

---

## Optimizations Applied (16 Properties)

### 1. Critical Fixes

#### Field Renames (Better Naming Convention)
- ❌ `document` → ✅ `taxId` (clearer business purpose)
- ❌ `address` → ✅ `billingAddress` (separates billing/shipping)
- ❌ `geo` → ✅ `coordinates` (descriptive naming)
- ❌ `celPhone` → ✅ `mobilePhone` (fixed typo)
- ❌ `businesPhone` → ✅ `phone` (fixed typo, Salesforce standard)
- ❌ `contactName` → ✅ `primaryContactName` (clarifies relationship)

#### Validation & Constraints
- **name**: Added `NotBlank` validation, length 255, indexed
- **email**: Added `Email` validation, removed unique constraint (companies can share emails), length 180
- **website**: Added `Url` validation, length 255, filterable
- **companySize**: Added `GreaterThanOrEqual(0)` validation

#### Performance Optimizations
- **accountManager** (FK): Added index + filterable
- **city** (FK): Added index + filterable
- **postalCode**: Added length constraint (20), filterable
- **notes**: Removed sortable flag (text fields shouldn't be sorted)

#### Data Consistency
- **industry**: Converted to ChoiceType with standard values, indexed, filterable
- **status**: Converted to ChoiceType (Active/Inactive/Prospect/Customer/Partner), indexed, filterable

---

## New Properties Added (25 Properties)

### Business Information
| Property | Type | Purpose | Salesforce/HubSpot Standard |
|----------|------|---------|----------------------------|
| `legalName` | string | Official legal entity name | ✅ Salesforce |
| `companyType` | choice | Business type (Prospect/Customer/Partner) | ✅ Salesforce Type |
| `ownership` | choice | Ownership structure (Public/Private/Gov) | ✅ Salesforce |
| `description` | text | Detailed company description | ✅ Salesforce |

### Financial Fields
| Property | Type | Purpose | B2B Critical |
|----------|------|---------|--------------|
| `annualRevenue` | decimal(15,2) | Annual revenue for segmentation | ✅ |
| `currency` | choice | ISO 4217 currency code (USD/EUR/GBP) | ✅ |
| `creditLimit` | decimal(15,2) | Maximum credit extended | ✅ |
| `paymentTerms` | choice | Payment terms (Net 30/60/90) | ✅ |
| `fiscalYearEnd` | choice | Fiscal year end month | ✅ |

### Shipping Address (Complete Set)
| Property | Type | Purpose |
|----------|------|---------|
| `shippingAddress` | string | Shipping street address |
| `shippingCity` | ManyToOne→City | Shipping city |
| `shippingPostalCode` | string | Shipping postal code |
| `shippingCountry` | string | Shipping country |

### Address Completion
| Property | Type | Purpose |
|----------|------|---------|
| `country` | string | Billing country (was missing) |

### Industry Classification
| Property | Type | Purpose | Standard |
|----------|------|---------|----------|
| `sicCode` | string | Standard Industrial Classification | ✅ US Gov |
| `naicsCode` | string | North American Industry Classification | ✅ US Census |
| `tickerSymbol` | string | Stock ticker for public companies | ✅ Salesforce |

### Sales & Marketing
| Property | Type | Purpose | Standard |
|----------|------|---------|----------|
| `accountSource` | choice | Lead source (Web/Phone/Partner/etc) | ✅ Salesforce |
| `rating` | choice | Account quality (Hot/Warm/Cold) | ✅ Salesforce |
| `customerSince` | date | Customer start date for retention | ✅ HubSpot |
| `linkedInUrl` | string | LinkedIn company page | ✅ HubSpot |

### Corporate Hierarchy
| Property | Type | Purpose | Standard |
|----------|------|---------|----------|
| `parentCompany` | ManyToOne→Company | Self-reference for enterprise hierarchies | ✅ Salesforce Parent Account |

### Compliance & Privacy
| Property | Type | Purpose | Standard |
|----------|------|---------|----------|
| `doNotContact` | boolean | Marketing opt-out flag | ✅ GDPR |
| `gdprConsent` | boolean | GDPR consent tracking | ✅ GDPR |

### Legacy Communication
| Property | Type | Purpose |
|----------|------|---------|
| `fax` | string | Fax number (still used in manufacturing/healthcare) |

---

## Index Strategy

### Indexes Added (8 Primary Indexes)

| Index | Field(s) | Reasoning | Performance Impact |
|-------|----------|-----------|-------------------|
| `idx_company_name` | name | Primary identifier, frequent searches | ⚡ High |
| `idx_company_organization_id` | organization | Multi-tenant filtering | ⚡⚡⚡ Critical |
| `idx_company_account_manager_id` | accountManager | Territory reports | ⚡⚡ High |
| `idx_company_city_id` | city | Geographic queries | ⚡⚡ High |
| `idx_company_status` | status | Filter active accounts | ⚡⚡ High |
| `idx_company_industry` | industry | Segmentation reports | ⚡⚡ High |
| `idx_company_parent_company_id` | parentCompany | Hierarchy queries | ⚡ Medium |
| `idx_company_customer_since` | customerSince | Cohort analysis | ⚡ Medium |

---

## Validation Rules

### 16 Properties with Validation

#### Required Fields
- **name**: `NotBlank` - "Company name is required"

#### Format Validation
- **email**: `Email` - "Invalid email address"
- **website**: `Url` - "Invalid URL format"
- **linkedInUrl**: `Url` - Valid URL required

#### Range Validation
- **companySize**: `GreaterThanOrEqual(0)` - "Number of employees must be positive"
- **annualRevenue**: `GreaterThanOrEqual(0)` - Non-negative values only
- **creditLimit**: `GreaterThanOrEqual(0)` - Non-negative values only

#### Help Text (User Guidance)
- **taxId**: "Tax identification number (EIN, VAT, etc.)"
- **primaryContactName**: "Quick reference - full name maintained in Contact entity"
- **annualRevenue**: "Annual revenue in company currency"
- **currency**: "ISO 4217 currency code"
- **creditLimit**: "Maximum credit limit in company currency"
- **parentCompany**: "Parent company in corporate hierarchy"
- **description**: "Detailed company description"
- **country**: "Country for billing address"
- **shippingCountry**: "Country for shipping address"

---

## Form Configuration

### 9 Properties with Choice Types

#### Industry (9 Options)
```
Technology, Healthcare, Finance, Manufacturing,
Retail, Education, Real Estate, Professional Services, Other
```

#### Status (6 Options)
```
1: Active, 2: Inactive, 3: Prospect,
4: Customer, 5: Former Customer, 6: Partner
```

#### Currency (6 Options)
```
USD, EUR, GBP, JPY, AUD, CAD
```

#### Company Type (7 Options)
```
Prospect, Customer, Partner, Reseller,
Vendor, Competitor, Other
```

#### Account Source (7 Options)
```
Web, Phone Inquiry, Partner Referral, Trade Show,
Cold Call, Employee Referral, Other
```

#### Rating (3 Options)
```
Hot, Warm, Cold
```

#### Payment Terms (6 Options)
```
Net 15, Net 30, Net 60, Net 90,
Due on Receipt, Prepaid
```

#### Fiscal Year End (12 Options)
```
January (01) through December (12)
```

#### Ownership (5 Options)
```
Public, Private, Government, Non-Profit, Other
```

---

## API Security Recommendations

### Sensitive Fields - Restrict Access

| Field | Recommendation | Reasoning |
|-------|----------------|-----------|
| `taxId` | Exclude from public API or require elevated permissions | Tax ID is sensitive business data |
| `creditLimit` | Finance roles only | Financial information needs strict access control |
| `annualRevenue` | Consider masking/rounding in public API | Revenue may be confidential for private companies |
| `notes` | Filter based on user role | May contain sensitive business context |

### Implementation
```php
// In API Platform resource configuration
#[ApiResource(
    normalizationContext: ['groups' => ['company:read']],
    denormalizationContext: ['groups' => ['company:write']],
    security: "is_granted('ROLE_USER')"
)]
class Company {
    #[Groups(['company:read:admin'])] // Admin only
    private ?string $taxId = null;

    #[Groups(['company:read:finance'])] // Finance only
    private ?string $creditLimit = null;
}
```

---

## Best Practices Applied

### ✅ 15 Modern CRM Best Practices

1. **Foreign Key Indexing**: All FKs indexed for join performance
2. **Validation at Database Layer**: NotBlank, Email, Url, Range validation
3. **Standardized Field Names**: Aligned with Salesforce/HubSpot standards
4. **Choice Types for Enums**: Data consistency via predefined options
5. **Separate Billing/Shipping**: B2B requirement for different addresses
6. **Firmographic Data**: Revenue, employees, industry for segmentation
7. **Corporate Hierarchy**: Parent company for enterprise accounts
8. **Financial Management**: Payment terms, credit limit, currency
9. **Compliance & Privacy**: GDPR consent, do not contact flags
10. **Industry Classification**: SIC/NAICS codes for standard categorization
11. **Account Attribution**: Source tracking for ROI analysis
12. **Social Selling**: LinkedIn integration for modern sales
13. **Searchable/Filterable**: Optimized for UI performance
14. **Length Constraints**: Prevent data overflow, ensure DB performance
15. **Help Text Documentation**: Guide users, ensure data quality

---

## Alignment with Industry Standards

### Salesforce Account Object ✅
- ✅ Parent Account (parentCompany)
- ✅ Account Owner (accountManager)
- ✅ Rating (Hot/Warm/Cold)
- ✅ Type (companyType)
- ✅ Industry (industry)
- ✅ Annual Revenue (annualRevenue)
- ✅ Number of Employees (companySize)
- ✅ Account Source (accountSource)
- ✅ SIC Code (sicCode)
- ✅ Ticker Symbol (tickerSymbol)
- ✅ Ownership (ownership)
- ✅ Description (description)
- ✅ Billing/Shipping Address separation

### HubSpot Company Properties ✅
- ✅ Annual Revenue
- ✅ Number of Employees
- ✅ Industry
- ✅ LinkedIn Company Page (linkedInUrl)
- ✅ Customer Since Date (customerSince)
- ✅ Currency
- ✅ Company Type
- ✅ Billing/Shipping Address

### GDPR Compliance ✅
- ✅ GDPR Consent tracking (gdprConsent)
- ✅ Do Not Contact flag (doNotContact)
- ✅ Data protection fields

### B2B Financial Best Practices ✅
- ✅ Payment Terms (Net 30/60/90)
- ✅ Credit Limit tracking
- ✅ Currency support (multi-currency)
- ✅ Fiscal Year End for sales timing
- ✅ Tax ID for invoicing

---

## Implementation Steps

### 1. Execute SQL Script
```bash
# Connect to database
docker-compose exec -T database psql -U luminai_user -d luminai_db < company_optimization.sql
```

### 2. Verify Changes
```sql
-- Should return 51
SELECT COUNT(*) FROM generator_property
WHERE entity_id = '0199cadd-62b3-768e-b8ab-7d84650ebd47';
```

### 3. Regenerate Entity
```bash
# Use your generator system to create the PHP class
php bin/console app:generate:entity Company
```

### 4. Create Migration
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 5. Update Forms/Templates
- Update any existing forms that reference old field names
- Add new fields to company forms as needed
- Update templates to display new fields

### 6. Update API Configuration
- Configure security groups for sensitive fields
- Update normalization contexts
- Add filters for new filterable fields

### 7. Documentation
- Update API documentation
- Create user guides for new fields
- Document business rules and validation

---

## Migration Considerations

### ⚠️ Breaking Changes

These field renames will require code updates:

| Old Name | New Name | Migration Required |
|----------|----------|-------------------|
| `document` | `taxId` | ✅ Update all references |
| `address` | `billingAddress` | ✅ Update all references |
| `geo` | `coordinates` | ✅ Update all references |
| `celPhone` | `mobilePhone` | ✅ Update all references |
| `businesPhone` | `phone` | ✅ Update all references |
| `contactName` | `primaryContactName` | ✅ Update all references |

### Migration Script Template
```php
// migrations/VersionXXXXXXXXXXXXXX.php
public function up(Schema $schema): void
{
    // Rename columns in actual database
    $this->addSql('ALTER TABLE company RENAME COLUMN document TO tax_id');
    $this->addSql('ALTER TABLE company RENAME COLUMN address TO billing_address');
    $this->addSql('ALTER TABLE company RENAME COLUMN geo TO coordinates');
    $this->addSql('ALTER TABLE company RENAME COLUMN cel_phone TO mobile_phone');
    $this->addSql('ALTER TABLE company RENAME COLUMN busines_phone TO phone');
    $this->addSql('ALTER TABLE company RENAME COLUMN contact_name TO primary_contact_name');

    // Add new columns
    $this->addSql('ALTER TABLE company ADD legal_name VARCHAR(255) DEFAULT NULL');
    $this->addSql('ALTER TABLE company ADD shipping_address VARCHAR(255) DEFAULT NULL');
    // ... etc for all new fields
}
```

---

## Testing Checklist

### ✅ Unit Tests
- [ ] Test validation rules (NotBlank, Email, Url, Range)
- [ ] Test choice type constraints
- [ ] Test relationship mappings (parentCompany, accountManager, city)
- [ ] Test default values

### ✅ Integration Tests
- [ ] Test CRUD operations with new fields
- [ ] Test filtering by new filterable fields
- [ ] Test sorting by new sortable fields
- [ ] Test search across new searchable fields

### ✅ Performance Tests
- [ ] Verify indexes are created
- [ ] Test query performance with indexes
- [ ] Test large dataset performance
- [ ] Monitor query execution plans

### ✅ API Tests
- [ ] Test API field exposure
- [ ] Test security groups for sensitive fields
- [ ] Test validation error responses
- [ ] Test filter/sort operations

### ✅ UI Tests
- [ ] Test form rendering with choice types
- [ ] Test validation error display
- [ ] Test required field indicators
- [ ] Test help text display

---

## Future Enhancements

### Suggested Additional Features

1. **Account Health Scoring**
   - Computed property based on revenue, engagement, status
   - Automated risk/opportunity flagging

2. **Data Enrichment Integration**
   - Auto-populate from D&B, Clearbit, ZoomInfo
   - Revenue, employee count, industry validation

3. **Multi-Currency Conversion**
   - Automatic currency conversion tables
   - Historical exchange rate tracking

4. **Audit Trail**
   - Track changes to sensitive fields (creditLimit, status)
   - User attribution for compliance

5. **Territory Management**
   - Geographic territory assignment
   - Auto-assign accountManager by region

6. **Account Scoring**
   - Lead scoring based on firmographics
   - Predictive analytics for conversion

---

## Files Generated

1. **company_optimization_report.json** - Detailed JSON analysis
2. **company_optimization.sql** - Executable SQL script
3. **COMPANY_OPTIMIZATION_SUMMARY.md** - This document

---

## Support & Resources

### Documentation
- [Salesforce Account Object](https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/object_reference/sforce_api_objects_account.htm)
- [HubSpot Company Properties](https://knowledge.hubspot.com/properties/hubspot-crm-default-company-properties)
- [CRM Data Management Best Practices](https://airbyte.com/data-engineering-resources/crm-data-management-best-practices)

### Project Files
- Main reference: `/home/user/inf/CLAUDE.md`
- Database guide: `/home/user/inf/docs/DATABASE.md`
- Development workflow: `/home/user/inf/docs/DEVELOPMENT_WORKFLOW.md`

---

**Generated**: 2025-10-18
**Author**: Claude Code Analysis
**Version**: 1.0
**Status**: Ready for Implementation ✅
