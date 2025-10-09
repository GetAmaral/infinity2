# Luminai Code Generator - Implementation Plan V4.0

## Executive Summary

This document outlines the complete implementation plan for the Luminai CSV-to-PHP Code Generator - a production-ready system that generates fully functional CRUD operations from CSV definitions.

### What's New in V4.0

**Major Changes:**
1. **OrganizationTrait Pattern** - Multi-tenant organization support via reusable trait instead of individual properties
2. **Repository Abstract Layer** - Repositories now follow the same Generated + Extension pattern as Controllers/Voters/Forms
3. **Enhanced CSV Migration** - Complete migration from current mixed CSV format to separated Entity.csv + Property.csv

**Why These Changes:**
- **OrganizationTrait**: Reduces code duplication, ensures consistency, easier maintenance for multi-tenant features
- **Repository Pattern**: Enables custom query methods while preserving auto-generated search/filter capabilities
- **CSV Separation**: Better organization, clearer structure, easier to maintain and extend

### System Capabilities

The generator creates **17+ files per entity** from CSV definitions:

| Category | Generated Files |
|----------|----------------|
| **Entity Layer** | EntityGenerated.php, Entity.php, OrganizationTrait.php (when needed) |
| **API Configuration** | Entity.yaml (API Platform) |
| **Repository Layer** | EntityRepositoryGenerated.php, EntityRepository.php |
| **Controller Layer** | EntityControllerGenerated.php, EntityController.php |
| **Security Layer** | EntityVoterGenerated.php, EntityVoter.php |
| **Form Layer** | EntityTypeGenerated.php, EntityType.php |
| **UI Layer** | index.html.twig, form.html.twig, show.html.twig |
| **Navigation** | Updated navbar with entity menu items |
| **Translations** | Entity messages in translations/messages.en.yaml |
| **Tests** | Entity, Repository, Controller, Voter test classes |

**Total Development Time:** 11 weeks (8 implementation phases)

---

## Architecture Overview

### Generation Pattern: Inheritance with Abstract Layer

All generated code uses the **Generated + Extension** pattern:

```
┌─────────────────────────────────────┐
│   Generated Base (Abstract)         │
│   - Always regenerated from CSV     │
│   - Contains all auto-generated     │
│   - MappedSuperclass/Abstract       │
└────────────┬────────────────────────┘
             │ extends
             ▼
┌─────────────────────────────────────┐
│   Extension Class (Concrete)        │
│   - Generated ONCE, safe to edit    │
│   - Custom business logic           │
│   - Preserved between regenerations │
└─────────────────────────────────────┘
```

**Applied To:**
- Entities (ContactGenerated → Contact)
- Repositories (ContactRepositoryGenerated → ContactRepository)
- Controllers (ContactControllerGenerated → ContactController)
- Voters (ContactVoterGenerated → ContactVoter)
- Form Types (ContactTypeGenerated → ContactType)

**Benefits:**
- ✅ Safe customization in extension classes
- ✅ Automatic updates from CSV changes
- ✅ No code parsing needed
- ✅ Full IDE support and autocomplete
- ✅ Clear separation of concerns

### Multi-Tenant Architecture with OrganizationTrait

**NEW in V4:** Organization support via trait pattern instead of individual properties.

```php
// src/Entity/Trait/OrganizationTrait.php (Generated ONCE)
<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait OrganizationTrait
{
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read'])]
    protected Organization $organization;

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }
}
```

**Usage in Generated Entities:**

```php
// src/Entity/Generated/ContactGenerated.php
use App\Entity\Trait\OrganizationTrait;

#[ORM\MappedSuperclass]
abstract class ContactGenerated extends EntityBase
{
    use OrganizationTrait;  // ← Imported when hasOrganization=true in CSV

    // Other properties...
}
```

**CSV Control:**
```csv
entityName,hasOrganization,...
Contact,true,...           ← Will import OrganizationTrait
Task,false,...            ← Will NOT import OrganizationTrait
```

**Benefits:**
- ✅ Single source of truth for organization logic
- ✅ Easy updates (change trait, all entities updated)
- ✅ Reduced code duplication
- ✅ Consistent multi-tenant behavior
- ✅ Clear ownership semantics

---

