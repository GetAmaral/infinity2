# Brand Entity - Complete Property Reference

**Generated:** 2025-10-19
**Status:** Ready for Generation
**Total Properties:** 18

---

## Property Groups Overview

### 1. Basic Information (5 properties)

| # | Property | Type | Required | API | Description |
|---|----------|------|----------|-----|-------------|
| 1 | `name` | string | ✅ Yes | ✅ | The brand name |
| 2 | `description` | text | No | ✅ | Detailed description of the brand and its positioning |
| 3 | `tagline` | string | No | ✅ | Brand tagline or slogan |
| 4 | `logoUrl` | string | No | ✅ | URL to the brand logo image |
| 5 | `primaryColor` | string | No | ✅ | Primary brand color (hex code) |

**Example:**
```json
{
  "name": "Nike",
  "description": "Nike is a global leader in athletic footwear and apparel, known for innovation and performance.",
  "tagline": "Just Do It",
  "logoUrl": "https://example.com/logos/nike.png",
  "primaryColor": "#FF6B00"
}
```

---

### 2. Market Information (5 properties)

| # | Property | Type | Required | API | Description |
|---|----------|------|----------|-----|-------------|
| 6 | `industry` | string | No | ✅ | Industry or sector the brand operates in |
| 7 | `positioning` | text | No | ✅ | How the brand positions itself in the market |
| 8 | `targetMarket` | text | No | ✅ | Description of the brand's target market and demographics |
| 9 | `marketShare` | decimal(5,2) | No | ✅ | Market share percentage (0.00 to 100.00) |
| 10 | `brandValue` | decimal(15,2) | No | ✅ | Estimated brand value in USD |

**Example:**
```json
{
  "industry": "Sporting Goods",
  "positioning": "Premium performance athletic wear with innovative technology",
  "targetMarket": "Athletes and fitness enthusiasts aged 18-45",
  "marketShare": 15.75,
  "brandValue": 35000000000.00
}
```

**Competitive Analysis Features:**
- Track market share to monitor competitive position
- Monitor brand value trends over time
- Segment by industry for market analysis
- Compare positioning strategies across competitors

---

### 3. Company Information (4 properties)

| # | Property | Type | Required | API | Description |
|---|----------|------|----------|-----|-------------|
| 11 | `countryOfOrigin` | string | No | ✅ | Country where the brand originated |
| 12 | `foundedYear` | integer | No | ✅ | Year the brand was founded |
| 13 | `website` | string | No | ✅ | Official website URL of the brand |
| 14 | `active` | boolean | ✅ Yes | ✅ | Whether this brand is currently active in the market |

**Example:**
```json
{
  "countryOfOrigin": "United States",
  "foundedYear": 1964,
  "website": "https://www.nike.com",
  "active": true
}
```

**Lifecycle Management:**
- Use `active` flag to track brand status
- Filter active brands in reports
- Historical analysis with `foundedYear`
- Geographic analysis with `countryOfOrigin`

---

### 4. Relationships (4 properties)

| # | Property | Relationship | Target Entity | Description |
|---|----------|--------------|---------------|-------------|
| 20 | `organization` | ManyToOne | Organization | The organization that owns this brand record |
| 21 | `products` | OneToMany | Product | Products associated with this brand |
| 22 | `manufacturers` | ManyToMany | Company | Companies that manufacture this brand |
| 23 | `suppliers` | ManyToMany | Company | Companies that supply this brand |

**Example:**
```json
{
  "organization": "/api/organizations/0199cadd-63f3-79cf-8bf8-e250f0e400c8",
  "products": [
    {"@id": "/api/products/123"},
    {"@id": "/api/products/456"}
  ],
  "manufacturers": [
    {"@id": "/api/companies/123"}
  ],
  "suppliers": [
    {"@id": "/api/companies/789"}
  ]
}
```

**Supply Chain Visibility:**
- Track manufacturer relationships for sourcing analysis
- Monitor supplier diversity
- Link to product catalog for inventory management
- Multi-tenant isolation via organization

---

## Complete API Example

