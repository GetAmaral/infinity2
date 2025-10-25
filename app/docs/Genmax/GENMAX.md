# Genmax Code Generator

**Database-driven code generation for Symfony + API Platform**

Version: 2.0 | Status: Production-Ready | Updated: October 2025

---

## Overview

Genmax generates production-ready Symfony code directly from database entities (`generator_entity` and `generator_property` tables). No CSV files, no manual configuration—just define your entities in the database and run one command.

### What It Generates

| Component | Status | Description |
|-----------|--------|-------------|
| Doctrine Entities | ✅ Active | Base + Extension pattern with UUIDv7 |
| API Platform Config | ✅ Active | YAML configuration with operations |
| DTOs | ✅ Active | Input/Output DTOs for API operations |
| State Processors | ✅ Active | Handles DTO → Entity transformations |
| State Providers | ✅ Active | Custom data fetching logic |
| Repositories | ✅ Active | Base + Extension with query methods |
| Batch Operations | 🔨 Planned | Bulk create/update/delete (future) |

### Architecture Pattern

**Base/Extension Principle:**
- **Generated files** (in `Generated/` folders): ALWAYS regenerated, never edit
- **Extension files**: Created once, safe to customize

---

## Quick Start

```bash
# 1. Define entity in database (via fixtures, admin UI, or direct SQL)
# 2. Run generation
php bin/console genmax:generate

# 3. Generate specific entity only
php bin/console genmax:generate Contact

# 4. Preview without writing files
php bin/console genmax:generate --dry-run
```

**Result:** All code files created in seconds!

---

## Core Concepts

### 1. GeneratorEntity (Database Table)

Defines what entities to generate.

**Essential Fields:**
- `entityName` - PascalCase (e.g., `Contact`, `DealStage`)
- `entityLabel` - Display name (e.g., `Contact`)
- `pluralLabel` - Plural form (e.g., `Contacts`)

**API Configuration:**
- `apiEnabled` - Enable API Platform (bool)
- `apiOperations` - Operations: `['GetCollection', 'Get', 'Post', 'Put', 'Delete']`
- `apiSecurity` - Global security: `"is_granted('ROLE_USER')"`
- `dtoEnabled` - Use DTOs instead of direct entity exposure (bool)

**Advanced:**
- `operationSecurity` - Per-operation security overrides
- `validationGroups` - Global validation groups
- `operationValidationGroups` - Per-operation validation

### 2. GeneratorProperty (Database Table)

Defines properties within an entity.

**Essential Fields:**
- `propertyName` - camelCase (e.g., `fullName`, `emailAddress`)
- `propertyType` - Doctrine type (e.g., `string`, `integer`, `datetime_immutable`)
- `propertyLabel` - Display name

**Database Options:**
- `length` - String max length
- `nullable` - Allow NULL
- `unique` - Unique constraint
- `defaultValue` - Default value

**Validation:**
```json
{
  "NotBlank": {},
  "Length": {"max": 100},
  "Email": {}
}
```

**API Filters:**
- `filterStrategy` - `'partial'`, `'exact'`, `'start'`, `'end'`, `'word_start'`
- `filterSearchable` - Enable text search
- `filterOrderable` - Enable sorting
- `filterBoolean` - Boolean filter
- `filterDate` - Date range filter
- `filterNumericRange` - Numeric range filter

**Relationships:**
- `relationshipType` - `'ManyToOne'`, `'OneToMany'`, `'ManyToMany'`, `'OneToOne'`
- `targetEntity` - Target class (e.g., `'App\\Entity\\Organization'`)
- `inversedBy` / `mappedBy` - Relationship mapping
- `cascade` - `['persist', 'remove']`
- `orphanRemoval` - Remove orphaned entities

---

## Generated File Structure

For entity `Contact` with API enabled and DTOs enabled:

```
app/
├── config/api_platform/
│   └── Contact.yaml                              # ALWAYS regenerated
│
├── src/Entity/
│   ├── Contact.php                               # Created once, safe to edit
│   └── Generated/
│       └── ContactGenerated.php                  # ALWAYS regenerated
│
├── src/Dto/
│   ├── ContactInputDto.php                       # Created once, safe to edit
│   ├── ContactOutputDto.php                      # Created once, safe to edit
│   └── Generated/
│       ├── ContactInputDtoGenerated.php          # ALWAYS regenerated
│       └── ContactOutputDtoGenerated.php         # ALWAYS regenerated
│
├── src/State/
│   ├── ContactProcessor.php                      # ALWAYS regenerated
│   └── ContactProvider.php                       # ALWAYS regenerated
│
└── src/Repository/
    ├── ContactRepository.php                     # Created once, safe to edit
    └── Generated/
        └── ContactRepositoryGenerated.php        # ALWAYS regenerated
```

---

## Features in Detail

