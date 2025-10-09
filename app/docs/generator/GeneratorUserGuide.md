# Generator User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [CSV Reference](#csv-reference)
4. [Usage](#usage)
5. [Customization](#customization)
6. [Maintenance](#maintenance)
7. [Troubleshooting](#troubleshooting)

---

## Introduction

The TURBO Generator System is a powerful code generation tool that creates complete CRUD applications from CSV definitions. It generates 17+ files per entity, including entities, repositories, controllers, forms, voters, templates, tests, and more.

### What Gets Generated

For each entity defined in CSV:

| Layer | Files Generated |
|-------|----------------|
| **Entity** | EntityGenerated.php, Entity.php, OrganizationTrait.php |
| **API** | Entity.yaml (API Platform configuration) |
| **Repository** | EntityRepositoryGenerated.php, EntityRepository.php |
| **Controller** | EntityControllerGenerated.php, EntityController.php |
| **Security** | EntityVoterGenerated.php, EntityVoter.php |
| **Form** | EntityTypeGenerated.php, EntityType.php |
| **Templates** | index.html.twig, form.html.twig, show.html.twig |
| **Navigation** | Updated navbar menu items |
| **Translations** | Entity labels and messages |
| **Tests** | Entity, Repository, Controller, Voter tests |

### Key Benefits

✅ **Rapid Development** - 90% reduction in boilerplate coding
✅ **Consistency** - All code follows the same patterns
✅ **Maintainability** - CSV as single source of truth
✅ **Safe Customization** - Generated + Extension pattern
✅ **Full Test Coverage** - Tests generated automatically
✅ **Multi-tenant Ready** - OrganizationTrait pattern

---

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Symfony 7.3+
- Composer installed
- PostgreSQL database
- Basic understanding of Symfony and Doctrine

### Installation

The generator is included in the Luminai application. No additional installation needed.

### Configuration

1. **Prepare CSV files** in `config/` directory:
   - `EntityNew.csv` - Entity definitions
   - `PropertyNew.csv` - Property definitions

2. **Environment setup**:
```bash
# Database must be configured
DATABASE_URL="postgresql://user:pass@localhost:5432/dbname"
```

3. **Verify installation**:
```bash
php bin/console app:generate-from-csv --help
```

---

## CSV Reference

### Entity.csv Columns (25 columns)

Complete reference for all entity-level configuration:

#### Basic Information

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `entityName` | string | ✅ | Entity class name (PascalCase) | `Contact` |
| `entityLabel` | string | ✅ | Singular display label | `Contact` |
| `pluralLabel` | string | ✅ | Plural display label | `Contacts` |
| `icon` | string | ✅ | Bootstrap icon class | `bi-person` |
| `description` | string | | Entity description | `Manages customer contacts` |

#### Multi-Tenancy

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `hasOrganization` | boolean | | Enables multi-tenant via OrganizationTrait | `true` |

#### API Configuration

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `apiEnabled` | boolean | | Expose as REST API | `true` |
| `operations` | string | | API operations (comma-separated) | `GetCollection,Get,Post,Put,Delete` |
| `security` | string | | API security expression | `is_granted('ROLE_USER')` |
| `normalizationContext` | string | | Read serialization groups | `contact:read,audit:read` |
| `denormalizationContext` | string | | Write serialization groups | `contact:write` |
| `paginationEnabled` | boolean | | Enable API pagination | `true` |
| `itemsPerPage` | int | | Items per page (1-1000) | `30` |
| `order` | string | | Default sort order (JSON) | `{"name": "asc"}` |
| `searchableFields` | string | | Search fields (comma-separated) | `name,email,phone` |
| `filterableFields` | string | | Filter fields (comma-separated) | `status,active` |

#### Security & Authorization

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `voterEnabled` | boolean | | Generate security voter | `true` |
| `voterAttributes` | string | | Voter attributes (comma-separated) | `VIEW,EDIT,DELETE` |

#### Form Configuration

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `formTheme` | string | | Form theme template | `bootstrap_5_layout.html.twig` |

#### UI Templates

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `indexTemplate` | string | | Custom list template path | `contact/index.html.twig` |
| `formTemplate` | string | | Custom form template path | `contact/form.html.twig` |
| `showTemplate` | string | | Custom detail template path | `contact/show.html.twig` |

#### Navigation

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `menuGroup` | string | | Menu group name | `CRM` |
| `menuOrder` | int | | Menu display order (0-999) | `10` |

#### Testing

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `testEnabled` | boolean | | Generate test files | `true` |

### Property.csv Columns (38 columns)

Complete reference for property-level configuration:

#### Basic Information

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `entityName` | string | ✅ | Parent entity name | `Contact` |
| `propertyName` | string | ✅ | Property name (camelCase) | `email` |
| `propertyLabel` | string | ✅ | Display label | `Email Address` |
| `propertyType` | string | ✅ | Doctrine type | `string`, `integer`, `datetime` |

#### Database Configuration

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `nullable` | boolean | | Allow NULL values | `false` |
| `length` | int | | Max length (for strings) | `255` |
| `precision` | int | | Precision (for decimals) | `10` |
| `scale` | int | | Scale (for decimals) | `2` |
| `unique` | boolean | | Unique constraint | `true` |
| `defaultValue` | string | | Default value | `active` |

#### Relationships

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `relationshipType` | string | | Relation type | `ManyToOne`, `OneToMany`, `ManyToMany` |
| `targetEntity` | string | | Related entity name | `Organization` |
| `inversedBy` | string | | Inverse relation property | `contacts` |
| `mappedBy` | string | | Mapped by property | `contact` |
| `cascade` | string | | Cascade operations (comma-separated) | `persist,remove` |
| `orphanRemoval` | boolean | | Remove orphaned entities | `true` |
| `fetch` | string | | Fetch strategy | `EAGER`, `LAZY`, `EXTRA_LAZY` |
| `orderBy` | string | | Order for collections (JSON) | `{"name": "asc"}` |

#### Validation

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `validationRules` | string | | Constraints (comma-separated) | `NotBlank,Email,Length(min=5)` |
| `validationMessage` | string | | Custom error message | `Please enter valid email` |

#### Form Configuration

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `formType` | string | | Symfony form type | `EmailType`, `TextType` |
| `formOptions` | string | | Form options (JSON) | `{"attr": {"placeholder": "..."}}` |
| `formRequired` | boolean | | Required in form | `true` |
| `formReadOnly` | boolean | | Read-only in form | `false` |
| `formHelp` | string | | Help text | `Enter your work email` |

#### UI Display

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `showInList` | boolean | | Show in list view | `true` |
| `showInDetail` | boolean | | Show in detail view | `true` |
| `showInForm` | boolean | | Show in form | `true` |
| `sortable` | boolean | | Sortable in list | `true` |
| `searchable` | boolean | | Searchable field | `true` |
| `filterable` | boolean | | Filterable field | `true` |

#### API Configuration

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `apiReadable` | boolean | | Readable via API | `true` |
| `apiWritable` | boolean | | Writable via API | `true` |
| `apiGroups` | string | | Serialization groups (comma-separated) | `contact:read,contact:write` |

#### Localization

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `translationKey` | string | | Custom translation key | `contact.email` |
| `formatPattern` | string | | Display format pattern | `%s@example.com` |

#### Fixtures

| Column | Type | Required | Description | Example |
|--------|------|----------|-------------|---------|
| `fixtureType` | string | | Faker fixture type | `email`, `name`, `text` |
| `fixtureOptions` | string | | Fixture options (JSON) | `{"unique": true}` |

### CSV Best Practices

1. **Always use consistent naming**:
   - Entities: PascalCase (`Contact`, `TaskList`)
   - Properties: camelCase (`firstName`, `emailAddress`)

2. **Required fields**:
   - Always provide: `entityName`, `entityLabel`, `pluralLabel`, `icon`
   - For properties: `entityName`, `propertyName`, `propertyLabel`, `propertyType`

3. **Relationships**:
   - Set `targetEntity` to existing entity names
   - Use `inversedBy` for bidirectional relations
   - Always specify `cascade` operations

4. **Validation**:
   - Use comma-separated constraints: `NotBlank,Email`
   - Provide custom messages for better UX

5. **API Configuration**:
   - Enable pagination for collections
   - Use security expressions: `is_granted('ROLE_USER')`
   - Group operations logically

---

## Usage

### Generate All Entities

Generate code for all entities defined in CSV:

```bash
php bin/console app:generate-from-csv
```

### Generate Single Entity

Generate code for specific entity:

```bash
php bin/console app:generate-from-csv --entity=Contact
```

### Dry Run Mode

Preview what will be generated without writing files:

```bash
php bin/console app:generate-from-csv --dry-run
```

### Batch Generation

Generate multiple entities with progress tracking:

```bash
php scripts/batch-generate.php --batch=10
```

Options:
- `--batch=SIZE` - Process N entities per batch
- `--continue-on-error` - Continue if an entity fails
- `--skip-tests` - Skip running tests after each batch

### Pre-Generation Checks

Verify system readiness before generation:

```bash
php scripts/pre-generation-check.php
```

Auto-fix issues:

```bash
php scripts/pre-generation-check.php --fix
```

### Post-Generation Tasks

After generation, run:

```bash
# Create database migration
php bin/console make:migration

# Run migration
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
php bin/console cache:clear

# Run tests
php bin/phpunit
```

---

## Customization

### Extending Generated Classes

The generator uses the **Generated + Extension** pattern:

```php
// Generated base (always regenerated)
// src/Entity/Generated/ContactGenerated.php
abstract class ContactGenerated extends EntityBase
{
    // Auto-generated properties and methods
}

// Extension class (safe to edit)
// src/Entity/Contact.php
class Contact extends ContactGenerated
{
    // Add your custom methods here
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

### Adding Custom Business Logic

**In Controllers:**

```php
// src/Controller/ContactController.php
class ContactController extends ContactControllerGenerated
{
    #[Route('/contact/{id}/send-email', name: 'contact_send_email')]
    public function sendEmail(Contact $contact): Response
    {
        // Custom action
    }
}
```

**In Repositories:**

```php
// src/Repository/ContactRepository.php
class ContactRepository extends ContactRepositoryGenerated
{
    public function findActiveContacts(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }
}
```

**In Voters:**

```php
// src/Security/Voter/ContactVoter.php
class ContactVoter extends ContactVoterGenerated
{
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        // Add custom authorization logic
        if ($attribute === 'SPECIAL_ACTION') {
            return $this->canDoSpecialAction($subject, $token);
        }

        return parent::voteOnAttribute($attribute, $subject, $token);
    }
}
```

### Custom Templates

Override generated templates:

1. Generated templates are in `templates/{entity}/`
2. Edit them directly - they are regenerated each time
3. For complex customizations, use Twig inheritance:

```twig
{# templates/contact/custom_index.html.twig #}
{% extends 'contact/index.html.twig' %}

{% block content %}
    {# Custom content #}
    {{ parent() }}
{% endblock %}
```

---

## Maintenance

### Updating CSV

To modify existing entities:

1. **Edit CSV files** (`EntityNew.csv`, `PropertyNew.csv`)
2. **Run generator**:
```bash
php bin/console app:generate-from-csv
```
3. **Create migration**:
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```
4. **Clear cache**:
```bash
php bin/console cache:clear
```

### Regenerating Code

To regenerate after CSV changes:

```bash
# Preview changes
php bin/console app:generate-from-csv --dry-run

# Generate
php bin/console app:generate-from-csv

# Apply migrations
php bin/console doctrine:migrations:migrate

# Test
php bin/phpunit
```

### Backup & Restore

Generator automatically creates backups before generation:

**Backup location**: `var/generatorBackup/{timestamp}/`

**Restore from backup**:

```bash
# List backups
ls -la var/generatorBackup/

# Restore (manually copy files back)
cp var/generatorBackup/20250107_120000/*.bak src/Entity/
```

---

## Troubleshooting

### Common Issues

#### 1. CSV Validation Errors

**Problem**: `CSV validation failed`

**Solution**:
```bash
# Check CSV syntax
php scripts/verify-csv-migration.php

# Common issues:
# - Missing required columns
# - Invalid entity names (must be PascalCase)
# - Invalid property types
# - Relationships to non-existent entities
```

#### 2. Generation Failures

**Problem**: `Generation failed for entity X`

**Solution**:
```bash
# Check logs
tail -f var/log/dev.log

# Common causes:
# - Invalid CSV data
# - Missing template files
# - Permission issues
# - Namespace conflicts
```

#### 3. Database Migration Errors

**Problem**: `Migration failed`

**Solution**:
```bash
# Check database status
php bin/console doctrine:schema:validate

# View pending migrations
php bin/console doctrine:migrations:status

# Rollback if needed
php bin/console doctrine:migrations:migrate prev
```

#### 4. Test Failures

**Problem**: Tests fail after generation

**Solution**:
```bash
# Clear test cache
php bin/console cache:clear --env=test

# Reload fixtures
php bin/console doctrine:fixtures:load --env=test

# Run specific test
php bin/phpunit tests/Entity/ContactTest.php
```

#### 5. Performance Issues

**Problem**: Generation is slow

**Solution**:
```bash
# Use batch generation
php scripts/batch-generate.php --batch=5

# Check system resources
php scripts/performance-test.php

# Optimize database
php scripts/performance-optimize.php
```

### Getting Help

1. **Check logs**: `var/log/dev.log`
2. **Run diagnostics**: `php scripts/pre-generation-check.php`
3. **Review documentation**: `docs/`
4. **Check generated files**: Look for `@generated` tags

### Support Resources

- **Phase Documentation**: `docs/GeneratorPlan/`
- **Developer Guide**: `docs/GeneratorDeveloperGuide.md`
- **API Reference**: `docs/api/`
- **Example Projects**: `examples/`

---

## Next Steps

- Read [Generator Developer Guide](GeneratorDeveloperGuide.md) for advanced topics
- Review [Production Deployment Guide](ProductionDeployment.md) for deployment
- Check [Cheat Sheets](CheatSheets.md) for quick reference