```json
{
  "@context": "/api/contexts/Brand",
  "@id": "/api/brands/0199cadd-63f3-79cf-8bf8-e250f0e400c8",
  "@type": "Brand",
  "id": "0199cadd-63f3-79cf-8bf8-e250f0e400c8",
  
  "name": "Nike",
  "description": "Nike is a global leader in athletic footwear and apparel, known for innovation and performance.",
  "tagline": "Just Do It",
  "logoUrl": "https://example.com/logos/nike.png",
  "primaryColor": "#FF6B00",
  
  "industry": "Sporting Goods",
  "positioning": "Premium performance athletic wear with innovative technology",
  "targetMarket": "Athletes and fitness enthusiasts aged 18-45",
  "marketShare": 15.75,
  "brandValue": 35000000000.00,
  
  "countryOfOrigin": "United States",
  "foundedYear": 1964,
  "website": "https://www.nike.com",
  "active": true,
  
  "organization": "/api/organizations/0199cadd-63f3-79cf-8bf8-e250f0e400c8",
  "products": [
    {"@id": "/api/products/0199...", "name": "Air Max 90"},
    {"@id": "/api/products/0199...", "name": "Air Force 1"}
  ],
  "manufacturers": [
    {"@id": "/api/companies/0199...", "name": "Nike Inc."}
  ],
  "suppliers": [
    {"@id": "/api/companies/0199...", "name": "Global Textile Supply"}
  ]
}
```

---

## Validation Rules

| Property | Rule | Constraint | Error Message |
|----------|------|------------|---------------|
| `name` | NotBlank | Required | Brand name is required |
| `website` | Url | Valid URL | Must be a valid URL |
| `logoUrl` | Url | Valid URL | Must be a valid URL |
| `marketShare` | Range | 0.00 - 100.00 | Market share must be between 0 and 100 |
| `foundedYear` | Range | Valid year | Must be a valid year |

---

## Database Schema

### Main Table: `brand_table`

```sql
CREATE TABLE brand_table (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
    
    -- Basic Information
    name VARCHAR(255) NOT NULL,
    description TEXT,
    tagline VARCHAR(255),
    logo_url VARCHAR(255),
    primary_color VARCHAR(7),
    
    -- Market Information
    industry VARCHAR(255),
    positioning TEXT,
    target_market TEXT,
    market_share NUMERIC(5,2),
    brand_value NUMERIC(15,2),
    
    -- Company Information
    country_of_origin VARCHAR(255),
    founded_year INTEGER,
    website VARCHAR(255),
    active BOOLEAN NOT NULL DEFAULT true,
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

### Join Tables

```sql
-- Brand <-> Company (Manufacturers)
CREATE TABLE brand_manufacturer (
    brand_id UUID NOT NULL REFERENCES brand_table(id) ON DELETE CASCADE,
    company_id UUID NOT NULL REFERENCES company(id) ON DELETE CASCADE,
    PRIMARY KEY (brand_id, company_id)
);

-- Brand <-> Company (Suppliers)
CREATE TABLE brand_supplier (
    brand_id UUID NOT NULL REFERENCES brand_table(id) ON DELETE CASCADE,
    company_id UUID NOT NULL REFERENCES company(id) ON DELETE CASCADE,
    PRIMARY KEY (brand_id, company_id)
);
```

---

## Recommended Indexes

```sql
-- Search and filter performance
CREATE INDEX idx_brand_name ON brand_table(name);
CREATE INDEX idx_brand_industry ON brand_table(industry);
CREATE INDEX idx_brand_active ON brand_table(active);
CREATE INDEX idx_brand_organization_id ON brand_table(organization_id);

-- Composite indexes for common queries
CREATE INDEX idx_brand_org_active ON brand_table(organization_id, active);
CREATE INDEX idx_brand_industry_active ON brand_table(industry, active);
CREATE INDEX idx_brand_org_industry ON brand_table(organization_id, industry);

-- Full-text search (PostgreSQL trigram)
CREATE INDEX idx_brand_name_trgm ON brand_table USING gin(name gin_trgm_ops);
CREATE INDEX idx_brand_description_trgm ON brand_table USING gin(description gin_trgm_ops);
```

---

## Common Queries

### 1. List Active Brands by Industry

```sql
SELECT id, name, industry, market_share, brand_value
FROM brand_table
WHERE organization_id = ? 
  AND active = true 
  AND industry = ?
