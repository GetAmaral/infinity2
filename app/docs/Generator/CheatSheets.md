# Generator Cheat Sheets

Quick reference for common tasks and commands.

---

## Table of Contents

1. [CLI Commands](#cli-commands)
2. [Database Tables Reference](#database-tables-reference)
3. [Doctrine Types → Form Types](#doctrine-types--form-types)
4. [Common Validation Rules](#common-validation-rules)
5. [Troubleshooting](#troubleshooting)
6. [Performance Tips](#performance-tips)
7. [Legacy CSV Reference](#legacy-csv-reference)

---

## CLI Commands

### Generation (Database-First)

```bash
# Generate all entities from database
php bin/console genmax:generate

# Generate single entity
php bin/console genmax:generate --entity=Contact

# Dry run (preview only)
php bin/console genmax:generate --dry-run

# Export database definitions to CSV backup
php bin/console generator:export-csv
# Creates timestamped files in app/config/backup/
```

### Feature Flags

Located in `/app/src/Service/Generator/GeneratorOrchestrator.php`:

```php
// Currently Active
private const ENTITY_ACTIVE = true;      // ✅

// To enable other generators, change false to true:
private const REPOSITORY_ACTIVE = false; // Change to true
private const CONTROLLER_ACTIVE = false; // Change to true
private const FORM_ACTIVE = false;       // Change to true
private const TEMPLATE_ACTIVE = false;   // Change to true
private const VOTER_ACTIVE = false;      // Change to true
private const API_ACTIVE = false;        // Change to true
private const NAVIGATION_ACTIVE = false; // Change to true
private const TRANSLATION_ACTIVE = false;// Change to true
private const TESTS_ACTIVE = false;      // Change to true
```

### Validation & Testing

```bash
# Pre-generation checks
php scripts/pre-generation-check.php

# With auto-fix
php scripts/pre-generation-check.php --fix

# Verify CSV
php scripts/verify-csv-migration.php

# Performance test
php scripts/performance-test.php

# Full performance test
php scripts/performance-test.php --full --report=perf.json

# Code quality
php scripts/code-quality-check.php

# With auto-fix
php scripts/code-quality-check.php --fix --report=quality.json

# Performance optimization
php scripts/performance-optimize.php --analyze

# Apply optimizations
php scripts/performance-optimize.php --optimize --report=opt.json
```

### Statistics

```bash
# Generation statistics (text)
php scripts/generation-stats.php

# JSON output
php scripts/generation-stats.php --format=json

# Markdown output
php scripts/generation-stats.php --format=markdown --output=stats.md
```

### Database

```bash
# Create migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Rollback migration
php bin/console doctrine:migrations:migrate prev

# Migration status
php bin/console doctrine:migrations:status

# Validate schema
php bin/console doctrine:schema:validate
```

### Cache

```bash
# Clear cache
php bin/console cache:clear

# Warm cache
php bin/console cache:warmup

# Clear + warm
php bin/console cache:clear && php bin/console cache:warmup
```

### Testing

```bash
# All tests
php bin/phpunit

# Specific suite
php bin/phpunit tests/Entity/
php bin/phpunit tests/Repository/
php bin/phpunit tests/Controller/

# With coverage
php bin/phpunit --coverage-html coverage/

# Single test
php bin/phpunit tests/Entity/ContactTest.php
```

### Code Quality

```bash
# PHPStan
vendor/bin/phpstan analyse src --level=8

# PHP CS Fixer (check)
vendor/bin/php-cs-fixer fix --dry-run --diff

# PHP CS Fixer (apply)
vendor/bin/php-cs-fixer fix

# Security audit
composer audit
```

---

## Database Tables Reference

### generator_entity Table - Quick Reference

| Column | Type | Example | Notes |
|--------|------|---------|-------|
| `entity_name` | string | `Contact` | PascalCase, required, unique |
| `entity_label` | string | `Contact` | Singular label |
| `plural_label` | string | `Contacts` | Plural label |
| `icon` | string | `bi-person` | Bootstrap icon |
| `has_organization` | boolean | `true` | Multi-tenant |
| `api_enabled` | boolean | `true` | Expose API |
| `api_operations` | json | `["GetCollection","Get"]` | API operations |
| `api_searchable_fields` | json | `["name","email"]` | Search fields |
| `api_filterable_fields` | json | `["status"]` | Filter fields |
| `voter_enabled` | boolean | `true` | Generate voter |
| `menu_group` | string | `CRM` | Menu group |
| `menu_order` | int | `10` | Menu position |
| `canvas_x` | int | `100` | Canvas X position |
| `canvas_y` | int | `100` | Canvas Y position |

### generator_property Table - Quick Reference

| Column | Type | Example | Notes |
|--------|------|---------|-------|
| `property_name` | string | `email` | camelCase, required |
| `property_label` | string | `Email Address` | Display label |
| `property_type` | string | `string` | Doctrine type |
| `nullable` | boolean | `false` | Allow NULL |
| `length` | int | `255` | For strings |
| `unique` | boolean | `true` | Unique constraint |
| `relationship_type` | string | `ManyToOne` | Relation type |
| `target_entity` | string | `Organization` | Related entity |
| `validation_rules` | json | `["NotBlank","Email"]` | Constraints |
| `form_type` | string | `EmailType` | Form type |
| `show_in_list` | boolean | `true` | Show in list |
| `searchable` | boolean | `true` | Searchable |
| `api_readable` | boolean | `true` | API readable |

### Quick Database Queries

```bash
# View all entities
php bin/console doctrine:query:sql "SELECT entity_name, menu_group FROM generator_entity ORDER BY menu_group, menu_order"

# View properties for an entity
php bin/console doctrine:query:sql "SELECT property_name, property_type FROM generator_property WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Contact')"

# Count entities
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM generator_entity"
```

---

## Doctrine Types → Form Types

Common mappings:

| Doctrine Type | Form Type | Example |
|--------------|-----------|---------|
| `string` | `TextType` | Short text |
| `text` | `TextareaType` | Long text |
| `integer` | `IntegerType` | Whole numbers |
| `decimal` | `NumberType` | Decimals |
| `boolean` | `CheckboxType` | true/false |
| `datetime` | `DateTimeType` | Date + time |
| `date` | `DateType` | Date only |
| `time` | `TimeType` | Time only |
| `array` | `ChoiceType` (multiple) | Multi-select |
| `json` | `TextareaType` | JSON data |

**Relationships:**

| Relation | Form Type | Options |
|----------|-----------|---------|
| `ManyToOne` | `EntityType` | `choice_label`, `class` |
| `OneToMany` | `CollectionType` | `entry_type`, `allow_add` |
| `ManyToMany` | `EntityType` (multiple) | `multiple: true` |

**Special Types:**

| Use Case | Form Type | Notes |
|----------|-----------|-------|
| Email | `EmailType` | Built-in validation |
| URL | `UrlType` | URL validation |
| Phone | `TelType` | Telephone |
| Color | `ColorType` | Color picker |
| File | `FileType` | File upload |
| Password | `PasswordType` | Password field |
| Hidden | `HiddenType` | Hidden input |

---

## Common Validation Rules

### Basic Constraints

```
NotBlank                    # Required, not empty
NotNull                     # Required (allows empty string)
Type(type="string")         # Type validation
Valid                       # Validate nested object
```

### String Constraints

```
Length(min=5, max=255)      # String length
Email                       # Email format
Url                         # URL format
Regex(pattern="/^[A-Z]/")   # Regex match
```

### Number Constraints

```
Range(min=0, max=100)       # Number range
Positive                    # > 0
PositiveOrZero             # >= 0
Negative                    # < 0
LessThan(value=100)        # < 100
GreaterThan(value=0)       # > 0
```

### Date Constraints

```
Date                        # Valid date
DateTime                    # Valid datetime
Time                        # Valid time
GreaterThan("today")       # Date comparison
```

### Choice Constraints

```
Choice(choices=["a","b"])   # In list
Count(min=1, max=5)        # Array count
```

### Custom Messages

```
NotBlank(message="This field is required")
Email(message="Please enter valid email")
Length(min=5, message="Must be at least 5 characters")
```

### Combined Constraints

```
NotBlank,Email,Length(min=5,max=255)
NotBlank,Url
Range(min=0,max=100),Positive
```

---

## Troubleshooting

### Database Errors

**Error**: `No entities found in database`
```bash
# Check if generator tables exist
php bin/console doctrine:schema:validate

# Run migrations
php bin/console doctrine:migrations:migrate

# Verify data
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM generator_entity"
```

**Error**: `Invalid entity name`
```bash
# Fix: Use PascalCase in database
UPDATE generator_entity SET entity_name = 'Contact' WHERE entity_name = 'contact';
```

**Error**: `Target entity does not exist`
```bash
# Fix: Ensure target entity exists in database
SELECT entity_name FROM generator_entity WHERE entity_name = 'Organization';
```

### Generation Errors

**Error**: `Class already exists`
```bash
# Solution: Remove old file or regenerate
rm src/Entity/Contact.php
php bin/console gen --entity=Contact
```

**Error**: `Template not found`
```bash
# Solution: Check template exists
ls -la templates/Generator/
php bin/console cache:clear
```

**Error**: `Permission denied`
```bash
# Solution: Fix permissions
chmod -R 755 var/
chmod -R 755 src/
```

### Migration Errors

**Error**: `Migration already exists`
```bash
# Solution: Delete duplicate or run existing
rm migrations/VersionXXXXXXXXXXXXXX.php
php bin/console doctrine:migrations:migrate
```

**Error**: `Table already exists`
```bash
# Solution: Update migration or drop table
php bin/console doctrine:schema:update --force
# Or manually:
psql -U user dbname -c "DROP TABLE contact;"
```

### Test Failures

**Error**: `Database not found`
```bash
# Solution: Create test database
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
```

**Error**: `Fixtures failed to load`
```bash
# Solution: Clear cache and reload
php bin/console cache:clear --env=test
php bin/console doctrine:fixtures:load --env=test
```

---

## Performance Tips

### Generation Performance

```bash
# Use batch generation for many entities
php scripts/batch-generate.php --batch=10 --skip-tests

# Run tests after all generation
php bin/phpunit

# Parallel generation (future)
# Generate multiple entities in parallel
```

### Database Performance

```bash
# Add indexes for search fields
# In Entity.csv: searchableFields=name,email
# Auto-generates indexes

# Use EAGER fetch for critical relations
# In Property.csv: fetch=EAGER

# Optimize queries with QueryBuilder
# In XxxRepository.php extension class
```

### Cache Performance

```bash
# Production cache warmup
php bin/console cache:warmup --env=prod

# Use Redis for sessions/cache
# In .env: REDIS_URL=redis://localhost:6379

# Enable OPcache
# In php.ini: opcache.enable=1
```

### Code Quality

```bash
# Fix code style before commit
vendor/bin/php-cs-fixer fix

# Run PHPStan on generated code
vendor/bin/phpstan analyse src/Entity/Generated --level=8

# Regular security audits
composer audit
```

---

## Common Patterns (Database)

### Simple Entity

```sql
-- Insert entity
INSERT INTO generator_entity (id, entity_name, entity_label, plural_label, icon, has_organization, api_enabled, voter_enabled, menu_group, menu_order, test_enabled)
VALUES (gen_random_uuid(), 'Contact', 'Contact', 'Contacts', 'bi-person', true, true, true, 'CRM', 10, true);

-- Insert property (name field)
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, length, show_in_list, show_in_detail, show_in_form, searchable)
VALUES (gen_random_uuid(), (SELECT id FROM generator_entity WHERE entity_name = 'Contact'), 'name', 'Name', 'string', false, 255, true, true, true, true);
```

### Entity with Organization

```sql
-- When has_organization = true, OrganizationTrait is automatically added
INSERT INTO generator_entity (id, entity_name, entity_label, plural_label, icon, has_organization)
VALUES (gen_random_uuid(), 'Contact', 'Contact', 'Contacts', 'bi-person', true);
```

### ManyToOne Relationship

```sql
-- Add organization relationship property
INSERT INTO generator_property (id, entity_id, property_name, property_label, relationship_type, target_entity, form_type)
VALUES (gen_random_uuid(),
        (SELECT id FROM generator_entity WHERE entity_name = 'Contact'),
        'organization',
        'Organization',
        'ManyToOne',
        'Organization',
        'EntityType');
```

### Searchable Text Field

```sql
-- Add searchable description field
INSERT INTO generator_property (id, entity_id, property_name, property_label, property_type, nullable, show_in_list, searchable, form_type)
VALUES (gen_random_uuid(),
        (SELECT id FROM generator_entity WHERE entity_name = 'Contact'),
        'description',
        'Description',
        'text',
        true,
        true,
        true,
        'TextareaType');
```

---

## Quick Workflows

### Add New Entity (Database-First)

1. Insert into `generator_entity` table (via UI or SQL)
2. Add properties to `generator_property` table
3. Generate: `php bin/console gen --entity=NewEntity`
4. Migrate: `php bin/console make:migration && php bin/console doctrine:migrations:migrate`
5. Test: `php bin/phpunit tests/Entity/NewEntityTest.php`

### Update Existing Entity

1. Edit `generator_entity` or `generator_property` table
2. Regenerate: `php bin/console gen --entity=Contact`
3. Create migration: `php bin/console make:migration`
4. Review migration: `cat migrations/Version*.php`
5. Apply: `php bin/console doctrine:migrations:migrate`
6. Clear cache: `php bin/console cache:clear`

### Deploy to Production

1. Check: `php scripts/pre-generation-check.php`
2. Test: `php bin/phpunit`
3. Quality: `php scripts/code-quality-check.php`
4. Commit: `git add . && git commit -m "..." && git push`
5. Deploy: `ssh server 'cd /var/www && git pull && php bin/console doctrine:migrations:migrate'`
6. Verify: `curl https://domain.com/health/detailed`

---

## Keyboard Shortcuts (for reference)

### Git

```bash
gs      # git status
ga .    # git add .
gc -m   # git commit -m
gp      # git push
gl      # git pull
```

### Composer

```bash
c install       # composer install
c update        # composer update
c require       # composer require
c dump-autoload # composer dump-autoload
```

### Symfony Console

```bash
sf cache:clear      # Alias for php bin/console cache:clear
sf d:m:m           # doctrine:migrations:migrate
sf d:f:l           # doctrine:fixtures:load
sf make:entity     # make:entity
```

*(Add these aliases to `.bashrc` or `.zshrc`)*

---

## Legacy CSV Reference

**⚠️ DEPRECATED** - For legacy CSV mode only (`--from-csv` flag)

### CSV Files Location

- `config/EntityNew.csv` - Entity definitions
- `config/PropertyNew.csv` - Property definitions

### CSV Quick Reference

```csv
# Entity.csv example
Contact,Contact,Contacts,bi-person,,true,true,"GetCollection,Get,Post,Put,Delete",...

# Property.csv example (name field)
Contact,name,Name,string,false,255,,,false,,,,,,,,,,"NotBlank,Length(min=2,max=255)",...
```

### Migrating from CSV to Database

```bash
# Import existing CSV data into database
php bin/console app:import-csv-to-database

# Verify import
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM generator_entity"

# Generate from database (new default)
php bin/console gen
```

For complete CSV column reference, see `GENERATOR_V2_DATABASE_PLAN.md`.

---

## Resources

- **User Guide**: `docs/Generator/GeneratorUserGuide.md`
- **Developer Guide**: `docs/Generator/GeneratorDeveloperGuide.md`
- **Deployment**: `docs/ProductionDeployment.md`
- **Database Plan**: `GENERATOR_V2_DATABASE_PLAN.md`

---

**Pro Tip**: Keep this file open in a second terminal for quick reference while working!