### Reserved Keyword Protection

Genmax automatically protects against SQL reserved keywords:

**Table Names:** ALL tables get `_table` suffix
- `User` → `user_table`
- `Order` → `order_table`

**Column Names:** Reserved keywords get `_prop` suffix
- Property `default` → column `default_prop`
- Property `user` → column `user_prop`

**400+ keywords protected** from PostgreSQL, MySQL, SQL Server, and PHP.

See `app/src/Twig/ReservedKeywordExtension.php` for full list.

### API Filter Examples

```bash
# Text search (partial match)
GET /api/contacts?fullName=john

# Exact match
GET /api/contacts?email=john@example.com

# Sorting
GET /api/contacts?order[createdAt]=desc

# Boolean filter
GET /api/contacts?isActive=true

# Date range
GET /api/contacts?createdAt[after]=2024-01-01

# Numeric range
GET /api/contacts?age[gte]=18&age[lte]=65

# Null check
GET /api/contacts?deletedAt[exists]=false
```

### Security Configuration

**Global security:**
```php
$entity->setApiSecurity("is_granted('ROLE_USER')");
```

**Per-operation security:**
```php
$entity->setOperationSecurity([
    'Post' => "is_granted('ROLE_ADMIN')",
    'Delete' => "is_granted('ROLE_ADMIN')"
]);
```

### Multi-Tenant Support

All generated entities automatically:
- Include `organization` relationship
- Filter by organization in State Providers
- Validate organization ownership in Processors

Disable with: `$entity->setHasOrganization(false)`

---

## Practical Examples

### Example 1: Simple Contact Entity

```php
use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;

$entity = new GeneratorEntity();
$entity->setEntityName('Contact');
$entity->setEntityLabel('Contact');
$entity->setPluralLabel('Contacts');
$entity->setApiEnabled(true);
$entity->setDtoEnabled(true);
$entity->setApiOperations(['GetCollection', 'Get', 'Post', 'Put', 'Delete']);
$entity->setApiSecurity("is_granted('ROLE_USER')");

$em->persist($entity);

// Full Name Property
$fullName = new GeneratorProperty();
$fullName->setEntity($entity);
$fullName->setPropertyName('fullName');
$fullName->setPropertyLabel('Full Name');
$fullName->setPropertyType('string');
$fullName->setLength(100);
$fullName->setNullable(false);
$fullName->setFilterStrategy('partial');
$fullName->setFilterOrderable(true);
$fullName->setValidationRules([
    ['constraint' => 'NotBlank'],
    ['constraint' => 'Length', 'max' => 100]
]);

$em->persist($fullName);

// Email Property
$email = new GeneratorProperty();
$email->setEntity($entity);
$email->setPropertyName('email');
$email->setPropertyLabel('Email');
$email->setPropertyType('string');
$email->setLength(180);
$email->setUnique(true);
$email->setFilterStrategy('exact');
$email->setFilterOrderable(true);
$email->setValidationRules([
    ['constraint' => 'NotBlank'],
    ['constraint' => 'Email']
]);

$em->persist($email);
$em->flush();
```

**Generate:**
```bash
php bin/console genmax:generate Contact
```

### Example 2: Relationship Property

```php
// Company relationship (ManyToOne)
$company = new GeneratorProperty();
$company->setEntity($entity);
$company->setPropertyName('company');
$company->setPropertyLabel('Company');
$company->setRelationshipType('ManyToOne');
$company->setTargetEntity('App\\Entity\\Company');
$company->setInversedBy('contacts');
$company->setNullable(false);

$em->persist($company);
```

### Example 3: Collection Relationship

```php
// Deal Stages (OneToMany)
$stages = new GeneratorProperty();
$stages->setEntity($entity);
$stages->setPropertyName('stages');
$stages->setPropertyLabel('Stages');
$stages->setRelationshipType('OneToMany');
$stages->setTargetEntity('App\\Entity\\DealStage');
$stages->setMappedBy('pipeline');
$stages->setCascade(['persist', 'remove']);
$stages->setOrphanRemoval(true);
$stages->setOrderBy(['position' => 'ASC']);

$em->persist($stages);
```

---

## Best Practices

### Naming Conventions

✅ **DO:**
- Entity names: PascalCase, singular (`Contact`, `DealStage`)
- Property names: camelCase (`fullName`, `isActive`, `createdAt`)
- Boolean properties: `is` prefix (`isActive`, `isDeleted`)
- Date properties: `At` suffix (`createdAt`, `updatedAt`)

❌ **DON'T:**
- Use plural entity names (`Contacts` ❌)
- Use reserved keywords without letting Genmax handle them
- Manually set table names (auto-generated as `{entity}_table`)

### API Configuration

✅ **DO:**
- Enable DTOs for all entities with write operations
- Use per-operation security for sensitive actions
- Set appropriate filter strategies (exact for IDs/emails, partial for names)
- Enable `filterOrderable` on sortable fields

