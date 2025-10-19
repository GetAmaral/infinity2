# GROUP 4 - BATCH ENTITY OPTIMIZATION

## ENTITIES (10 Total)

### Product Management Group
1. **Brand** - Product and company brands
2. **ProductCategory** - Product categorization (hierarchical, self-referencing)
3. **ProductLine** - Product line and series organization
4. **ProductBatch** - Manufacturing batch and lot tracking

### Financial Group
5. **TaxCategory** - Tax rate categories and classifications
6. **BillingFrequency** - Billing and payment frequency options

### CRM Group
7. **Competitor** - Competitive landscape tracking
8. **AgentType** - Sales and service agent classifications
9. **CommunicationMethod** - Communication channel types
10. **SocialMediaType** - Social media platform types

---

## EXECUTION

```bash
# 1. Execute SQL (creates all 10 entities + properties)
docker-compose exec -T database psql -U luminai -d luminai < GROUP4_ENTITIES_BATCH.sql

# 2. Verify entities
docker-compose exec -T database psql -U luminai -d luminai -c "
SELECT entity_name, plural_label, menu_group, menu_order
FROM generator_entity
WHERE entity_name IN (
    'Brand', 'ProductCategory', 'ProductLine', 'ProductBatch',
    'TaxCategory', 'BillingFrequency', 'Competitor',
    'AgentType', 'CommunicationMethod', 'SocialMediaType'
)
ORDER BY menu_group, menu_order;"
```

---

## ENTITY DETAILS

### 1. Brand
- **Table**: `brand_table`
- **Menu**: Product Management (100)
- **Color**: #8B4513 (Brown)
- **Icon**: bi-bookmark-star
- **Properties**: 6
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - description (text)
  - logoUrl (string, 500)
  - website (string, 255)
  - active (boolean, default true)

### 2. ProductCategory
- **Table**: `product_category_table`
- **Menu**: Product Management (110)
- **Color**: #28A745 (Green)
- **Icon**: bi-grid
- **Properties**: 7
  - name (string, 100, indexed, searchable)
  - code (string, 50, unique, indexed)
  - description (text)
  - parent (ManyToOne -> ProductCategory, self-referencing)
  - children (OneToMany -> ProductCategory)
  - displayOrder (integer, default 0)
  - active (boolean, default true)

### 3. ProductLine
- **Table**: `product_line_table`
- **Menu**: Product Management (120)
- **Color**: #007BFF (Blue)
- **Icon**: bi-diagram-3
- **Properties**: 6
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - description (text)
  - launchDate (date)
  - endOfLifeDate (date)
  - active (boolean, default true)

### 4. ProductBatch
- **Table**: `product_batch_table`
- **Menu**: Product Management (130)
- **Color**: #6C757D (Gray)
- **Icon**: bi-layers
- **Properties**: 6
  - batchNumber (string, 100, unique, indexed, searchable)
  - lotNumber (string, 100, indexed, searchable)
  - manufactureDate (date, indexed)
  - expirationDate (date, indexed)
  - quantity (integer)
  - notes (text)

### 5. TaxCategory
- **Table**: `tax_category_table`
- **Menu**: Financial (100)
- **Color**: #DC3545 (Red)
- **Icon**: bi-calculator
- **Properties**: 6
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - rate (decimal 5,2, required, 0-100%)
  - description (text)
  - countryCode (string, 2, ISO 3166-1 alpha-2)
  - active (boolean, default true)

### 6. BillingFrequency
- **Table**: `billing_frequency_table`
- **Menu**: Financial (110)
- **Color**: #FFC107 (Yellow)
- **Icon**: bi-calendar-event
- **Properties**: 5
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - intervalDays (integer)
  - description (text)
  - active (boolean, default true)

### 7. Competitor
- **Table**: `competitor_table`
- **Menu**: CRM (200)
- **Color**: #E91E63 (Pink)
- **Icon**: bi-trophy
- **Properties**: 7
  - name (string, 255, unique, indexed, searchable)
  - website (string, 255)
  - description (text, max 2000)
  - strengths (text, max 2000)
  - weaknesses (text, max 2000)
  - marketShare (decimal 5,2, 0-100%)
  - active (boolean, default true)

### 8. AgentType
- **Table**: `agent_type_table`
- **Menu**: CRM (210)
- **Color**: #9C27B0 (Purple)
- **Icon**: bi-person-badge
- **Properties**: 5
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - description (text)
  - commissionRate (decimal 5,2, 0-100%)
  - active (boolean, default true)

### 9. CommunicationMethod
- **Table**: `communication_method_table`
- **Menu**: CRM (220)
- **Color**: #00BCD4 (Cyan)
- **Icon**: bi-chat-dots
- **Properties**: 5
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - description (text)
  - icon (string, 50, Bootstrap icon pattern)
  - active (boolean, default true)

### 10. SocialMediaType
- **Table**: `social_media_type_table`
- **Menu**: CRM (230)
- **Color**: #3F51B5 (Indigo)
- **Icon**: bi-share
- **Properties**: 6
  - name (string, 100, unique, indexed, searchable)
  - code (string, 50, unique, indexed)
  - icon (string, 50, Bootstrap icon pattern)
  - urlPattern (string, 500, URL template)
  - description (text)
  - active (boolean, default true)

---

## FEATURES

### All Entities Include
- ✅ Organization multi-tenancy
- ✅ API Platform enabled (full CRUD)
- ✅ Security voters (VIEW, EDIT, DELETE, CREATE)
- ✅ Fixtures enabled
- ✅ Test generation enabled
- ✅ UUIDv7 primary keys
- ✅ Timestamps (createdAt, updatedAt)

### Common Patterns
- ✅ name field (unique, indexed, searchable)
- ✅ code field (unique, indexed, optional)
- ✅ description field (text, optional)
- ✅ active boolean (default true, filterable)

### Special Features
- **ProductCategory**: Self-referencing hierarchy (parent/children)
- **ProductBatch**: Date-indexed batch tracking
- **TaxCategory**: Decimal rate with validation (0-100%)
- **Competitor**: SWOT analysis fields (strengths/weaknesses)
- **SocialMediaType**: URL pattern template support

---

## PROPERTY COUNT BY ENTITY

| Entity | Scalar Properties | Relationships | Total |
|--------|------------------|---------------|-------|
| Brand | 6 | 0 | 6 |
| ProductCategory | 5 | 2 | 7 |
| ProductLine | 6 | 0 | 6 |
| ProductBatch | 6 | 0 | 6 |
| TaxCategory | 6 | 0 | 6 |
| BillingFrequency | 5 | 0 | 5 |
| Competitor | 7 | 0 | 7 |
| AgentType | 5 | 0 | 5 |
| CommunicationMethod | 5 | 0 | 5 |
| SocialMediaType | 6 | 0 | 6 |
| **TOTAL** | **57** | **2** | **59** |

---

## MENU ORGANIZATION

### Product Management (4 entities)
- Brand (100)
- ProductCategory (110)
- ProductLine (120)
- ProductBatch (130)

### Financial (2 entities)
- TaxCategory (100)
- BillingFrequency (110)

### CRM (4 entities)
- Competitor (200)
- AgentType (210)
- CommunicationMethod (220)
- SocialMediaType (230)
