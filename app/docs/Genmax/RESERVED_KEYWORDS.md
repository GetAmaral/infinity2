# Reserved Keywords Protection in Genmax

**Status:** ✅ **Active** (Implemented 2025-10-20)

## Overview

Genmax automatically protects against SQL and PostgreSQL reserved keywords at TWO levels:

1. **Table Names**: Automatically generates table names as `{entity_name}_table` (e.g., `user_table`, `order_table`)
2. **Column Names**: Detects reserved keywords in property names and adds `_prop` suffix (e.g., `default_prop`, `type_prop`)

## Problems Solved

### Problem 1: Reserved Entity Names

If you name an entity with a reserved keyword (e.g., `User`, `Order`, `Group`), Doctrine will generate SQL like:

```sql
CREATE TABLE user (  -- ERROR: "user" is a reserved keyword!
    id UUID PRIMARY KEY,
    ...
);
```

### Problem 2: Reserved Property Names

If you use a reserved keyword as a property name (e.g., `default`, `public`, `type`), Doctrine will generate SQL like:

```sql
CREATE TABLE calendar (
    id UUID PRIMARY KEY,
    default BOOLEAN,  -- ERROR: "default" is a reserved keyword!
    public BOOLEAN,   -- ERROR: "public" is a reserved keyword!
    ...
);
```

This causes SQL syntax errors during migration.

## Solutions

### Solution 1: Automatic Table Name Generation

**ALL** entities get `_table` suffix automatically:

```php
#[ORM\Table(name: 'user_table')]  // ✅ Safe!
class User extends UserGenerated
```

Generated SQL:
```sql
CREATE TABLE user_table (  -- ✅ Safe! No conflict
    id UUID PRIMARY KEY,
    ...
);
```

**Key Change:** Removed `table_name` property from `GeneratorEntity` - table names are now calculated automatically from entity names using snake_case + `_table` suffix.

### Solution 2: Automatic Column Name Protection

Genmax detects reserved keywords and adds `name: 'propertyName_prop'` to the `@ORM\Column` annotation:

```php
#[ORM\Column(name: 'default_prop', type: 'boolean')]
protected bool $default;

#[ORM\Column(name: 'public_prop', type: 'boolean')]
protected bool $public;
```

This generates safe SQL:

```sql
CREATE TABLE calendar (
    id UUID PRIMARY KEY,
    default_prop BOOLEAN,  -- ✅ Safe!
    public_prop BOOLEAN,   -- ✅ Safe!
    ...
);
```

## Reserved Keywords List

Genmax maintains a comprehensive list of **400+ reserved keywords** from:

- **PostgreSQL 18** reserved keywords
- **SQL:2023 ANSI/ISO** standard keywords
- **MySQL, Oracle, SQL Server** common reserved words
- **PHP** reserved words (to avoid conflicts in generated code)

### Examples of Protected Keywords

| Category | Keywords |
|----------|----------|
| **SQL Core** | SELECT, INSERT, UPDATE, DELETE, FROM, WHERE, JOIN, ORDER, GROUP |
| **PostgreSQL** | ANALYZE, FREEZE, ILIKE, RETURNING, WINDOW, LATERAL |
| **Data Types** | INTEGER, BOOLEAN, VARCHAR, TIMESTAMP, INTERVAL, ARRAY |
| **Constraints** | PRIMARY, FOREIGN, UNIQUE, CHECK, DEFAULT, NOT, NULL |
| **Common** | USER, TABLE, INDEX, KEY, VALUE, TYPE, CLASS, ROLE, SCHEMA |

**Full list:** See `App\Twig\ReservedKeywordExtension::RESERVED_KEYWORDS`

## How It Works

### 1. Table Name Generation

**File:** `/app/src/Twig/ReservedKeywordExtension.php`

```php
public function getSafeTableName(string $entityName): string
{
    // Convert PascalCase to snake_case
    $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityName));

    // Always add _table suffix (Luminai convention + protects from reserved keywords)
    return $snakeCase . '_table';
}
```

**Template:** `/app/templates/genmax/php/entity_extension.php.twig`

```twig
#[ORM\Table(name: '{{ getSafeTableName(entity.getEntityName()) }}')]
```