## CSV Structure

The system uses **two CSV files** as the single source of truth:

### 1. Entity.csv (25 columns)

Defines entity-level metadata:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `entityName` | string | Entity class name | `Contact` |
| `entityLabel` | string | Display label | `Contact` |
| `pluralLabel` | string | Plural form | `Contacts` |
| `icon` | string | Bootstrap icon | `bi-person` |
| `description` | string | Entity description | `Manages customer contacts` |
| `hasOrganization` | boolean | Multi-tenant? | `true` |
| `apiEnabled` | boolean | Expose API? | `true` |
| `operations` | string | API operations (comma-separated) | `GetCollection,Get,Post,Put,Delete` |
| `security` | string | API security | `is_granted('ROLE_USER')` |
| `normalizationContext` | string | API normalization groups | `contact:read,audit:read` |
| `denormalizationContext` | string | API denormalization groups | `contact:write` |
| `paginationEnabled` | boolean | Enable pagination? | `true` |
| `itemsPerPage` | int | Items per page | `30` |
| `order` | string | Default order | `{"name": "asc"}` |
| `searchableFields` | string | Search fields (comma-separated) | `name,email,phone` |
| `filterableFields` | string | Filter fields (comma-separated) | `status,active` |
| `voterEnabled` | boolean | Generate voter? | `true` |
| `voterAttributes` | string | Voter attributes | `VIEW,EDIT,DELETE` |
| `formTheme` | string | Form theme | `bootstrap_5_layout.html.twig` |
| `indexTemplate` | string | Custom index template | `contact/index.html.twig` |
| `formTemplate` | string | Custom form template | `contact/form.html.twig` |
| `showTemplate` | string | Custom show template | `contact/show.html.twig` |
| `menuGroup` | string | Navbar menu group | `CRM` |
| `menuOrder` | int | Menu display order | `10` |
| `testEnabled` | boolean | Generate tests? | `true` |

**Example Row:**
```csv
Contact,Contact,Contacts,bi-person,Manages customer contacts,true,true,"GetCollection,Get,Post,Put,Delete",is_granted('ROLE_USER'),"contact:read,audit:read",contact:write,true,30,"{""name"": ""asc""}","name,email,phone","status,active",true,"VIEW,EDIT,DELETE",bootstrap_5_layout.html.twig,,,CRM,10,true
```

### 2. Property.csv (38 columns)

Defines property-level metadata for each entity field:

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| `entityName` | string | Parent entity | `Contact` |
| `propertyName` | string | Property name | `email` |
| `propertyLabel` | string | Display label | `Email Address` |
| `propertyType` | string | Doctrine type | `string` |
| `nullable` | boolean | Allows null? | `false` |
| `length` | int | Max length (strings) | `255` |
| `precision` | int | Precision (decimals) | `10` |
| `scale` | int | Scale (decimals) | `2` |
| `unique` | boolean | Unique constraint? | `true` |
| `defaultValue` | string | Default value | `active` |
| `relationshipType` | string | Relation type | `ManyToOne,OneToMany,ManyToMany` |
| `targetEntity` | string | Related entity | `Organization` |
| `inversedBy` | string | Inverse relation | `contacts` |
| `mappedBy` | string | Mapped by field | `contact` |
| `cascade` | string | Cascade operations | `persist,remove` |
| `orphanRemoval` | boolean | Remove orphans? | `true` |
| `fetch` | string | Fetch strategy | `EAGER,LAZY,EXTRA_LAZY` |
| `orderBy` | string | Order related items | `{"name": "asc"}` |
| `validationRules` | string | Constraints (comma-separated) | `NotBlank,Email,Length(min=5)` |
| `validationMessage` | string | Custom error message | `Please enter valid email` |
| `formType` | string | Symfony form type | `EmailType` |
| `formOptions` | string | Form field options (JSON) | `{"attr": {"placeholder": "email@example.com"}}` |
| `formRequired` | boolean | Required in form? | `true` |
| `formReadOnly` | boolean | Read-only in form? | `false` |
| `formHelp` | string | Help text | `Enter your work email` |
| `showInList` | boolean | Show in list view? | `true` |
| `showInDetail` | boolean | Show in detail view? | `true` |
| `showInForm` | boolean | Show in form? | `true` |
| `sortable` | boolean | Sortable in list? | `true` |
| `searchable` | boolean | Searchable? | `true` |
| `filterable` | boolean | Filterable? | `true` |
| `apiReadable` | boolean | Readable via API? | `true` |
| `apiWritable` | boolean | Writable via API? | `true` |
| `apiGroups` | string | Serialization groups | `contact:read,contact:write` |
| `translationKey` | string | Custom translation key | `contact.email` |
| `formatPattern` | string | Display format | `%s@example.com` |
| `fixtureType` | string | Faker fixture type | `email` |
| `fixtureOptions` | string | Fixture options (JSON) | `{"unique": true}` |