ORDER BY market_share DESC;
```

### 2. Top Brands by Market Share

```sql
SELECT name, market_share, brand_value, industry
FROM brand_table
WHERE organization_id = ? AND active = true
ORDER BY market_share DESC
LIMIT 10;
```

### 3. Brands by Country

```sql
SELECT country_of_origin, COUNT(*) as brand_count, SUM(market_share) as total_share
FROM brand_table
WHERE organization_id = ? AND active = true
GROUP BY country_of_origin
ORDER BY brand_count DESC;
```

### 4. Brand Portfolio Value

```sql
SELECT 
    industry,
    COUNT(*) as brand_count,
    SUM(brand_value) as total_value,
    AVG(market_share) as avg_market_share
FROM brand_table
WHERE organization_id = ? AND active = true
GROUP BY industry
ORDER BY total_value DESC;
```

---

## API Endpoints

### Collection Operations

```http
GET /api/brands
  ?page=1
  &itemsPerPage=30
  &active=true
  &industry=Sporting Goods
  &order[marketShare]=desc
```

### Item Operations

```http
GET    /api/brands/{id}
POST   /api/brands
PUT    /api/brands/{id}
DELETE /api/brands/{id}
```

### Filters

- `active`: boolean
- `industry`: exact match
- `countryOfOrigin`: exact match
- `search`: name, description (full-text)
- `marketShare[gte]`: greater than or equal
- `marketShare[lte]`: less than or equal
- `brandValue[gte]`: greater than or equal
- `brandValue[lte]`: less than or equal

---

## Security

### Voter Permissions

| Action | Required Permission | Logic |
|--------|-------------------|-------|
| VIEW | Any authenticated user | Must belong to same organization |
| EDIT | ROLE_DATA_ADMIN | Must belong to same organization |
| DELETE | ROLE_DATA_ADMIN | Must belong to same organization |

### API Security

```php
security: "is_granted('ROLE_DATA_ADMIN')"
```

All API operations require ROLE_DATA_ADMIN. Organization filtering is automatic via Doctrine filter.

---

## Testing

### Unit Tests

```php
// tests/Entity/BrandTest.php
testNameIsRequired()
testWebsiteValidation()
testLogoUrlValidation()
testMarketShareRange()
testActiveDefaultsToTrue()
testTimestampUpdates()
```

### Functional Tests

```php
// tests/Controller/BrandControllerTest.php
testGetBrandsCollection()
testGetBrand()
testCreateBrand()
testUpdateBrand()
testDeleteBrand()
testMultiTenantIsolation()
testSecurityVoters()
```

---

## CRM Use Cases

### 1. Competitive Analysis
- Track competitor brands in your industry
- Monitor market share trends
- Compare brand values
- Analyze positioning strategies

### 2. Portfolio Management
- Manage multiple brand portfolios
- Track brand performance by industry
- Monitor active vs inactive brands
- Calculate total portfolio value

### 3. Supply Chain Tracking
- Link brands to manufacturers
- Track supplier relationships
- Analyze geographic distribution
- Monitor sourcing strategies

### 4. Market Research
- Analyze brand demographics (target market)
- Study positioning strategies
- Track historical data (founded year)
- Geographic market analysis

---

## Fixture Examples

```php
// src/DataFixtures/BrandFixtures.php
$brand1 = new Brand();
$brand1->setName('Nike');
$brand1->setDescription('Global leader in athletic footwear and apparel');
$brand1->setTagline('Just Do It');
$brand1->setIndustry('Sporting Goods');
$brand1->setMarketShare(15.75);
$brand1->setBrandValue(35000000000);
$brand1->setCountryOfOrigin('United States');
$brand1->setFoundedYear(1964);
$brand1->setActive(true);

$brand2 = new Brand();
$brand2->setName('Adidas');
$brand2->setDescription('Innovation and performance in sports');
$brand2->setTagline('Impossible is Nothing');
$brand2->setIndustry('Sporting Goods');
$brand2->setMarketShare(12.30);
$brand2->setBrandValue(14200000000);
$brand2->setCountryOfOrigin('Germany');
$brand2->setFoundedYear(1949);
$brand2->setActive(true);
```

---

**Status:** ✅ Ready for Generation
**API Documentation:** 100% Complete
**CRM Best Practices:** Applied
**Performance:** Optimized

For complete analysis, see: `/home/user/inf/brand_entity_analysis_report.md`