**Examples:**
- `User` → `user_table`
- `DealStage` → `deal_stage_table`
- `CalendarType` → `calendar_type_table`

### 2. Column Name Protection

**File:** `/app/src/Twig/ReservedKeywordExtension.php`

```php
class ReservedKeywordExtension extends AbstractExtension
{
    private const RESERVED_KEYWORDS = [
        'DEFAULT', 'USER', 'ORDER', 'GROUP', 'PUBLIC', // ...400+ keywords
    ];

    public function isReservedKeyword(string $propertyName): bool
    {
        return in_array(strtoupper($propertyName), self::RESERVED_KEYWORDS, true);
    }

    public function getSafeColumnName(string $propertyName): string
    {
        return $this->isReservedKeyword($propertyName)
            ? $propertyName . '_prop'
            : $propertyName;
    }
}
```

### 3. Template Integration

**File:** `/app/templates/genmax/php/entity_generated.php.twig`

```twig
#[ORM\Column({% if isReservedKeyword(property.getPropertyName()) %}name: '{{ getSafeColumnName(property.getPropertyName()) }}', {% endif %}type: '{{ property.getPropertyType() }}'...)]
```

### 4. Automatic Application

When you run:

```bash
php bin/console genmax:generate Calendar
```

Genmax automatically:
1. **Table Name**: Converts `Calendar` → `calendar_table`
2. **Property Check**: Checks each property name against the reserved keywords list
3. **Column Protection**: Adds `name: 'propertyName_prop'` if property name is reserved
4. **Code Generation**: Generates safe Doctrine entity code
5. **Migration Ready**: Creates correct database migrations

## Current Usage in Luminai

**34 properties** across multiple entities use reserved keywords:

| Entity | Reserved Properties |
|--------|-------------------|
| Agent | user |
| AgentType | default |
| AuditLog | user |
| BillingFrequency | default, value |
| Calendar | default, public, user |
| CalendarExternalLink | user |
| CalendarType | default |
| Company | public |
| DealCategory | default, group |
| DealType | default |
| Event | sequence |
| EventAttendee | comment, user |
| EventCategory | default |
| EventResource | type |
| Flag | system |
| Holiday | year |
| LostReason | default |
| Notification | type |
| NotificationType | default |
| Pipeline | default |
| PipelineStageTemplate | order |
| PipelineTemplate | default, public |
| SocialMedia | user |
| StepInput | type |
| TalkMessage | system |
| Task | type, user |
| TaskTemplate | type |

## Verification

### Check if a Property Name is Reserved

```bash
php bin/console debug:twig --filter=isReservedKeyword
```

### Find All Reserved Keywords in Database

```sql
SELECT DISTINCT e.entity_name, p.property_name
FROM generator_property p
JOIN generator_entity e ON p.entity_id = e.id
WHERE UPPER(p.property_name) IN (
    'DEFAULT', 'USER', 'ORDER', 'GROUP', 'TABLE',
    'INDEX', 'KEY', 'VALUE', 'TYPE', 'CLASS'
)
ORDER BY e.entity_name, p.property_name;
```

### Inspect Generated Entity

```bash
grep -E "Column.*name.*_prop" src/Entity/Generated/CalendarGenerated.php
```

Expected output:
```php
#[ORM\Column(name: 'default_prop', type: 'boolean')]
#[ORM\Column(name: 'public_prop', type: 'boolean')]
```

## Updating the Keywords List

The reserved keywords list is maintained in a **const array** for easy updates:

**File:** `/app/src/Twig/ReservedKeywordExtension.php`

```php
private const RESERVED_KEYWORDS = [
    // PostgreSQL Core Reserved
    'ALL', 'ANALYZE', 'AND', 'ANY', 'ARRAY', 'AS', 'ASC', ...

    // SQL:2023 ANSI Additional
    'ABSOLUTE', 'ACTION', 'ADD', 'AFTER', ...

    // Add new keywords here when PostgreSQL or SQL standards update
    'NEW_KEYWORD_2026', ...
];
```

**When to Update:**
- New PostgreSQL major version released
- SQL standard updated (SQL:2026, etc.)
- Discovered conflicts in production