**Example Row:**
```csv
Contact,email,Email Address,string,false,255,,,true,,,,,,,,,,,"NotBlank,Email",Please enter valid email,EmailType,"{""attr"": {""placeholder"": ""email@example.com""}}",true,false,Enter your work email,true,true,true,true,true,true,true,true,"contact:read,contact:write",contact.email,,email,"{""unique"": true}"
```

### CSV Validation Rules

**Entity.csv Validation:**
- `entityName`: Required, alphanumeric, PascalCase
- `entityLabel`: Required, non-empty string
- `pluralLabel`: Required, non-empty string
- `icon`: Required, Bootstrap icon format (`bi-*`)
- `hasOrganization`: Boolean (true/false)
- `apiEnabled`: Boolean (true/false)
- `operations`: Comma-separated API Platform operations
- `itemsPerPage`: Positive integer (1-1000)
- `menuGroup`: Optional string
- `menuOrder`: Integer (0-999)

**Property.csv Validation:**
- `entityName`: Must exist in Entity.csv
- `propertyName`: Required, camelCase
- `propertyType`: Valid Doctrine type
- `nullable`: Boolean (true/false)
- `length`: Positive integer (required for strings)
- `relationshipType`: Valid relation type when specified
- `targetEntity`: Required for relationships, must exist in Entity.csv
- `validationRules`: Symfony validation constraints
- `formType`: Valid Symfony form type
- `apiGroups`: Comma-separated serialization groups

---

## Technology Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| **Symfony** | 7.3+ | Framework foundation |
| **API Platform** | 4.1+ | REST/GraphQL API |
| **Doctrine ORM** | 3.5+ | Database abstraction |
| **PHP** | 8.2+ | Language |
| **Twig** | 3.x | Template engine (UI + code generation) |
| **PHPUnit** | 12.x | Testing framework |
| **Faker** | Latest | Test fixture generation |
| **Doctrine Inflector** | Latest | Pluralization |
| **Symfony Process** | 7.3+ | Secure command execution |
| **Bootstrap** | 5.3 | UI framework |
| **Turbo/Hotwire** | 8.x | Frontend interactivity |

---

## File Structure