❌ **DON'T:**
- Expose entities directly without DTOs
- Use global ROLE_ADMIN security (use operation-level instead)
- Forget to set validation rules

### Validation

✅ **DO:**
- Define validation in `validationRules` JSON
- Use stricter validation for create operations
- Combine multiple constraints when needed

❌ **DON'T:**
- Rely on database constraints alone
- Skip validation on optional fields that have format requirements

---

## Troubleshooting

### Problem: Generated files have errors

**Solution:**
1. Check `lastGenerationLog` in `generator_entity` table
2. Run with `--dry-run` to preview
3. Check logs: `docker-compose exec app tail -f var/log/app.log`

### Problem: Filters not working in API

**Solution:**
1. Regenerate: `php bin/console genmax:generate`
2. Clear cache: `php bin/console cache:clear`
3. Check API Platform config: `app/config/api_platform/Entity.yaml`

### Problem: Validation not applied

**Solution:**
1. Verify `validationRules` is valid JSON
2. Check validation groups match operation configuration
3. Ensure DTO is enabled

### Problem: Relationship not generated

**Solution:**
1. Check `targetEntity` is fully qualified class name
2. Verify `inversedBy`/`mappedBy` are correct
3. Ensure target entity exists and is generated

---

## Configuration

**File:** `app/config/services.yaml`

```yaml
parameters:
    genmax.paths:
        entity_dir: 'src/Entity'
        entity_generated_dir: 'src/Entity/Generated'
        dto_dir: 'src/Dto'
        dto_generated_dir: 'src/Dto/Generated'
        processor_dir: 'src/State'
        provider_dir: 'src/State'
        repository_dir: 'src/Repository'
        repository_generated_dir: 'src/Repository/Generated'
        api_platform_config_dir: 'config/api_platform'

    genmax.templates:
        entity_generated: 'genmax/php/entity_generated.php.twig'
        entity_extension: 'genmax/php/entity_extension.php.twig'
        dto_input_generated: 'genmax/php/dto_input_generated.php.twig'
        dto_input_extension: 'genmax/php/dto_input_extension.php.twig'
        dto_output_generated: 'genmax/php/dto_output_generated.php.twig'
        dto_output_extension: 'genmax/php/dto_output_extension.php.twig'
        state_processor: 'genmax/php/state_processor.php.twig'
        state_provider: 'genmax/php/state_provider.php.twig'
        repository_generated: 'genmax/php/repository_generated.php.twig'
        repository_extension: 'genmax/php/repository_extension.php.twig'
        api_platform: 'genmax/yaml/api_platform.yaml.twig'
```

---

## Service Architecture

```
GenmaxOrchestrator (Main Controller)
├── EntityGenerator → Entities (base + extension)
├── ApiGenerator → API Platform YAML configs
├── DtoGenerator → Input/Output DTOs (base + extension)
├── StateProcessorGenerator → DTO → Entity processors
├── StateProviderGenerator → Custom data providers
└── RepositoryGenerator → Repositories (base + extension)
```

**Feature Flags:** See `GenmaxOrchestrator.php:28-43`

---

## Migration & Updates

### Regenerate All Entities

```bash
php bin/console genmax:generate
```

### Regenerate Single Entity

```bash
php bin/console genmax:generate Contact
```

### After Database Schema Changes

```bash
# Update GeneratorEntity or GeneratorProperty in database
# Then regenerate
php bin/console genmax:generate Entity

# Create migration
php bin/console make:migration

# Apply migration
php bin/console doctrine:migrations:migrate
```

---

## Future Features

### Planned (Not Yet Implemented)

- ✨ **Batch Operations** - Bulk create/update/delete API endpoints
- ✨ **Controllers** - Web controllers for Twig templates
- ✨ **Security Voters** - RBAC permission checking
- ✨ **Forms** - Symfony forms for web UI
- ✨ **Templates** - Twig templates for CRUD pages
- ✨ **Tests** - Automated PHPUnit tests

See `app/docs/Genmax/old/BATCH_OPERATIONS_IMPLEMENTATION_PLAN.md` for batch operations roadmap.

---

## Resources

**Documentation:**
- API Platform: https://api-platform.com/docs/
- Doctrine ORM: https://www.doctrine-project.org/
- Symfony Validation: https://symfony.com/doc/current/validation.html

**Project Files:**
- Service Code: `/app/src/Service/Genmax/`
- Templates: `/app/templates/genmax/`
- Configuration: `/app/config/services.yaml`
- Old Docs: `/app/docs/Genmax/old/`

**Quick Start Guide:** See `QUICK_START.md` in this directory.

---

**Last Updated:** October 2025
**Version:** 2.0
**Maintainer:** Luminai Development Team