**After Update:**
1. Clear cache: `php bin/console cache:clear`
2. Regenerate all entities: `php bin/console genmax:generate`

## Benefits

1. **Prevents SQL Errors:** No more "syntax error at or near 'user'" or 'default'" during migrations
2. **Automatic Protection:** Developers don't need to remember reserved keywords
3. **Consistent Naming:** ALL tables follow `{name}_table` convention
4. **No Manual Configuration:** Removed `table_name` field from GeneratorEntity
5. **Future-Proof:** Comprehensive list covers current and legacy SQL versions
6. **Zero Configuration:** Works automatically, no setup required
7. **Safe Property Names:** PHP properties keep their logical names (`$default`, `$user`)
8. **Clean Database:** Column names clearly indicate they were auto-suffixed (`default_prop`)

## Best Practices

### DO:
✅ Use logical entity names even if they're reserved keywords (`User`, `Order`, `Group`)
✅ Use logical property names even if they're reserved keywords (`default`, `type`, `user`)
✅ Let Genmax handle table and column naming automatically
✅ Trust the reserved keywords list (400+ keywords)
✅ Trust the automatic `_table` suffix for all tables

### DON'T:
❌ Manually set `table_name` in database (field removed!)
❌ Manually add `_prop` suffix to property names in database
❌ Manually add `_table` suffix to entity names
❌ Worry about SQL reserved keywords when designing entities
❌ Use different naming conventions for reserved keywords

## Examples

### Example 1: User Relationship

```php
// Property name: user (reserved keyword)
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\Column(name: 'user_prop')]  // ← Genmax adds this automatically
protected ?User $user = null;
```

### Example 2: Default Flag

```php
// Property name: default (reserved keyword)
#[ORM\Column(name: 'default_prop', type: 'boolean')]  // ← Auto-protected
protected bool $default;
```

### Example 3: Type Classification

```php
// Property name: type (reserved keyword)
#[ORM\Column(name: 'type_prop', type: 'string', length: 50)]
protected string $type;
```

## Troubleshooting

### Problem: Migration fails with "syntax error"

**Cause:** Using a reserved keyword without `_prop` suffix

**Solution:** Regenerate the entity:
```bash
php bin/console genmax:generate YourEntity
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Problem: Keyword not detected

**Cause:** Keyword not in the list or incorrect case

**Solution:**
1. Add keyword to `ReservedKeywordExtension::RESERVED_KEYWORDS` array
2. Clear cache: `php bin/console cache:clear`
3. Regenerate entity

### Problem: False positive detection

**Cause:** Keyword in list but actually safe

**Solution:**
1. Remove keyword from array (with caution)
2. Document why it's safe
3. Test on all target database versions

## Testing

### Manual Test

```bash
# 1. Create test property with reserved keyword
docker-compose exec -T database psql -U luminai_user luminai_db -c "
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable)
SELECT gen_random_uuid(), id, 'order', 'Order', 'integer', false
FROM generator_entity WHERE entity_name = 'Test' LIMIT 1;
"

# 2. Generate entity
php bin/console genmax:generate Test

# 3. Verify _prop suffix
grep "order_prop" src/Entity/Generated/TestGenerated.php
```

### Expected Result:
```php
#[ORM\Column(name: 'order_prop', type: 'integer')]
protected int $order;
```

## References

- **PostgreSQL 18 Keywords:** https://www.postgresql.org/docs/current/sql-keywords-appendix.html
- **SQL:2023 Standard:** https://en.wikipedia.org/wiki/List_of_SQL_reserved_words
- **Doctrine Column Mapping:** https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.1 | 2025-10-20 | Added automatic table name generation, removed `table_name` property |
| 1.0 | 2025-10-20 | Initial implementation with column name protection (400+ keywords) |

## Maintainer

**Luminai Development Team**
- Extension: `/app/src/Twig/ReservedKeywordExtension.php`
- Template: `/app/templates/genmax/php/entity_generated.php.twig`
- Documentation: `/app/docs/Genmax/RESERVED_KEYWORDS.md`

---

**Last Updated:** 2025-10-20
**Status:** ✅ Production-Ready