```
luminai/
├── config/
│   ├── Entity.csv                              # Entity definitions
│   ├── Property.csv                            # Property definitions
│   └── api_platform/
│       └── {Entity}.yaml                       # API Platform configs (generated)
├── src/
│   ├── Command/
│   │   └── GenerateFromCsvCommand.php          # CLI command
│   ├── Service/
│   │   ├── Generator/
│   │   │   ├── Csv/
│   │   │   │   ├── CsvParserService.php        # CSV parser
│   │   │   │   ├── CsvValidatorService.php     # CSV validator
│   │   │   │   ├── CsvMigrationService.php     # CSV migration tool
│   │   │   │   ├── EntityDefinitionDto.php     # Entity DTO
│   │   │   │   └── PropertyDefinitionDto.php   # Property DTO
│   │   │   ├── Entity/
│   │   │   │   └── EntityGenerator.php         # Entity generator
│   │   │   ├── ApiPlatform/
│   │   │   │   └── ApiPlatformGenerator.php    # API config generator
│   │   │   ├── Repository/
│   │   │   │   └── RepositoryGenerator.php     # Repository generator
│   │   │   ├── Controller/
│   │   │   │   └── ControllerGenerator.php     # Controller generator
│   │   │   ├── Voter/
│   │   │   │   └── VoterGenerator.php          # Voter generator
│   │   │   ├── Form/
│   │   │   │   └── FormGenerator.php           # Form generator
│   │   │   ├── Template/
│   │   │   │   └── TemplateGenerator.php       # Template generator
│   │   │   ├── Navigation/
│   │   │   │   └── NavigationGenerator.php     # Navigation generator
│   │   │   ├── Translation/
│   │   │   │   └── TranslationGenerator.php    # Translation generator
│   │   │   └── Test/
│   │   │       ├── EntityTestGenerator.php     # Entity test generator
│   │   │       ├── RepositoryTestGenerator.php # Repository test generator
│   │   │       ├── ControllerTestGenerator.php # Controller test generator
│   │   │       └── VoterTestGenerator.php      # Voter test generator
│   │   ├── BackupService.php                   # Backup/restore
│   │   └── GeneratorOrchestrator.php           # Main orchestrator
│   ├── Entity/
│   │   ├── Trait/
│   │   │   └── OrganizationTrait.php           # Organization trait (generated once)
│   │   ├── Generated/
│   │   │   └── {Entity}Generated.php           # Generated entity bases
│   │   └── {Entity}.php                        # Entity extensions (custom)
│   ├── Repository/
│   │   ├── Generated/
│   │   │   └── {Entity}RepositoryGenerated.php # Generated repository bases
│   │   └── {Entity}Repository.php              # Repository extensions (custom)
│   ├── Controller/
│   │   ├── Generated/
│   │   │   └── {Entity}ControllerGenerated.php # Generated controller bases
│   │   └── {Entity}Controller.php              # Controller extensions (custom)
│   ├── Security/
│   │   ├── Voter/
│   │   │   ├── Generated/
│   │   │   │   └── {Entity}VoterGenerated.php  # Generated voter bases
│   │   │   └── {Entity}Voter.php               # Voter extensions (custom)
│   └── Form/
│       ├── Generated/
│       │   └── {Entity}TypeGenerated.php       # Generated form bases
│       └── {Entity}Type.php                    # Form extensions (custom)
├── templates/
│   ├── {entity}/
│   │   ├── index.html.twig                     # List view (generated)
│   │   ├── form.html.twig                      # Form view (generated)
│   │   └── show.html.twig                      # Detail view (generated)
│   └── base.html.twig                          # Base template (navigation markers)
├── translations/
│   └── messages.en.yaml                        # Translations (merged)
├── tests/
│   ├── Entity/
│   │   └── {Entity}Test.php                    # Entity tests
│   ├── Repository/
│   │   └── {Entity}RepositoryTest.php          # Repository tests
│   ├── Controller/
│   │   └── {Entity}ControllerTest.php          # Controller tests
│   └── Security/
│       └── Voter/
│           └── {Entity}VoterTest.php           # Voter tests
├── var/
│   └── generatorBackup/
│       └── {timestamp}/
│           ├── manifest.json                   # Backup metadata
│           ├── {backup-file}.bak               # Backed up files
│           └── {backup-file}.md5               # File checksums
└── templates/
    └── generator/                              # Twig templates for code generation
        ├── entity.php.twig
        ├── repository.php.twig
        ├── controller.php.twig
        ├── voter.php.twig
        ├── form.php.twig
        ├── api_platform.yaml.twig
        ├── index.html.twig.twig
        ├── form.html.twig.twig
        └── show.html.twig.twig
```

---

## Generation Workflow

