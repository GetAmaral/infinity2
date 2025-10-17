# Generator Cheat Sheets

Quick reference for common tasks and commands.

---

## Table of Contents

1. [CLI Commands](#cli-commands)
2. [CSV Column Reference](#csv-column-reference)
3. [Doctrine Types → Form Types](#doctrine-types--form-types)
4. [Common Validation Rules](#common-validation-rules)
5. [Troubleshooting](#troubleshooting)
6. [Performance Tips](#performance-tips)

---

## CLI Commands

### Generation

```bash
# Generate all entities
php bin/console app:generate-from-csv

# Generate single entity
php bin/console app:generate-from-csv --entity=Contact

# Dry run (preview only)
php bin/console app:generate-from-csv --dry-run

# Batch generation
php scripts/batch-generate.php --batch=10

# With options
php scripts/batch-generate.php --batch=5 --continue-on-error --skip-tests
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

## CSV Column Reference

### Entity.csv Quick Reference

| Column | Type | Example | Notes |
|--------|------|---------|-------|
| `entityName` | string | `Contact` | PascalCase, required |
| `entityLabel` | string | `Contact` | Singular label |
| `pluralLabel` | string | `Contacts` | Plural label |
| `icon` | string | `bi-person` | Bootstrap icon |
| `hasOrganization` | boolean | `true` | Multi-tenant |
| `apiEnabled` | boolean | `true` | Expose API |
| `operations` | string | `GetCollection,Get,Post` | API operations |
| `searchableFields` | string | `name,email` | Search fields |
| `filterableFields` | string | `status,active` | Filter fields |
| `voterEnabled` | boolean | `true` | Generate voter |
| `menuGroup` | string | `CRM` | Menu group |
| `menuOrder` | int | `10` | Menu position |

### Property.csv Quick Reference

| Column | Type | Example | Notes |
|--------|------|---------|-------|
| `entityName` | string | `Contact` | Parent entity |
| `propertyName` | string | `email` | camelCase, required |
| `propertyType` | string | `string` | Doctrine type |
| `nullable` | boolean | `false` | Allow NULL |
| `length` | int | `255` | For strings |
| `unique` | boolean | `true` | Unique constraint |
| `relationshipType` | string | `ManyToOne` | Relation type |
| `targetEntity` | string | `Organization` | Related entity |
| `validationRules` | string | `NotBlank,Email` | Constraints |
| `formType` | string | `EmailType` | Form type |
| `showInList` | boolean | `true` | Show in list |
| `searchable` | boolean | `true` | Searchable |
| `apiReadable` | boolean | `true` | API readable |

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

### CSV Validation Errors

**Error**: `Invalid entity name`
```bash
# Fix: Use PascalCase
entityName: contact ❌
entityName: Contact ✅
```

**Error**: `Target entity does not exist`
```bash
# Fix: Ensure target entity is in Entity.csv
targetEntity: Organisation ❌ (typo)
targetEntity: Organization ✅
```

**Error**: `Invalid property type`
```bash
# Fix: Use valid Doctrine type
propertyType: String ❌
propertyType: string ✅
```

### Generation Errors

**Error**: `Class already exists`
```bash
# Solution: Remove old file or regenerate
rm src/Entity/Contact.php
php bin/console app:generate-from-csv --entity=Contact
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

## Common Patterns

### Simple Entity

```csv
# Entity.csv
Contact,Contact,Contacts,bi-person,,true,true,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_USER'),,,,true,30,"{""name"": ""asc""}","name,email","status",true,"VIEW,EDIT,DELETE",,,,CRM,10,true

# Property.csv (name field)
Contact,name,Name,string,false,255,,,false,,,,,,,,,,"NotBlank,Length(min=2,max=255)",,TextType,,,true,true,true,true,true,true,true,true,true,,,,name,
```

### Entity with Organization

```csv
# Entity.csv
Contact,Contact,Contacts,bi-person,,true,true,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_USER'),,,,true,30,"{""name"": ""asc""}","name,email","status",true,"VIEW,EDIT,DELETE",,,,CRM,10,true

# Auto-adds OrganizationTrait when hasOrganization=true
```

### ManyToOne Relationship

```csv
# Property.csv
Contact,organization,Organization,,,,,,,,,ManyToOne,Organization,,,,,,,,NotBlank,,,EntityType,"{""class"": ""App\\Entity\\Organization"", ""choice_label"": ""name""}",true,false,,true,true,true,false,false,false,true,false,,,,,
```

### Searchable Text Field

```csv
# Property.csv
Contact,description,Description,text,true,,,,false,,,,,,,,,,"Length(max=1000)",,TextareaType,"{""attr"": {""rows"": 5}}",false,false,Detailed description,true,true,false,false,true,false,true,true,,,,,text,
```

---

## Quick Workflows

### Add New Entity

1. Add row to `Entity.csv`
2. Add properties to `Property.csv`
3. Generate: `php bin/console app:generate-from-csv --entity=NewEntity`
4. Migrate: `php bin/console make:migration && php bin/console doctrine:migrations:migrate`
5. Test: `php bin/phpunit tests/Entity/NewEntityTest.php`

### Update Existing Entity

1. Edit `Entity.csv` or `Property.csv`
2. Regenerate: `php bin/console app:generate-from-csv --entity=Contact`
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

## Resources

- **User Guide**: `docs/GeneratorUserGuide.md`
- **Developer Guide**: `docs/GeneratorDeveloperGuide.md`
- **Deployment**: `docs/ProductionDeployment.md`
- **Phase Plans**: `docs/GeneratorPlan/*.md`

---

**Pro Tip**: Keep this file open in a second terminal for quick reference while working!