```
┌─────────────────────────────────────────────────────────────┐
│  1. Read CSV Files                                           │
│     - Parse Entity.csv                                       │
│     - Parse Property.csv                                     │
│     - Validate all definitions                               │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Create DTOs                                              │
│     - EntityDefinitionDto for each entity                    │
│     - PropertyDefinitionDto for each property                │
│     - Link properties to entities                            │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Backup Existing Files                                    │
│     - Create timestamped backup directory                    │
│     - Copy existing files                                    │
│     - Generate checksums                                     │
│     - Create manifest.json                                   │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  4. Generate Entity Layer                                    │
│     - OrganizationTrait.php (if not exists)                  │
│     - Entity/Generated/{Entity}Generated.php (always)        │
│     - Entity/{Entity}.php (once)                             │
│     - config/api_platform/{Entity}.yaml (always)             │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  5. Generate Repository Layer                                │
│     - Repository/Generated/{Entity}RepositoryGenerated.php   │
│     - Repository/{Entity}Repository.php (once)               │
│     - Register in services.yaml                              │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  6. Generate Controller Layer                                │
│     - Controller/Generated/{Entity}ControllerGenerated.php   │
│     - Controller/{Entity}Controller.php (once)               │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  7. Generate Security Layer                                  │
│     - Security/Voter/Generated/{Entity}VoterGenerated.php    │
│     - Security/Voter/{Entity}Voter.php (once)                │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  8. Generate Form Layer                                      │
│     - Form/Generated/{Entity}TypeGenerated.php               │
│     - Form/{Entity}Type.php (once)                           │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  9. Generate UI Layer                                        │
│     - templates/{entity}/index.html.twig (always)            │
│     - templates/{entity}/form.html.twig (always)             │
│     - templates/{entity}/show.html.twig (always)             │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  10. Update Navigation                                       │
│      - Inject menu items into templates/base.html.twig      │
│      - Preserve custom menu items (marker-based)            │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  11. Update Translations                                     │
│      - Add entity labels to translations/messages.en.yaml   │
│      - Merge with existing translations                     │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  12. Generate Tests                                          │
│      - tests/Entity/{Entity}Test.php                         │
│      - tests/Repository/{Entity}RepositoryTest.php           │
│      - tests/Controller/{Entity}ControllerTest.php           │
│      - tests/Security/Voter/{Entity}VoterTest.php            │
└────────────────────┬────────────────────────────────────────┘
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  13. Run Post-Generation Tasks                               │
│      - Create database migration (doctrine:migrations:diff)  │
│      - Clear cache (cache:clear)                             │
│      - Warm cache (cache:warmup)                             │
│      - Run tests (bin/phpunit)                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Implementation Timeline

| Phase | Duration | Focus |
|-------|----------|-------|
| **Phase 1: Foundation** | Week 1 | CSV parser, validator, DTOs, backup system |
| **Phase 2: Code Generators** | Weeks 2-3 | Entity, Repository, Controller, Voter, Form generators |
| **Phase 3: UI Generators** | Week 4 | Template, Navigation, Translation generators |
| **Phase 4: Test Generators** | Week 5 | Test generators for all layers |
| **Phase 5: CLI & Orchestrator** | Week 6 | CLI command, orchestrator, integration |
| **Phase 6: CSV Migration** | Weeks 7-8 | Migrate existing CSV, migrate existing entities |
| **Phase 7: Bulk Generation** | Weeks 9-10 | Generate all 50+ new entities |
| **Phase 8: Polish & Documentation** | Week 11 | Quality checks, documentation, optimization |

**Total:** 11 weeks

---

## Success Criteria

✅ **Code Generation:**
- All 17+ files generated correctly from CSV
- Generated code passes PHPStan level 8
- No deprecation warnings
- Follows Symfony best practices

✅ **Testing:**
- 80%+ code coverage
- All generated tests pass
- Manual testing successful for CRUD operations

✅ **Performance:**
- Generation completes in < 2 minutes per entity
- No memory issues for bulk generation
- Backup system completes in < 10 seconds

✅ **Safety:**
- Backup system verified with checksums
- Custom code preserved in extension classes
- Rollback works correctly

✅ **User Experience:**
- Clear CLI output with progress indicators
- Helpful error messages
- Comprehensive documentation

---

## Next Steps

This overview provides the architectural foundation. The following documents detail each implementation phase:

- **01-Phase1-Foundation.md** - CSV parsing, validation, DTOs, backup
- **02-Phase2-CodeGenerators.md** - Entity, Repository, Controller, Voter, Form
- **03-Phase3-UIGenerators.md** - Templates, Navigation, Translations
- **04-Phase4-TestGenerators.md** - Test generation for all layers
- **05-Phase5-CLI.md** - CLI command and orchestrator
- **06-Phase6-Migration.md** - CSV and entity migration
- **07-Phase7-NewEntities.md** - Bulk generation of new entities
- **08-Phase8-Polish.md** - Final quality and documentation
- **09-Testing-Strategy.md** - Comprehensive testing approach

**Start with Phase 1 to build the foundation.**
